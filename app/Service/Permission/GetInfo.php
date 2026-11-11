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

use App\Model\Rbac\SysPermissionDictModel;

class GetInfo
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
    ) {
    }

    public function handle(int $id): array
    {
        requireId($id, '权限');

        $permission = $this->permissionModel->find($id);
        ! $permission && notFound('权限');

        return $permission->toArray();
    }
}
