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
use App\Service\Role\Dto\CreateRoleDto;

class Create
{
    public function __construct(
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(CreateRoleDto $dto): array
    {
        ! $this->roleModel->isFieldUnique(['role_code' => $dto->roleCode])
            && paramError("角色标识 {$dto->roleCode} 已存在");

        $dbData = $dto->toDbArray();
        unset($dbData['id'], $dbData['permission_codes']);
        $dbData['create_by'] = (int) currentUser('id');
        $dbData['permission_codes'] = json_encode($dto->permissionCodes ?: []);

        $role = $this->roleModel->create($dbData);

        return CreateRoleDto::dbToFront($role->toArray());
    }
}
