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

namespace App\Service\Admin\Manage\Dto;

use App\Common\Dto\BaseDto;

class CreateAdminDto extends BaseDto
{
    protected static array $fields = [
        'username' => 'username',
        'password' => 'password',
        'name' => 'name',
        'email' => 'email',
        'mobile' => 'phone',
        'avatar' => 'avatar',
        'role_ids' => 'role_ids',
        'status' => 'status',
    ];

    public function __construct(
        public readonly string $username,
        public readonly string $password,
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $mobile = null,
        public readonly ?string $avatar = null,
        public readonly array $roleIds = [],
        public readonly int $status = 1,
    ) {
    }

    public function hasRoles(): bool
    {
        return ! empty($this->roleIds);
    }
}
