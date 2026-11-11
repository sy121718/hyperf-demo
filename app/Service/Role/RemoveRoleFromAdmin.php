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
use App\Model\Rbac\SysRoleModel;

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

        $admin = $this->adminModel->findOrNotFound($adminId, '管理员');

        $role = $this->roleModel->findOrNotFound($roleId, '角色');

        $admin->roles()->detach($roleId);

        CacheKeys::flush(CacheKeys::PERM_CODES, CacheKeys::MENU_USER);

        return ['admin_id' => $adminId, 'role_id' => $roleId];
    }
}
