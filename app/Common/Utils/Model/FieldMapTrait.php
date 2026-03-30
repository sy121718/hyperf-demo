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

namespace App\Common\Utils\Model;

/**
 * 字段内容映射 Trait
 * 将数据库中的数字值转换为友好的文字描述.
 */
trait FieldMapTrait
{
    /** @var array<string, array<int|string, string>> */
    protected array $fieldMaps = [];

    public function getFieldMaps(): array
    {
        return $this->fieldMaps;
    }

    public function getFieldLabel(string $field, mixed $value): string
    {
        $maps = $this->getFieldMaps();

        if (! isset($maps[$field])) {
            return (string) $value;
        }

        return $maps[$field][$value] ?? (string) $value;
    }

    public function getFieldOptions(string $field): array
    {
        return $this->getFieldMaps()[$field] ?? [];
    }

    public function addFieldLabels(array $data, array $fields): array
    {
        foreach ($fields as $field => $labelField) {
            if (isset($data[$field])) {
                $data[$labelField] = $this->getFieldLabel($field, $data[$field]);
            }
        }
        return $data;
    }

    public function addDefaultFieldLabels(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field . '_text'] = $this->getFieldLabel($field, $data[$field]);
            }
        }
        return $data;
    }
}
