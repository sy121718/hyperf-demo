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

namespace App\Service\Role\Dto;

use App\Common\Dto\BaseDto;

class CreateRoleDto extends BaseDto
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
        public readonly string $roleCode,
        public readonly string $roleName,
        public readonly string $roleType = '1',
        public readonly ?int $sortOrder = null,
        public readonly int $status = 1,
        public readonly ?string $remark = null,
        public readonly array $permissionCodes = [],
    ) {
    }
}
