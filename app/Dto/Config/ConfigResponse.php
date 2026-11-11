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

namespace App\Dto\Config;

use App\Dto\BaseResponse;

/**
 * 系统配置模块响应字段白名单.
 */
class ConfigResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return ['id', 'group_key', 'group_name', 'config_data', 'remark', 'status', 'create_time'];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'group_key', 'group_name', 'config_data', 'remark', 'status',
            'create_time', 'update_time',
        ];
    }

    protected static function savedFields(): array
    {
        return ['id', 'group_key', 'group_name'];
    }
}
