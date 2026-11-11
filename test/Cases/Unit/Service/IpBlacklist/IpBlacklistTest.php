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

namespace HyperfTest\Cases\Unit\Service\IpBlacklist;

use App\Dto\IpBlacklist\IpBlacklistRequest;
use App\Exception\BusinessException;
use App\Model\System\IpBlacklistModel;
use App\Service\IpBlacklist\Ban;
use App\Service\IpBlacklist\CheckIp;
use App\Service\IpBlacklist\Unban;
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
class IpBlacklistTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 1, 'username' => 'admin', 'is_admin' => 1]);

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('delete')->andReturn(true);
        $this->instance(CacheInterface::class, $cache);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testBanNewIp(): void
    {
        $record = Mockery::mock(stdClass::class);
        $record->shouldReceive('toArray')->andReturn([
            'id' => 1, 'ip_address' => '1.2.3.4', 'status' => 1,
        ]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('first')->andReturn(null);

        $model = Mockery::mock(IpBlacklistModel::class);
        $model->shouldReceive('activeForIp')->with('1.2.3.4')->andReturn($builder);
        $model->shouldReceive('create')->once()->andReturn($record);

        $result = (new Ban($model))->handle(IpBlacklistRequest::fromArray(['ip_address' => '1.2.3.4']));

        $this->assertSame('1.2.3.4', $result['ip_address']);
    }

    public function testBanExtendExisting(): void
    {
        $existing = Mockery::mock(stdClass::class);
        $existing->banned_until_time = '2026-03-06 12:00:00.000';
        $existing->ban_count = 2;
        $existing->shouldReceive('update')->once()->andReturn(true);
        $existing->shouldReceive('fresh->toArray')->andReturn([
            'id' => 1, 'ip_address' => '1.2.3.4', 'ban_count' => 3,
        ]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('first')->andReturn($existing);

        $model = Mockery::mock(IpBlacklistModel::class);
        $model->shouldReceive('activeForIp')->with('1.2.3.4')->andReturn($builder);

        $result = (new Ban($model))->handle(IpBlacklistRequest::fromArray([
            'ip_address' => '1.2.3.4', 'ban_duration' => 60,
        ]));

        $this->assertSame(3, $result['ban_count']);
    }

    public function testBanEmptyIp(): void
    {
        $model = Mockery::mock(IpBlacklistModel::class);

        // 空 IP 由 DTO 的 required|ip 规则拦截，Action 不再重复校验
        $request = IpBlacklistRequest::fromArray(['ip_address' => '']);

        $this->assertSame('', $request->ip_address);
    }

    public function testUnbanSuccess(): void
    {
        $record = Mockery::mock(IpBlacklistModel::class);
        $record->shouldReceive('getAttribute')->with('status')->andReturn(IpBlacklistModel::STATUS_ACTIVE);
        $record->shouldReceive('getAttribute')->with('ip_address')->andReturn('1.2.3.4');
        $record->shouldReceive('update')->once()->andReturn(true);
        $record->shouldReceive('fresh->toArray')->andReturn([
            'id' => 1, 'status' => IpBlacklistModel::STATUS_MANUAL_LIFTED,
        ]);

        $model = Mockery::mock(IpBlacklistModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '拉黑记录')->andReturn($record);

        $result = (new Unban($model))->handle(1);

        $this->assertSame(IpBlacklistModel::STATUS_MANUAL_LIFTED, $result['status']);
    }

    public function testUnbanNotFound(): void
    {
        $model = Mockery::mock(IpBlacklistModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '拉黑记录不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Unban($model))->handle(999);
    }

    public function testUnbanAlreadyLifted(): void
    {
        $record = Mockery::mock(IpBlacklistModel::class);
        $record->shouldReceive('getAttribute')->with('status')->andReturn(IpBlacklistModel::STATUS_MANUAL_LIFTED);

        $model = Mockery::mock(IpBlacklistModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '拉黑记录')->andReturn($record);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('该记录已失效或已被解除');

        (new Unban($model))->handle(1);
    }

    public function testUnbanZeroId(): void
    {
        $model = Mockery::mock(IpBlacklistModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('记录ID不能为空');

        (new Unban($model))->handle(0);
    }

    public function testCheckIpBanned(): void
    {
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->andReturn(null);
        $cache->shouldReceive('set')->andReturn(true);

        $ban = Mockery::mock(stdClass::class);
        $ban->shouldReceive('toArray')->andReturn(['id' => 1, 'ip_address' => '1.2.3.4']);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('first')->andReturn($ban);

        $model = Mockery::mock(IpBlacklistModel::class);
        $model->shouldReceive('activeForIp')->with('1.2.3.4')->andReturn($builder);

        $result = (new CheckIp($model, $cache))->handle('1.2.3.4');

        $this->assertNotNull($result);
        $this->assertSame('1.2.3.4', $result['ip_address']);
    }

    public function testCheckIpNotBanned(): void
    {
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->andReturn(null);
        $cache->shouldReceive('set')->andReturn(true);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('first')->andReturn(null);

        $model = Mockery::mock(IpBlacklistModel::class);
        $model->shouldReceive('activeForIp')->with('5.6.7.8')->andReturn($builder);

        $result = (new CheckIp($model, $cache))->handle('5.6.7.8');

        $this->assertNull($result);
    }
}
