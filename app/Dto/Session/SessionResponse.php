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

namespace App\Dto\Session;

use App\Dto\BaseResponse;

/**
 * 在线会话模块响应字段白名单.
 *
 * jwt_token 属于凭据，一律不对外输出。
 */
class SessionResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return [
            'id', 'admin_id', 'device_type', 'login_ip', 'location', 'isp',
            'user_agent', 'login_time', 'last_active_time', 'status',
        ];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'admin_id', 'device_type', 'device_info', 'login_ip', 'location',
            'isp', 'user_agent', 'login_time', 'last_active_time', 'expires_time', 'status',
        ];
    }
}
