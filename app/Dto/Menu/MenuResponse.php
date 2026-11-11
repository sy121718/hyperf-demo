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

use App\Dto\BaseResponse;

/**
 * 菜单模块响应字段白名单.
 *
 * 库里存的是逗号分隔的 permission_code，对外统一暴露成 permission_codes 数组。
 */
class MenuResponse extends BaseResponse
{
    public static function detail(mixed $row): array
    {
        return parent::detail(static::withPermissionCodes(static::normalize($row)));
    }

    public static function collection(array $rows): array
    {
        return parent::collection(
            array_map(static fn ($row) => static::withPermissionCodes(static::normalize($row)), $rows)
        );
    }

    protected static function listFields(): array
    {
        return [
            'id', 'menu_code', 'menu_name', 'parent_id', 'type', 'title',
            'path', 'component', 'external_url', 'icon', 'status',
            'is_hidden', 'is_public', 'permission_codes', 'sort_order', 'children',
        ];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'menu_code', 'menu_name', 'parent_id', 'type', 'title',
            'path', 'component', 'external_url', 'icon', 'status',
            'is_hidden', 'is_public', 'permission_codes', 'sort_order',
            'create_time', 'update_time',
        ];
    }

    protected static function savedFields(): array
    {
        return ['id', 'menu_code', 'menu_name'];
    }

    /**
     * 把 permission_code 拆成 permission_codes 数组.
     */
    protected static function withPermissionCodes(array $row): array
    {
        $raw = $row['permission_code'] ?? null;
        $row['permission_codes'] = empty($raw) ? [] : explode(',', (string) $raw);

        // 子节点同样带上 permission_codes
        if (! empty($row['children']) && is_array($row['children'])) {
            $row['children'] = array_map(
                static fn ($child) => static::withPermissionCodes(static::normalize($child)),
                $row['children']
            );
        }

        return $row;
    }
}
