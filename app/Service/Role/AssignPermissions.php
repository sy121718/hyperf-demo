<?php

declare(strict_types=1);

namespace App\Service\Role;

use App\Common\CacheKeys;
use App\Model\Permission\SysPermissionDictModel;
use App\Model\Role\SysRoleModel;

class AssignPermissions
{
    public function __construct(
        protected SysRoleModel $roleModel,
        protected SysPermissionDictModel $permissionModel,
    ) {}

    public function handle(string $roleCode, array $permissionCodes): array
    {
        !empty($roleCode) || paramError('角色标识不能为空');

        $role = $this->roleModel->where('role_code', $roleCode)->first();
        !$role && paramError('角色不存在', 404);

        if (!empty($permissionCodes)) {
            $exists = $this->permissionModel
                ->whereIn('perm_code', $permissionCodes)
                ->count();
            $exists === count($permissionCodes) || paramError('部分权限不存在');
        }

        $role->update(['permission_codes' => json_encode($permissionCodes ?: [])]);

        CacheKeys::flush(
            CacheKeys::ROLE_OPTIONS,
            CacheKeys::ROLE_ALL,
            CacheKeys::PERM_USER,
            CacheKeys::MENU_USER,
            CacheKeys::MENU_SUPER
        );

        return ['role_code' => $roleCode, 'permission_codes' => $permissionCodes];
    }
}
