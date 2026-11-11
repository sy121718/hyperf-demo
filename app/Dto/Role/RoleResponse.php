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

namespace App\Dto\Role;

use App\Dto\BaseResponse;

/**
 * 角色模块响应字段白名单.
 */
class RoleResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return ['id', 'role_code', 'role_name', 'status', 'sort_order', 'remark', 'create_time'];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'role_code', 'role_name', 'role_type', 'sort_order',
            'status', 'remark', 'permission_codes', 'create_time', 'update_time',
        ];
    }

    protected static function savedFields(): array
    {
        return ['id', 'role_code', 'role_name'];
    }
}
