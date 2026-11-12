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

namespace App\Service\Admin\Manage;

use App\Common\CacheKeys;
use App\Model\Admin\SysAdminModel;
use App\Model\Rbac\SysMenuModel;
use Psr\SimpleCache\CacheInterface;

class GetMenu
{
    public function __construct(
        protected SysMenuModel $menuModel,
        protected SysAdminModel $adminModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(): array
    {
        $isSuperAdmin = currentUser('is_admin') == 1;
        $userId = (int) currentUser('id');

        $cacheKey = $isSuperAdmin ? CacheKeys::MENU_SUPER : CacheKeys::menuUser($userId);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        if ($isSuperAdmin) {
            $menus = $this->menuModel
                ->where('status', 1)
                ->orderBy('sort_order', 'asc')
                ->get()
                ->toArray();
        } else {
            $permissionCodes = $this->getUserPermissionCodes($userId);

            // 过滤条件必须始终生效：之前写成 when(! empty($permissionCodes), ...)，
            // 没有权限码的用户会跳过整个过滤，直接拿到全部菜单。
            // 空权限码时只保留目录（type=0，仅用于分组，不含可访问页面）。
            $menus = $this->menuModel
                ->where('status', 1)
                ->where(function ($sub) use ($permissionCodes) {
                    $sub->where('type', 0);

                    if (! empty($permissionCodes)) {
                        $sub->orWhereIn('permission_code', $permissionCodes);
                    }
                })
                ->orderBy('sort_order', 'asc')
                ->get()
                ->toArray();
        }

        $result = $this->menuModel->buildTree($menus);
        $this->cache->set($cacheKey, $result, CacheKeys::TTL_MEDIUM);

        return $result;
    }

    protected function getUserPermissionCodes(int $userId): array
    {
        return $this->adminModel->find($userId)?->permissionCodes() ?? [];
    }
}
