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

namespace App\Dto\Admin;

use App\Dto\BaseResponse;

/**
 * 管理员模块响应字段白名单.
 */
class AdminResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return [
            'id', 'username', 'name', 'email', 'phone', 'avatar', 'status',
            'is_admin', 'last_login_ip', 'last_login_time', 'create_time',
        ];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'username', 'admin_code', 'name', 'email', 'phone', 'avatar',
            'status', 'is_admin', 'last_login_ip', 'last_login_location', 'last_login_isp',
            'last_login_time', 'locked_until_time', 'create_time', 'update_time', 'roles',
        ];
    }

    protected static function savedFields(): array
    {
        return ['id', 'username', 'admin_code'];
    }
}
