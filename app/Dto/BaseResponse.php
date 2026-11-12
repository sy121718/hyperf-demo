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

namespace App\Dto;

use Hyperf\Contract\LengthAwarePaginatorInterface;

/**
 * 响应基类 —— 统一分页与字段裁剪.
 *
 * 设计原则：只输出调用方真正需要的字段。
 * - 列表：total + list，list 项只取 $listFields 白名单
 * - 详情：$detailFields 白名单
 * - 写操作：只回 id 与关键标识
 */
abstract class BaseResponse
{
    /**
     * 分页列表：{ total, list }.
     */
    public static function page(LengthAwarePaginatorInterface $paginator): array
    {
        $list = [];
        $fields = static::listFields();

        foreach ($paginator->items() as $item) {
            $list[] = static::pick(static::normalize($item), $fields);
        }

        return [
            'total' => (int) $paginator->total(),
            'list' => $list,
        ];
    }

    /**
     * 非分页列表：按 listFields 白名单裁剪每一项.
     *
     * @param array<int, mixed> $rows
     */
    public static function collection(array $rows): array
    {
        $fields = static::listFields();

        return array_map(
            static fn ($row) => static::pick(static::normalize($row), $fields),
            $rows
        );
    }

    /**
     * 详情：按白名单裁剪.
     */
    public static function detail(mixed $row): array
    {
        return static::pick(static::normalize($row), static::detailFields());
    }

    /**
     * 写操作回执.
     */
    public static function saved(mixed $row): array
    {
        return static::pick(static::normalize($row), static::savedFields());
    }

    /**
     * 列表项输出字段，子类覆盖.
     * @return array<int, string>
     */
    protected static function listFields(): array
    {
        return [];
    }

    /**
     * 详情输出字段，子类覆盖.
     * @return array<int, string>
     */
    protected static function detailFields(): array
    {
        return [];
    }

    /**
     * 写操作回执字段，子类可覆盖.
     * @return array<int, string>
     */
    protected static function savedFields(): array
    {
        return ['id'];
    }

    /**
     * 统一转成数组.
     */
    protected static function normalize(mixed $row): array
    {
        if (is_array($row)) {
            return $row;
        }
        if (is_object($row) && method_exists($row, 'toArray')) {
            return $row->toArray();
        }

        return (array) $row;
    }

    /**
     * 按字段白名单取值，白名单为空时原样返回.
     *
     * 支持两种写法：
     *   'admin_id'          —— 取当前行同名字段
     *   'admin_name' => 'admin.name' —— 从预加载的关联对象里取，输出时改名为 admin_name
     *
     * 关联未预加载或值为 null 时不输出该键，避免前端拿到 null 覆盖默认文案。
     */
    protected static function pick(array $row, array $fields): array
    {
        if ($fields === []) {
            return $row;
        }

        $result = [];
        foreach ($fields as $key => $field) {
            // 数字键是普通字段名；字符串键是「输出名 => 数据路径」
            $outputName = is_int($key) ? $field : $key;
            $path = is_int($key) ? $field : $field;

            $value = self::valueAt($row, $path);
            if ($value !== null) {
                $result[$outputName] = $value;
            } elseif (is_int($key) && array_key_exists($field, $row)) {
                // 普通字段即使为 null 也原样输出，保持响应结构稳定
                $result[$outputName] = $row[$field];
            }
        }

        return $result;
    }

    /**
     * 按点号路径取值，如 'admin.name'.
     */
    private static function valueAt(array $row, string $path): mixed
    {
        if (! str_contains($path, '.')) {
            return $row[$path] ?? null;
        }

        $current = $row;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }
}
