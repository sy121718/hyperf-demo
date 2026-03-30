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

namespace App\Common\Utils\Model\QueryBuilder;

/**
 * 单表排序条件解析器
 * 格式：sort[field]=create_time&sort[direction]=desc.
 */
class OrderConditionParser
{
    public static function parse(array $data, array $orderableFields): array
    {
        $sort = $data['sort'] ?? null;

        if (! is_array($sort) || empty($sort) || empty($orderableFields)) {
            return [];
        }

        $field = $sort['field'] ?? null;
        $direction = $sort['direction'] ?? 'asc';

        if (! $field || ! in_array($field, $orderableFields)) {
            return [];
        }

        $direction = strtolower($direction);
        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        return ['field' => $field, 'direction' => $direction];
    }
}
