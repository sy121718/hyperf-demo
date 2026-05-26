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
use App\Model\Admin\SysAdminModel;
use App\Model\Role\SysRoleModel;

class RemoveRoleFromAdmin
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(array $data): array
    {
        $adminId = (int) ($data['admin_id'] ?? 0);
        $roleId = (int) ($data['role_id'] ?? 0);

        $adminId > 0 || paramError('管理员ID不能为空');
        $roleId > 0 || paramError('角色ID不能为空');

        $admin = $this->adminModel->find($adminId);
        ! $admin && paramError('管理员不存在', 404);

        $role = $this->roleModel->find($roleId);
        ! $role && paramError('角色不存在', 404);

        $admin->roles()->detach($roleId);

        CacheKeys::flush(CacheKeys::PERM_CODES, CacheKeys::MENU_USER);

        return ['admin_id' => $adminId, 'role_id' => $roleId];
    }
}
