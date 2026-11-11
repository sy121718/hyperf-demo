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

namespace App\Service\Role;

use App\Dto\Role\RoleRequest;
use App\Model\Rbac\SysRoleModel;

class Update
{
    public function __construct(
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(RoleRequest $request): array
    {
        $id = (int) $request->id;
        $role = $this->roleModel->find($id);
        ! $role && notFound('角色');

        if ($request->role_code && $request->role_code !== $role->role_code) {
            ! $this->roleModel->isFieldUnique(['role_code' => $request->role_code], $id)
                && paramError("角色标识 {$request->role_code} 已存在");
        }

        $updateData = $request->toDbArrayFiltered();

        if (isset($updateData['permission_codes'])) {
            $updateData['permission_codes'] = $updateData['permission_codes'] ?: [];
        }

        $role->update($updateData);

        return $role->fresh()->toArray();
    }
}
