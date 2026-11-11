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
     */
    protected static function pick(array $row, array $fields): array
    {
        if ($fields === []) {
            return $row;
        }

        $result = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $row)) {
                $result[$field] = $row[$field];
            }
        }

        return $result;
    }
}
