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

use App\Model\Role\SysRoleModel;

class GetInfo
{
    public function __construct(
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(int $id): array
    {
        $id > 0 || paramError('角色ID不能为空');

        $role = $this->roleModel
            ->with(['menus'])
            ->find($id);

        ! $role && paramError('角色不存在', 404);

        return $role->toArray();
    }
}
