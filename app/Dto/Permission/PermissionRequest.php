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

use App\Dto\BaseRequestDto;

/**
 * 权限字典模块请求参数.
 *
 * 批量创建/更新以 items 数组提交，每项结构同单条创建。
 */
class PermissionRequest extends BaseRequestDto
{
    public ?int $id = null;

    public ?string $perm_code = null;

    public ?string $perm_name = null;

    public ?int $parent_id = null;

    public ?string $api_path = null;

    public ?string $http_method = null;

    public ?string $controller_method = null;

    public ?int $is_public = null;

    public ?int $rate_limit = null;

    public ?int $route_priority = null;

    public ?string $remark = null;

    public ?int $status = null;

    public ?array $items = null;

    public ?array $ids = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'create' => $this->itemRules(required: true),
            'update' => ['id' => 'required|integer|min:1', ...$this->itemRules(required: false)],
            'tree' => [
                ...$this->paginationRules(),
                'parent_id' => 'nullable|integer|min:0',
                'status' => 'nullable|in:0,1',
                'is_public' => 'nullable|in:0,1',
            ],
            'batchCreate' => [
                'items' => 'required|array|min:1',
            ],
            'batchUpdate' => [
                'items' => 'required|array|min:1',
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
        return ['id', 'items', 'ids'];
    }

    /**
     * 单条权限的字段规则，创建与更新共用.
     */
    private function itemRules(bool $required): array
    {
        return [
            'perm_code' => $required ? 'required|string|max:100' : 'nullable|string|max:100',
            'perm_name' => $required ? 'required|string|max:100' : 'nullable|string|max:100',
            'parent_id' => 'nullable|integer|min:0',
            'api_path' => 'nullable|string|max:255',
            'http_method' => 'nullable|string|max:10',
            'controller_method' => 'nullable|string|max:255',
            'is_public' => 'nullable|in:0,1',
            'rate_limit' => 'nullable|integer|min:0',
            'route_priority' => 'nullable|integer|min:0',
            'remark' => 'nullable|string|max:500',
            'status' => 'nullable|in:0,1',
        ];
    }
}
