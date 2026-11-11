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
use App\Dto\Permission\PermissionRequest;
use App\Model\Rbac\SysPermissionDictModel;
use Hyperf\DbConnection\Db;
use Throwable;

class BatchUpdate
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    /**
     * @param PermissionRequest[] $items
     */
    public function handle(array $items): array
    {
        empty($items) && paramError('权限数据不能为空');

        $updated = [];

        Db::beginTransaction();
        try {
            foreach ($items as $item) {
                $id = (int) $item->id;
                if ($id <= 0) {
                    continue;
                }

                $permission = $this->permissionModel->find($id);
                if (! $permission) {
                    continue;
                }

                if ($item->perm_code && $item->perm_code !== $permission->perm_code) {
                    $this->permissionModel->where('perm_code', $item->perm_code)
                        ->where('id', '!=', $id)
                        ->exists() && paramError("权限编码 {$item->perm_code} 已存在");
                }

                $permission->update($item->toDbArrayFiltered());
                $updated[] = $permission;
            }

            Db::commit();
            CacheKeys::flush(CacheKeys::PERM_OPTIONS, CacheKeys::PERM_TREE, CacheKeys::PERM_API, CacheKeys::PERM_CODES, CacheKeys::MENU_USER, CacheKeys::MENU_SUPER);

            return [
                'updated' => $updated,
                'updated_count' => count($updated),
            ];
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
