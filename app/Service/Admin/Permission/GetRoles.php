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

class GetRoles
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    public function handle(int $id): array
    {
        $admin = $this->adminModel->findOrNotFound($id, '管理员');

        $roles = $admin->roles()->get()->toArray();

        return [
            'admin_id' => $id,
            'roles' => $roles,
        ];
    }
}
