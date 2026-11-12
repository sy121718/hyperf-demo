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

namespace HyperfTest\Cases\Unit\Common;

use App\Common\CacheKeys;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * 权限相关变更的缓存失效集合.
 *
 * 背景：角色、角色-权限、管理员-角色这三类变更都会影响「谁能看到什么」，
 * 之前各 Action 自己列 key，有的只清 2 个、有的清 5 个。少清的那几处
 * 会导致改完角色权限后部分用户仍看到旧菜单。收敛成单一入口后，
 * 这里锁住必需项，防止有人「顺手精简」掉某个 key。
 * @internal
 */
#[CoversNothing]
class CacheKeysPermissionTest extends TestCase
{
    public function testCoversEveryKeyAffectedByPermissionChanges(): void
    {
        $this->assertSame([
            CacheKeys::ROLE_OPTIONS,
            CacheKeys::ROLE_ALL,
            CacheKeys::PERM_CODES,
            CacheKeys::MENU_USER,
            CacheKeys::MENU_SUPER,
        ], CacheKeys::permissionRelatedKeys());
    }

    /**
     * 角色下拉和全量角色：改了角色-权限映射后，下拉里的展示信息也可能变.
     */
    public function testIncludesRoleListCaches(): void
    {
        $keys = CacheKeys::permissionRelatedKeys();

        $this->assertContains(CacheKeys::ROLE_OPTIONS, $keys);
        $this->assertContains(CacheKeys::ROLE_ALL, $keys);
    }

    /**
     * 用户权限码、用户菜单、超管菜单：权限变了必须让这三处失效.
     */
    public function testIncludesMenuAndPermissionCaches(): void
    {
        $keys = CacheKeys::permissionRelatedKeys();

        $this->assertContains(CacheKeys::PERM_CODES, $keys);
        $this->assertContains(CacheKeys::MENU_USER, $keys);
        $this->assertContains(CacheKeys::MENU_SUPER, $keys);
    }

    public function testNoDuplicateKeys(): void
    {
        $keys = CacheKeys::permissionRelatedKeys();

        $this->assertSame($keys, array_values(array_unique($keys)));
    }

    // ==================== 菜单变更 ====================

    public function testMenuChangesFlushOnlyMenuCaches(): void
    {
        $this->assertSame([
            CacheKeys::MENU_TREE,
            CacheKeys::MENU_SUPER,
            CacheKeys::MENU_USER,
        ], CacheKeys::menuRelatedKeys());
    }

    /**
     * 菜单增删改只动 sys_menus，不改变任何人的权限码集合，
     * 因此不该清 PERM_CODES（前缀 SCAN，代价不小且无收益）。
     */
    public function testMenuChangesDoNotFlushPermissionCodes(): void
    {
        $this->assertNotContains(CacheKeys::PERM_CODES, CacheKeys::menuRelatedKeys());
    }

    /**
     * 反之，权限变更必须清掉按用户维度过滤的菜单缓存，否则用户仍看到旧菜单.
     *
     * MENU_TREE 是全量菜单树（管理端菜单管理页用），不带用户维度，
     * 权限变化不影响它，所以不在这里——两边的差异是刻意的。
     */
    public function testPermissionChangesFlushPerUserMenuCaches(): void
    {
        $keys = CacheKeys::permissionRelatedKeys();

        $this->assertContains(CacheKeys::MENU_SUPER, $keys);
        $this->assertContains(CacheKeys::MENU_USER, $keys);
        $this->assertNotContains(CacheKeys::MENU_TREE, $keys);
    }
}
