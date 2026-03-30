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
 * 单表查询条件解析器
 * 返回格式：[['field', 'operator', 'value'], ...].
 */
class WhereConditionParser
{
    public static function parse(array $data, array $whereableFields): array
    {
        $conditions = [];

        foreach ($whereableFields as $paramKey => $config) {
            if (is_int($paramKey)) {
                $fieldName = $config;
                $operator = '=';
                $paramKey = $config;
            } else {
                $fieldName = $config['field'] ?? $paramKey;
                $operator = $config['operator'] ?? '=';
            }

            if (! isset($data[$paramKey]) || $data[$paramKey] === '' || $data[$paramKey] === null) {
                continue;
            }

            $conditions[] = [$fieldName, $operator, $data[$paramKey]];
        }

        return $conditions;
    }
}
