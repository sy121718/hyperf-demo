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

use App\Dto\Role\RoleRequest;
use App\Model\Rbac\SysRoleModel;

class Create
{
    public function __construct(
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(RoleRequest $request): array
    {
        $roleCode = (string) $request->role_code;

        ! $this->roleModel->isFieldUnique(['role_code' => $roleCode])
            && paramError("角色标识 {$roleCode} 已存在");

        // 过滤 null：未提交的字段交给数据库默认值，避免显式写入 NULL 覆盖默认值
        $dbData = $request->toDbArrayFiltered();
        $dbData['create_by'] = (int) currentUser('id');
        // 模型已配 'array' cast，直接传数组，避免双重编码
        $dbData['permission_codes'] = $request->permission_codes ?: [];

        $role = $this->roleModel->create($dbData);

        return $role->toArray();
    }
}
