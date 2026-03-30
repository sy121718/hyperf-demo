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
use App\Service\Role\Dto\UpdateRoleDto;

class Update
{
    public function __construct(
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(UpdateRoleDto $dto): array
    {
        $role = $this->roleModel->find($dto->id);
        ! $role && paramError('角色不存在', 404);

        if ($dto->roleCode && $dto->roleCode !== $role->role_code) {
            ! $this->roleModel->isFieldUnique(['role_code' => $dto->roleCode], $dto->id)
                && paramError("角色标识 {$dto->roleCode} 已存在");
        }

        $updateData = $dto->toDbArrayFiltered();
        unset($updateData['id']);

        if (isset($updateData['permission_codes'])) {
            $updateData['permission_codes'] = json_encode($updateData['permission_codes'] ?: []);
        }

        $role->update($updateData);

        return UpdateRoleDto::dbToFront($role->fresh()->toArray());
    }
}
