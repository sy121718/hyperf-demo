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
use App\Model\Rbac\SysPermissionDictModel;
use Hyperf\DbConnection\Db;
use Throwable;

class Delete
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    public function handle(int $id): bool
    {
        $permission = $this->permissionModel->findOrNotFound($id, '权限');

        Db::beginTransaction();
        try {
            $allIds = $this->getPermissionAndChildrenIds($id);

            $this->permissionModel->whereIn('id', $allIds)->delete();

            Db::commit();
            CacheKeys::flush(CacheKeys::PERM_OPTIONS, CacheKeys::PERM_TREE, CacheKeys::PERM_API, CacheKeys::PERM_CODES, CacheKeys::MENU_USER, CacheKeys::MENU_SUPER);
            return true;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    private function getPermissionAndChildrenIds(int $permId): array
    {
        $ids = [$permId];
        $children = $this->permissionModel->where('parent_id', $permId)->pluck('id')->toArray();
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getPermissionAndChildrenIds($childId));
        }
        return $ids;
    }
}
