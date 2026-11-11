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

namespace App\Dto\Log;

use App\Dto\BaseResponse;

/**
 * 日志模块响应字段白名单.
 *
 * 登录日志与操作日志字段不同，两张表各自取交集，缺失的键会被自动跳过。
 */
class LogResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return [
            // 登录日志
            'id', 'user_id', 'admin_id', 'username', 'status', 'ip_address', 'ip',
            'location', 'user_agent', 'device_type', 'login_time',
            // 操作日志
            'menu_id', 'operation', 'service_method', 'api_path', 'http_method',
            'result', 'execution_time', 'create_time',
        ];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'user_id', 'admin_id', 'username', 'status', 'ip_address', 'ip',
            'location', 'user_agent', 'device_type', 'login_time', 'msg',
            'menu_id', 'operation', 'service_method', 'api_path', 'http_method',
            'request_data', 'response_data', 'result', 'content',
            'execution_time', 'create_time',
        ];
    }
}
