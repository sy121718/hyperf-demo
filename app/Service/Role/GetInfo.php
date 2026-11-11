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

use App\Model\Rbac\SysRoleModel;

class GetInfo
{
    public function __construct(
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(int $id): array
    {
        requireId($id, '角色');

        $role = $this->roleModel->find($id);

        ! $role && notFound('角色');

        return $role->toArray();
    }
}
