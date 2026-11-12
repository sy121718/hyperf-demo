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

use App\Common\CacheKeys;
use App\Dto\Role\RoleRequest;
use App\Model\Admin\SysAdminModel;
use App\Model\Rbac\SysRoleModel;

class RemoveRoleFromAdmin
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(RoleRequest $request): array
    {
        $adminId = (int) $request->admin_id;
        $roleId = (int) $request->role_id;

        $admin = $this->adminModel->findOrNotFound($adminId, '管理员');

        $role = $this->roleModel->findOrNotFound($roleId, '角色');

        // 关联键是 role_code 不是自增 id：roles() 的 belongsToMany 以
        // sys_admin_role.role_code ↔ sys_role.role_code 连接，detach() 按 role_code 列
        // 匹配。传 id 会 detach 不掉任何记录，接口却返回成功，角色实际还在。
        $admin->roles()->detach($role->role_code);

        CacheKeys::flushPermissionRelated();

        return ['admin_id' => $adminId, 'role_id' => $roleId];
    }
}
