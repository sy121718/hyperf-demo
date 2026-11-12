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

namespace HyperfTest\Cases\Unit\Service\Admin\Manage;

use App\Common\CacheKeys;
use App\Model\Admin\SysAdminModel;
use App\Model\Rbac\SysMenuModel;
use App\Service\Admin\Manage\GetMenu;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * 菜单树按登录身份走不同缓存 key，写错会导致「超管菜单」和「普通用户菜单」串号.
 * @internal
 * @coversNothing
 */
class GetMenuTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    /**
     * 超管用固定 key MENU_SUPER，命中缓存时直接返回，不查库.
     */
    public function testSuperAdminUsesSuperCacheKey(): void
    {
        Context::set('auth_user', ['id' => 1, 'username' => 'admin', 'is_admin' => 1]);

        $cached = [['id' => 1, 'menu_name' => '系统管理', 'children' => []]];

        /** @var CacheInterface|MockInterface $cache */
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->once()->with(CacheKeys::MENU_SUPER)->andReturn($cached);

        $menuModel = Mockery::mock(SysMenuModel::class);
        $menuModel->shouldNotReceive('buildTree');

        $adminModel = Mockery::mock(SysAdminModel::class);

        $result = (new GetMenu($menuModel, $adminModel, $cache))->handle();

        $this->assertSame($cached, $result);
    }

    /**
     * 普通用户用带 userId 的 key，不能和超管共用.
     */
    public function testNormalUserUsesPerUserCacheKey(): void
    {
        Context::set('auth_user', ['id' => 42, 'username' => 'plain', 'is_admin' => 0]);

        $cached = [];

        /** @var CacheInterface|MockInterface $cache */
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->once()->with(CacheKeys::menuUser(42))->andReturn($cached);

        $menuModel = Mockery::mock(SysMenuModel::class);
        $adminModel = Mockery::mock(SysAdminModel::class);

        $result = (new GetMenu($menuModel, $adminModel, $cache))->handle();

        $this->assertSame($cached, $result);
    }

    /**
     * 超级管理员的 is_admin 直接决定分支，不能误走普通用户路径.
     */
    public function testSuperAdminKeyDiffersFromUserKey(): void
    {
        $this->assertNotSame(CacheKeys::MENU_SUPER, CacheKeys::menuUser(1));
    }

    /**
     * 缓存未命中时，普通用户的菜单按角色权限码过滤，且结果写回自己的 key.
     *
     * 权限码解析走 SysAdminModel::permissionCodes()（全项目唯一入口），
     * 这里只验证 GetMenu 确实用了它并按预期过滤，不重复测模型内部实现。
     */
    public function testNormalUserFiltersMenusByPermissionCodes(): void
    {
        Context::set('auth_user', ['id' => 42, 'username' => 'plain', 'is_admin' => 0]);

        $admin = Mockery::mock(SysAdminModel::class);
        $admin->shouldReceive('permissionCodes')->once()->andReturn(['role:list']);

        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldReceive('find')->with(42)->andReturn($admin);

        $menuRows = [['id' => 1, 'menu_name' => '角色管理', 'parent_id' => 0]];
        $tree = [['id' => 1, 'menu_name' => '角色管理', 'children' => []]];

        $menuModel = Mockery::mock(SysMenuModel::class);
        $menuModel->shouldReceive('where->where->orderBy->get->toArray')->andReturn($menuRows);
        $menuModel->shouldReceive('buildTree')->with($menuRows)->andReturn($tree);

        /** @var CacheInterface|MockInterface $cache */
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->with(CacheKeys::menuUser(42))->andReturn(null);
        $cache->shouldReceive('set')->once()->with(CacheKeys::menuUser(42), $tree, CacheKeys::TTL_MEDIUM)->andReturn(true);

        $result = (new GetMenu($menuModel, $adminModel, $cache))->handle();

        $this->assertSame($tree, $result);
    }

    /**
     * 没有任何权限码的普通用户，不能拿到全部菜单——只应看到目录分组.
     *
     * 原实现把过滤写成 when(! empty($permissionCodes), ...)，权限码为空时
     * 整个过滤被跳过，SQL 退化成「status=1」，等于把全部菜单都返回了。
     */
    public function testUserWithoutPermissionStillGetsFilteredQuery(): void
    {
        Context::set('auth_user', ['id' => 43, 'username' => 'nobody', 'is_admin' => 0]);

        $admin = Mockery::mock(SysAdminModel::class);
        $admin->shouldReceive('permissionCodes')->once()->andReturn([]);

        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldReceive('find')->with(43)->andReturn($admin);

        $menuRows = [['id' => 1, 'menu_name' => '系统管理', 'type' => 0, 'parent_id' => 0]];

        // 断言过滤条件仍然被施加（where 链存在），而不是直接 orderBy
        $menuModel = Mockery::mock(SysMenuModel::class);
        $menuModel->shouldReceive('where->where->orderBy->get->toArray')->once()->andReturn($menuRows);
        $menuModel->shouldReceive('buildTree')->andReturn($menuRows);

        /** @var CacheInterface|MockInterface $cache */
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->andReturn(null);
        $cache->shouldReceive('set')->andReturn(true);

        $result = (new GetMenu($menuModel, $adminModel, $cache))->handle();

        $this->assertSame($menuRows, $result);
    }
}
