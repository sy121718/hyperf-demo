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

use App\Model\Admin\SysAdminModel;
use App\Model\Admin\SysAdminRoleModel;
use App\Model\Rbac\SysRoleModel;
use Hyperf\DbConnection\Db;
use Throwable;

/**
 * 按管理员 ID 全量替换其角色关联.
 *
 * 注意：sys_admin_role 的关联键是 admin_code / role_code（不是 id），
 * 因此需要先把传入的 roleIds 解析成 role_code。
 */
class AssignRoles
{
    public function __construct(
        protected SysAdminRoleModel $adminRoleModel,
        protected SysAdminModel $adminModel,
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(int $adminId, array $roleIds): void
    {
        $admin = $this->adminModel->find($adminId);
        ! $admin && notFound('管理员');

        $adminCode = (string) $admin->admin_code;

        $roleCodes = [];
        if (! empty($roleIds)) {
            $roleCodes = $this->roleModel
                ->whereIn('id', $roleIds)
                ->pluck('role_code')
                ->toArray();
            count($roleCodes) === count($roleIds) || paramError('部分角色不存在');
        }

        $operatorId = 0;
        try {
            $operatorId = (int) currentUser('id');
        } catch (Throwable) {
            $operatorId = 0;
        }

        Db::transaction(function () use ($adminCode, $roleCodes, $operatorId) {
            $this->adminRoleModel->where('admin_code', $adminCode)->delete();

            if (! empty($roleCodes)) {
                $rows = array_map(fn (string $roleCode) => [
                    'admin_code' => $adminCode,
                    'role_code' => $roleCode,
                    'create_by' => $operatorId,
                ], $roleCodes);
                $this->adminRoleModel->insert($rows);
            }
        });
    }
}
