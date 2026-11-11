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

class Update
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    public function handle(PermissionRequest $request): array
    {
        $id = (int) $request->id;
        $permission = $this->permissionModel->findOrNotFound($id, '权限');

        if ($request->perm_code && $request->perm_code !== $permission->perm_code) {
            ! $this->permissionModel->isFieldUnique(['perm_code' => $request->perm_code], $id)
                && paramError("权限编码 {$request->perm_code} 已存在");
        }

        $permission->update($request->toDbArrayFiltered());

        CacheKeys::flush(CacheKeys::PERM_OPTIONS, CacheKeys::PERM_TREE, CacheKeys::PERM_API, CacheKeys::PERM_CODES, CacheKeys::MENU_USER, CacheKeys::MENU_SUPER);

        return $permission->fresh()->toArray();
    }
}
