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

namespace App\Dto\Permission;

use App\Dto\BaseResponse;

/**
 * 权限字典模块响应字段白名单.
 */
class PermissionResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return [
            'id', 'perm_name', 'perm_code', 'parent_id', 'api_path',
            'http_method', 'is_public', 'route_priority', 'status', 'children',
        ];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'perm_name', 'perm_code', 'parent_id', 'api_path', 'http_method',
            'controller_method', 'is_public', 'rate_limit', 'route_priority',
            'remark', 'status', 'create_time', 'update_time',
        ];
    }

    protected static function savedFields(): array
    {
        return ['id', 'perm_code', 'perm_name'];
    }
}
