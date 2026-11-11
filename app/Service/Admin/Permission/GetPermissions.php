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

namespace App\Service\Admin\Permission;

use App\Model\Admin\SysAdminModel;

class GetPermissions
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    public function handle(int $staffId): array
    {
        $admin = $this->adminModel->findOrNotFound($staffId, '管理员', ['roles']);

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
