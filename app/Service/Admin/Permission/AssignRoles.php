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

use App\Common\CacheKeys;
use App\Dto\Admin\AdminRequest;
use App\Model\Admin\SysAdminModel;
use App\Model\Admin\SysAdminRoleModel;
use App\Model\Rbac\SysRoleModel;
use Hyperf\DbConnection\Db;

class AssignRoles
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected SysAdminRoleModel $adminRoleModel,
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(AdminRequest $request): array
    {
        $isSuperAdmin = currentUser('is_admin') == 1;
        if (! $isSuperAdmin) {
            paramError('只有超级管理员可以分配角色', 403);
        }

        $adminCode = (string) $request->admin_code;
        $roleCodes = $request->role_codes ?? [];

        ! empty($adminCode) || paramError('管理员标识不能为空');
        ! empty($roleCodes) || paramError('角色标识不能为空');

        $admin = $this->adminModel->firstOrNotFound(['admin_code' => $adminCode], '管理员');

        if ($admin->is_admin == 1) {
            paramError('不能修改超级管理员的角色', 403);
        }

        $roles = $this->roleModel->whereIn('role_code', $roleCodes)->get();
        $roles->count() === count($roleCodes) || paramError('部分角色不存在');

        // 删除旧关联、创建新关联必须在同一事务里，否则中途失败会留下「角色被清空」的中间态
        $operatorId = (int) currentUser('id');
        Db::transaction(function () use ($adminCode, $roleCodes, $operatorId) {
            $this->adminRoleModel->where('admin_code', $adminCode)->delete();

            $rows = array_map(static fn (string $roleCode) => [
                'admin_code' => $adminCode,
                'role_code' => $roleCode,
                'create_by' => $operatorId,
            ], $roleCodes);
            $this->adminRoleModel->insert($rows);
        });

        CacheKeys::flushPermissionRelated();

        return ['admin_code' => $adminCode, 'role_codes' => $roleCodes];
    }
}
