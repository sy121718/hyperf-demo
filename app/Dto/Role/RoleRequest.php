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

use App\Dto\BaseRequestDto;

/**
 * 角色模块请求参数.
 *
 * page/limit/search/sort 已由基类提供，这里只声明角色自己的字段。
 */
class RoleRequest extends BaseRequestDto
{
    public ?int $id = null;

    public ?string $role_code = null;

    public ?string $role_name = null;

    public ?string $role_type = null;

    public ?int $sort_order = null;

    public ?int $status = null;

    public ?string $remark = null;

    public ?array $permission_codes = null;

    public ?int $admin_id = null;

    public ?array $role_ids = null;

    public ?int $role_id = null;

    public ?array $menu_ids = null;

    /**
     * 批量删除的角色 ID 集合.
     */
    public ?array $ids = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'create' => [
                'role_code' => 'required|string|max:50',
                'role_name' => 'required|string|max:50',
                'role_type' => 'nullable|string|in:1,2,3',
                'sort_order' => 'nullable|integer|min:0',
                'status' => 'nullable|in:0,1',
                'remark' => 'nullable|string|max:200',
                'permission_codes' => 'nullable|array',
            ],
            'update' => [
                'id' => 'required|integer|min:1',
                'role_name' => 'nullable|string|max:50',
                'role_code' => 'nullable|string|max:50',
                'role_type' => 'nullable|string|in:1,2,3',
                'sort_order' => 'nullable|integer|min:0',
                'status' => 'nullable|in:0,1',
                'remark' => 'nullable|string|max:200',
            ],
            'list' => [
                ...$this->paginationRules(),
                'status' => 'nullable|in:0,1',
            ],
            'assignMenus' => [
                'role_code' => 'required|string|max:50',
                'menu_ids' => 'nullable|array',
                'menu_ids.*' => 'integer|min:1',
            ],
            'assignToAdmin' => [
                'admin_id' => 'required|integer|min:1',
                'role_ids' => 'required|array|min:1',
                'role_ids.*' => 'integer|min:1',
            ],
            'removeFromAdmin' => [
                'admin_id' => 'required|integer|min:1',
                'role_id' => 'required|integer|min:1',
            ],
            'batchDelete' => [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|min:1',
            ],
            default => [],
        };
    }

    /**
     * 只用于业务、不写 sys_role 表的属性（page/limit/search/sort 由基类另行处理）.
     */
    protected function virtualFields(): array
    {
        return ['id', 'admin_id', 'role_ids', 'role_id', 'menu_ids', 'ids'];
    }
}
