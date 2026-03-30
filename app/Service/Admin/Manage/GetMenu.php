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
use App\Model\Menu\SysMenuModel;
use Hyperf\DbConnection\Db;
use Psr\SimpleCache\CacheInterface;

class GetMenu
{
    public function __construct(
        protected SysMenuModel $menuModel,
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

            $menus = $this->menuModel
                ->where('status', 1)
                ->when(! empty($permissionCodes), function ($q) use ($permissionCodes) {
                    $q->where(function ($sub) use ($permissionCodes) {
                        $sub->where('type', 0)
                            ->orWhereIn('permission_code', $permissionCodes);
                    });
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
        $rolePermissionArrays = Db::table('sys_admin_role as ar')
            ->join('sys_role as r', 'ar.role_code', '=', 'r.role_code')
            ->where('ar.admin_code', function ($q) use ($userId) {
                $q->select('admin_code')
                    ->from('sys_admin')
                    ->where('id', $userId);
            })
            ->where('r.status', 1)
            ->pluck('r.permission_codes')
            ->toArray();

        $allCodes = [];
        foreach ($rolePermissionArrays as $codes) {
            if (is_array($codes)) {
                $allCodes = array_merge($allCodes, $codes);
            }
        }

        return array_unique($allCodes);
    }
}
