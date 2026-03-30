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

class Update
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    public function handle(PermissionItemDto $dto): array
    {
        $id = $dto->id;
        ($id === null || $id <= 0) && paramError('权限ID不能为空');

        $permission = $this->permissionModel->find($id);
        ! $permission && paramError('权限不存在', 404);

        if ($dto->permCode && $dto->permCode !== $permission->perm_code) {
            ! $this->permissionModel->isFieldUnique(['perm_code' => $dto->permCode], $id)
                && paramError("权限编码 {$dto->permCode} 已存在");
        }

        $updateData = $dto->toDbArrayFiltered();
        unset($updateData['id']);

        $permission->update($updateData);

        CacheKeys::flush(CacheKeys::PERM_OPTIONS, CacheKeys::PERM_TREE, CacheKeys::PERM_API, CacheKeys::PERM_USER, CacheKeys::MENU_USER, CacheKeys::MENU_SUPER);

        return $permission->fresh()->toArray();
    }
}
