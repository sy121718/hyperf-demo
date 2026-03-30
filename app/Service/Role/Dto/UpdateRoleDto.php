<?php

declare(strict_types=1);

namespace App\Service\Role\Dto;

use App\Common\Dto\BaseDto;

class UpdateRoleDto extends BaseDto
{
    protected static array $fields = [
        'id' => 'id',
        'role_code' => 'role_code',
        'role_name' => 'role_name',
        'role_type' => 'role_type',
        'sort_order' => 'sort_order',
        'status' => 'status',
        'remark' => 'remark',
        'permission_codes' => 'permission_codes',
    ];

    public function __construct(
        public readonly int $id,
        public readonly ?string $roleCode = null,
        public readonly ?string $roleName = null,
        public readonly ?string $roleType = null,
        public readonly ?int $sortOrder = null,
        public readonly ?int $status = null,
        public readonly ?string $remark = null,
        public readonly array $permissionCodes = [],
    ) {}
}
