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

namespace App\Common\Utils\Validation;

class FieldUniquenessChecker
{
    /**
     * @param object $model 模型实例
     * @param array $conditions ['field' => 'value', ...]
     * @param null|int $excludeId 排除的记录ID（更新时校验）
     * @return bool true=唯一，false=存在重复
     */
    public static function isUnique(object $model, array $conditions, ?int $excludeId = null): bool
    {
        if (empty($conditions)) {
            return true;
        }

        $query = $model::query();
        $query->where($conditions);

        if ($excludeId !== null) {
            $query->where($model->getKeyName(), '<>', $excludeId);
        }

        return ! $query->exists();
    }
}
