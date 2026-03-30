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

namespace App\Service\Permission;

use App\Common\CacheKeys;
use App\Model\Permission\SysPermissionDictModel;
use Hyperf\DbConnection\Db;
use Throwable;

class BatchDelete
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    public function handle(array $data): int
    {
        $isSuperAdmin = currentUser('is_admin') == 1;
        if (! $isSuperAdmin) {
            paramError('只有超级管理员可以批量删除权限', 403);
        }

        $ids = $data['ids'] ?? [];
        empty($ids) && paramError('权限ID不能为空');

        Db::beginTransaction();
        try {
            $deletedCount = 0;
            $allDeletedIds = [];

            foreach ($ids as $id) {
                $permission = $this->permissionModel->find($id);
                if (! $permission) {
                    continue;
                }

                $permIds = $this->getPermissionAndChildrenIds($id);
                $allDeletedIds = array_merge($allDeletedIds, $permIds);

                Db::table('sys_menu_permission')->whereIn('permission_id', $permIds)->delete();
                $deletedCount += $this->permissionModel->whereIn('id', $permIds)->delete();
            }

            Db::commit();
            CacheKeys::flush(CacheKeys::PERM_OPTIONS, CacheKeys::PERM_TREE, CacheKeys::PERM_API, CacheKeys::PERM_USER, CacheKeys::MENU_USER, CacheKeys::MENU_SUPER);
            return $deletedCount;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    private function getPermissionAndChildrenIds(int $permissionId): array
    {
        $ids = [$permissionId];
        $children = $this->permissionModel->where('parent_id', $permissionId)->pluck('id')->toArray();
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getPermissionAndChildrenIds($childId));
        }
        return $ids;
    }
}
