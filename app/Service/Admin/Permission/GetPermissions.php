<?php

declare(strict_types=1);

namespace App\Service\Admin\Permission;

use App\Model\Admin\SysAdminModel;

class GetPermissions
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {}

    public function handle(int $staffId): array
    {
        $admin = $this->adminModel->with(['roles'])->find($staffId);
        !$admin && paramError('管理员不存在');

        $permissions = [];
        $permissionCodes = [];

        foreach ($admin->roles as $role) {
            $permissions[] = [
                'role_code' => $role->role_code,
                'role_name' => $role->role_name,
                'permission_codes' => $role->permission_codes ?? [],
            ];
            $permissionCodes = array_merge($permissionCodes, $role->permission_codes ?? []);
        }

        $permissionCodes = array_unique($permissionCodes);

        return [
            'admin_id' => $staffId,
            'admin_code' => $admin->admin_code,
            'roles' => $permissions,
            'permission_codes' => array_values($permissionCodes),
        ];
    }
}
