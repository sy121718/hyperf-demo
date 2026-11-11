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

namespace App\Dto\Menu;

use App\Dto\BaseRequestDto;

/**
 * 菜单模块请求参数.
 *
 * permission_codes 在接口层是数组，落库时由 Service 转成逗号分隔的 permission_code。
 */
class MenuRequest extends BaseRequestDto
{
    public ?int $id = null;

    public ?string $menu_code = null;

    public ?string $menu_name = null;

    public ?int $parent_id = null;

    public ?int $type = null;

    public ?string $title = null;

    public ?string $path = null;

    public ?string $component = null;

    public ?string $external_url = null;

    public ?string $icon = null;

    public ?int $status = null;

    public ?int $is_hidden = null;

    public ?int $is_public = null;

    public ?int $sort_order = null;

    public ?array $permission_codes = null;

    /**
     * 批量删除的菜单 ID 集合.
     */
    public ?array $ids = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'create' => [
                'menu_code' => 'required|string|max:50',
                'menu_name' => 'required|string|max:50',
                'parent_id' => 'nullable|integer|min:0',
                'type' => 'nullable|integer|in:0,1,2',
                'title' => 'nullable|string|max:50',
                'path' => 'nullable|string|max:255',
                'component' => 'nullable|string|max:255',
                'external_url' => 'nullable|string|max:300',
                'icon' => 'nullable|string|max:50',
                'status' => 'nullable|in:0,1',
                'is_hidden' => 'nullable|in:0,1',
                'is_public' => 'nullable|in:0,1',
                'sort_order' => 'nullable|integer|min:0',
                'permission_codes' => 'nullable|array',
                'permission_codes.*' => 'string|max:100',
            ],
            'update' => [
                'menu_code' => 'nullable|string|max:50',
                'menu_name' => 'nullable|string|max:50',
                'parent_id' => 'nullable|integer|min:0',
                'type' => 'nullable|integer|in:0,1,2',
                'title' => 'nullable|string|max:50',
                'path' => 'nullable|string|max:255',
                'component' => 'nullable|string|max:255',
                'external_url' => 'nullable|string|max:300',
                'icon' => 'nullable|string|max:50',
                'status' => 'nullable|in:0,1',
                'is_hidden' => 'nullable|in:0,1',
                'is_public' => 'nullable|in:0,1',
                'sort_order' => 'nullable|integer|min:0',
                'permission_codes' => 'nullable|array',
                'permission_codes.*' => 'string|max:100',
            ],
            'tree' => [
                ...$this->paginationRules(),
                'parent_id' => 'nullable|integer|min:0',
                'type' => 'nullable|integer|in:0,1,2',
                'status' => 'nullable|in:0,1',
                'is_hidden' => 'nullable|in:0,1',
                'is_public' => 'nullable|in:0,1',
            ],
            'batchDelete' => [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|min:1',
            ],
            default => [],
        };
    }

    protected function virtualFields(): array
    {
        // permission_codes 由 Action 拼成 permission_code 落库，表里没有这一列
        return ['id', 'ids', 'permission_codes'];
    }
}
