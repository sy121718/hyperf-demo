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

use App\Model\Permission\SysPermissionDictModel;

class GetInfo
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    public function handle(int $id): array
    {
        $id > 0 || paramError('权限ID不能为空');

        $permission = $this->permissionModel->find($id);
        ! $permission && paramError('权限不存在', 404);

        return $permission->toArray();
    }
}
