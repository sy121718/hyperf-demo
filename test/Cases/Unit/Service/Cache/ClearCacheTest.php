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

namespace HyperfTest\Cases\Unit\Service\Cache;

use App\Dto\Cache\CacheRequest;
use App\Exception\BusinessException;
use App\Service\Cache\ClearCache;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 * @coversNothing
 */
class ClearCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 1, 'username' => 'admin', 'is_admin' => 0]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testRejectsNonSuperAdmin(): void
    {
        // 缓存清理是全站共享操作，普通管理员必须被拦住，且不能产生任何删除动作
        /** @var CacheInterface|MockInterface $cache */
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldNotReceive('deleteMultiple');
        $this->instance(CacheInterface::class, $cache);

        $this->expectException(BusinessException::class);

        (new ClearCache())->handle(CacheRequest::fromArray(['modules' => ['role']]));
    }

    public function testAllowsSuperAdmin(): void
    {
        Context::set('auth_user', ['id' => 1, 'username' => 'admin', 'is_admin' => 1]);

        /** @var CacheInterface|MockInterface $cache */
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('deleteMultiple')->once()->andReturn(true);
        $this->instance(CacheInterface::class, $cache);

        // 只选 role 模块：它的两个 key 都是精确 key，不会走到需要 Redis 的前缀扫描
        $result = (new ClearCache())->handle(CacheRequest::fromArray(['modules' => ['role']]));

        $this->assertFalse($result['is_full_clear']);
        $this->assertSame('role', $result['cleared'][0]['module']);
        $this->assertSame(2, $result['total_deleted']);
    }
}
