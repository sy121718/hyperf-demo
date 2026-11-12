<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace HyperfTest\Cases\Unit\Service\Ops\Session;

use App\Common\CacheKeys;
use App\Dto\Session\SessionRequest;
use App\Exception\BusinessException;
use App\Model\Admin\SysAdminSessionsModel;
use App\Service\Ops\Session\BatchKickout;
use App\Service\Ops\Session\CleanExpired;
use App\Service\Ops\Session\GetInfo;
use App\Service\Ops\Session\Kickout;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection as BaseCollection;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 * @coversNothing
 */
class SessionExtraTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 1, 'username' => 'admin', 'is_admin' => 1]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testGetInfoSuccess(): void
    {
        $session = Mockery::mock(SysAdminSessionsModel::class);
        $session->shouldReceive('toArray')->andReturn(['id' => 1, 'admin_id' => 5]);

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('findOrNotFound')
            ->with(1, '会话', ['admin:id,username,name'])
            ->andReturn($session);

        $result = (new GetInfo($model))->handle(1);

        $this->assertSame(1, $result['id']);
    }

    public function testGetInfoNotFound(): void
    {
        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '会话不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new GetInfo($model))->handle(999);
    }

    public function testGetInfoZeroId(): void
    {
        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '会话ID不能为空'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('会话ID不能为空');

        (new GetInfo($model))->handle(0);
    }

    public function testKickoutSuccess(): void
    {
        $status = 1;
        $session = Mockery::mock(SysAdminSessionsModel::class);
        $session->shouldReceive('getAttribute')->with('status')->andReturnUsing(function () use (&$status) {
            return $status;
        });
        $session->shouldReceive('offsetExists')->with('jwt_token')->andReturn(true);
        $session->shouldReceive('getAttribute')->with('jwt_token')->andReturn('abc123');
        $session->shouldReceive('offsetGet')->with('jwt_token')->andReturn('abc123');
        $session->shouldReceive('setAttribute')->with('status', 0)->andReturnUsing(function () use (&$status) {
            $status = 0;
        });
        $session->shouldReceive('save')->once()->andReturn(true);

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '会话')->andReturn($session);

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('delete')->once();

        $result = (new Kickout($model, $cache))->handle(1);

        $this->assertTrue($result);
        $this->assertSame(0, $status);
    }

    public function testKickoutAlreadyInactive(): void
    {
        $session = Mockery::mock(SysAdminSessionsModel::class);
        $session->shouldReceive('getAttribute')->with('status')->andReturn(0);
        $session->shouldNotReceive('save');

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '会话')->andReturn($session);

        $cache = Mockery::mock(CacheInterface::class);

        $result = (new Kickout($model, $cache))->handle(1);

        $this->assertTrue($result);
    }

    public function testKickoutNotFound(): void
    {
        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '会话不存在'));

        $cache = Mockery::mock(CacheInterface::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Kickout($model, $cache))->handle(999);
    }

    public function testCleanExpired(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('status', 1)->andReturnSelf();
        $builder->shouldReceive('update')->with(['status' => 0])->andReturn(5);

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('where')->andReturn($builder);

        $count = (new CleanExpired($model))->handle();

        $this->assertSame(5, $count);
    }

    // ==================== BatchKickout ====================

    /**
     * 只处理启用中的会话；已是下线状态的跳过，不重复写库、不重复删缓存.
     */
    public function testBatchKickoutOnlyTouchesActiveSessions(): void
    {
        $active = $this->makeSession(status: 1, token: 'tok-a', expectSave: true);
        $inactive = $this->makeSession(status: 0, token: 'tok-b', expectSave: false);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('get')->andReturn(new BaseCollection([$active, $inactive]));

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('whereIn')->with('id', [1, 2])->andReturn($builder);

        $cache = Mockery::mock(CacheInterface::class);
        // 只有启用中的那条会话需要清缓存
        $cache->shouldReceive('delete')->once()->with(CacheKeys::authSession('tok-a'));

        $count = (new BatchKickout($model, $cache))->handle(
            SessionRequest::fromArray(['ids' => [1, 2]])
        );

        $this->assertSame(1, $count, '只应统计真正被踢下线的会话');
    }

    public function testBatchKickoutWithoutTokenStillCounts(): void
    {
        $session = $this->makeSession(status: 1, token: null, expectSave: true);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('get')->andReturn(new BaseCollection([$session]));

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('whereIn')->andReturn($builder);

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldNotReceive('delete');

        $count = (new BatchKickout($model, $cache))->handle(
            SessionRequest::fromArray(['ids' => [3]])
        );

        $this->assertSame(1, $count);
    }

    public function testBatchKickoutWithEmptyIds(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('get')->andReturn(new BaseCollection([]));

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('whereIn')->with('id', [])->andReturn($builder);

        $cache = Mockery::mock(CacheInterface::class);

        $this->assertSame(
            0,
            (new BatchKickout($model, $cache))->handle(SessionRequest::fromArray([]))
        );
    }

    private function makeSession(int $status, ?string $token, bool $expectSave): MockInterface&SysAdminSessionsModel
    {
        /** @var MockInterface|SysAdminSessionsModel $session */
        $session = Mockery::mock(SysAdminSessionsModel::class);
        $session->shouldReceive('getAttribute')->with('status')->andReturn($status);
        // empty($session->jwt_token) 先走 __isset → offsetExists，命中后才读 getAttribute
        $session->shouldReceive('offsetExists')->with('jwt_token')->andReturn($token !== null);
        $session->shouldReceive('getAttribute')->with('jwt_token')->andReturn($token);
        $session->shouldReceive('offsetGet')->with('jwt_token')->andReturn($token);
        $session->shouldReceive('setAttribute')->with('status', 0)->andReturnNull();
        $session->shouldReceive('save')->times($expectSave ? 1 : 0)->andReturn(true);

        return $session;
    }
}
