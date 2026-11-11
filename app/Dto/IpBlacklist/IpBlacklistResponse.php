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

namespace App\Dto\IpBlacklist;

use App\Dto\BaseResponse;

/**
 * IP 黑名单模块响应字段白名单.
 */
class IpBlacklistResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return [
            'id', 'ip_address', 'error_type', 'ban_reason', 'ban_duration',
            'banned_time', 'banned_until_time', 'ban_count', 'status', 'create_time',
        ];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'ip_address', 'error_type', 'ban_reason', 'ban_duration',
            'banned_time', 'banned_until_time', 'ban_count', 'operator_id',
            'remark', 'status', 'create_time', 'update_time',
        ];
    }

    protected static function savedFields(): array
    {
        return ['id', 'ip_address', 'status'];
    }
}
