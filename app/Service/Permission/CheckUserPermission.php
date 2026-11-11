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

use App\Model\Admin\SysAdminRoleModel;
use App\Model\Rbac\SysPermissionDictModel;
use App\Model\Rbac\SysRoleModel;

class CheckUserPermission
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
        protected SysAdminRoleModel $adminRoleModel,
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(int $userId, string $apiPath, string $httpMethod): bool
    {
        $permission = $this->findPermission($apiPath, $httpMethod);

        if (! $permission) {
            return true;
        }

        if ($permission->status !== 1) {
            return false;
        }

        if ($permission->is_public === 1) {
            return true;
        }

        $permissionCodes = $this->getUserPermissionCodes($userId);

        if (empty($permissionCodes)) {
            return false;
        }

        return in_array($permission->perm_code, $permissionCodes);
    }

    protected function getUserPermissionCodes(int $userId): array
    {
        $roleCodes = $this->adminRoleModel
            ->where('admin_code', function ($q) use ($userId) {
                $q->select('admin_code')
                    ->from('sys_admin')
                    ->where('id', $userId);
            })
            ->pluck('role_code')
            ->toArray();

        if (empty($roleCodes)) {
            return [];
        }

        $allCodes = [];
        $roles = $this->roleModel
            ->whereIn('role_code', $roleCodes)
            ->where('status', 1)
            ->get();

        foreach ($roles as $role) {
            $codes = $role->permission_codes ?? [];
            $allCodes = array_merge($allCodes, $codes);
        }

        return array_unique($allCodes);
    }

    protected function findPermission(string $apiPath, string $httpMethod): ?object
    {
        $httpMethod = strtoupper($httpMethod);

        $permission = $this->permissionModel
            ->where('api_path', $apiPath)
            ->where('http_method', $httpMethod)
            ->first();

        if ($permission) {
            return $permission;
        }

        $dynamicPermissions = $this->permissionModel
            ->where('api_path', 'like', '%{%')
            ->where('http_method', $httpMethod)
            ->get();

        foreach ($dynamicPermissions as $perm) {
            $pattern = preg_replace('/\{[^}]+\}/', '[^/]+', $perm->api_path);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $apiPath)) {
                return $perm;
            }
        }

        return null;
    }
}
