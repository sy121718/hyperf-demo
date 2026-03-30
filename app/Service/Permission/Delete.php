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

class Delete
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    public function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $id > 0 || paramError('权限ID不能为空');

        $permission = $this->permissionModel->find($id);
        ! $permission && paramError('权限不存在', 404);

        Db::beginTransaction();
        try {
            $allIds = $this->getPermissionAndChildrenIds($id);

            $this->permissionModel->whereIn('id', $allIds)->delete();

            Db::commit();
            CacheKeys::flush(CacheKeys::PERM_OPTIONS, CacheKeys::PERM_TREE, CacheKeys::PERM_API, CacheKeys::PERM_USER, CacheKeys::MENU_USER, CacheKeys::MENU_SUPER);
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
