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
use App\Model\Permission\SysPermissionDictModel;

class Create
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    public function handle(PermissionRequest $request): array
    {
        $permCode = (string) $request->perm_code;
        ! $this->permissionModel->isFieldUnique(['perm_code' => $permCode])
            && paramError("权限编码 {$permCode} 已存在");

        $dbData = $request->toDbArrayFiltered();
        $dbData['parent_id'] = $dbData['parent_id'] ?? 0;

        $permission = $this->permissionModel->create($dbData);

        CacheKeys::flush(CacheKeys::PERM_OPTIONS, CacheKeys::PERM_TREE, CacheKeys::PERM_API);

        return $permission->toArray();
    }
}
