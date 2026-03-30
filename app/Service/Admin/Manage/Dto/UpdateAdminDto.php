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

class UpdateAdminDto extends BaseDto
{
    protected static array $fields = [
        'id' => 'id',
        'username' => 'username',
        'password' => 'password',
        'name' => 'name',
        'email' => 'email',
        'mobile' => 'phone',
        'avatar' => 'avatar',
        'role_ids' => 'role_ids',
        'status' => 'status',
        'isAdmin' => 'is_admin',
    ];

    public function __construct(
        public readonly int $id,
        public readonly ?string $username = null,
        public readonly ?string $password = null,
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $mobile = null,
        public readonly ?string $avatar = null,
        public readonly ?array $roleIds = null,
        public readonly ?int $status = null,
        public readonly ?int $isAdmin = null,
    ) {
    }

    public function shouldUpdatePassword(): bool
    {
        return $this->password !== null && $this->password !== '';
    }

    public function shouldUpdateRoles(): bool
    {
        return $this->roleIds !== null;
    }
}
