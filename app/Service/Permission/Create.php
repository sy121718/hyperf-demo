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

class Create
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    public function handle(PermissionItemDto $dto): array
    {
        empty($dto->permCode) && paramError('权限编码不能为空');
        empty($dto->permName) && paramError('权限名称不能为空');

        ! $this->permissionModel->isFieldUnique(['perm_code' => $dto->permCode])
            && paramError("权限编码 {$dto->permCode} 已存在");

        $dbData = $dto->toDbArray();
        unset($dbData['id']);
        $dbData['create_by'] = (int) currentUser('id');

        $permission = $this->permissionModel->create($dbData);

        CacheKeys::flush(CacheKeys::PERM_OPTIONS, CacheKeys::PERM_TREE, CacheKeys::PERM_API);

        return $permission->toArray();
    }
}
