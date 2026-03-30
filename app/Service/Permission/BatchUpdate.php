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
use App\Service\Permission\Dto\PermissionItemDto;
use Hyperf\DbConnection\Db;
use Throwable;

class BatchUpdate
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    /**
     * @param PermissionItemDto[] $items
     */
    public function handle(array $items): array
    {
        empty($items) && paramError('权限数据不能为空');

        $updatedPermissions = [];

        Db::beginTransaction();
        try {
            foreach ($items as $item) {
                $dbData = $item->toDbArrayFiltered();
                $id = $dbData['id'] ?? null;
                if (! $id) {
                    continue;
                }

                $permission = $this->permissionModel->find($id);
                if (! $permission) {
                    continue;
                }

                if (! empty($dbData['perm_code']) && $dbData['perm_code'] !== $permission->perm_code) {
                    $this->permissionModel->where('perm_code', $dbData['perm_code'])
                        ->where('id', '!=', $id)
                        ->exists() && paramError("权限编码 {$dbData['perm_code']} 已存在");
                }

                unset($dbData['id']);
                $permission->update($dbData);
                $updatedPermissions[] = $permission;
            }

            Db::commit();
            CacheKeys::flush(CacheKeys::PERM_OPTIONS, CacheKeys::PERM_TREE, CacheKeys::PERM_API, CacheKeys::PERM_USER, CacheKeys::MENU_USER, CacheKeys::MENU_SUPER);

            return [
                'updated' => $updatedPermissions,
                'updated_count' => count($updatedPermissions),
            ];
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
