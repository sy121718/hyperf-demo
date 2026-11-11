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

namespace App\Dto\Auth;

use App\Dto\BaseRequestDto;

/**
 * 登录请求参数.
 */
class LoginRequest extends BaseRequestDto
{
    public ?string $username = null;

    public ?string $password = null;

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:50',
            'password' => 'required|string|min:6|max:100',
        ];
    }
}
