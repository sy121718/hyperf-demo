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

use App\Exception\BusinessException;
use App\Model\Admin\SysAdminSessionsModel;
use App\Service\Ops\Session\CleanExpired;
use App\Service\Ops\Session\GetInfo;
use App\Service\Ops\Session\Kickout;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\Testing\TestCase;
use Mockery;
use Psr\SimpleCache\CacheInterface;
use stdClass;

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
        $session = Mockery::mock(stdClass::class);
        $session->shouldReceive('toArray')->andReturn(['id' => 1, 'admin_id' => 5]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('find')->with(1)->andReturn($session);

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('with')->andReturn($builder);

        $result = (new GetInfo($model))->handle(1);

        $this->assertSame(1, $result['id']);
    }

    public function testGetInfoNotFound(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('find')->andReturn(null);

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('with')->andReturn($builder);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new GetInfo($model))->handle(999);
    }

    public function testGetInfoZeroId(): void
    {
        $model = Mockery::mock(SysAdminSessionsModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('会话ID不能为空');

        (new GetInfo($model))->handle(0);
    }

    public function testKickoutSuccess(): void
    {
        $session = Mockery::mock(stdClass::class);
        $session->status = 1;
        $session->jwt_token = 'abc123';
        $session->shouldReceive('save')->once()->andReturn(true);

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('find')->with(1)->andReturn($session);

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('delete')->once();

        $result = (new Kickout($model, $cache))->handle(1);

        $this->assertTrue($result);
        $this->assertSame(0, $session->status);
    }

    public function testKickoutAlreadyInactive(): void
    {
        $session = Mockery::mock(stdClass::class);
        $session->status = 0;
        $session->shouldNotReceive('save');

        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('find')->with(1)->andReturn($session);

        $cache = Mockery::mock(CacheInterface::class);

        $result = (new Kickout($model, $cache))->handle(1);

        $this->assertTrue($result);
    }

    public function testKickoutNotFound(): void
    {
        $model = Mockery::mock(SysAdminSessionsModel::class);
        $model->shouldReceive('find')->andReturn(null);

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
}
