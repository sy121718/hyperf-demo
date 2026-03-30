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

namespace App\Common\Dto;

/**
 * DTO 基类 - 严格模式字段映射.
 *
 * 所有 DTO 必须定义 $fields 映射：前端字段名 => 数据库字段名
 */
abstract class BaseDto
{
    /**
     * 字段映射表：前端字段名 => 数据库字段名.
     * @var array<string, string>
     */
    protected static array $fields = [];

    public static function fromArray(array $data): static
    {
        $dtoData = [];
        foreach (static::$fields as $frontField => $dbField) {
            if (array_key_exists($frontField, $data)) {
                $dtoProp = self::toCamel($frontField);
                $dtoData[$dtoProp] = $data[$frontField];
            }
        }
        return new static(...$dtoData);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function toFrontArray(): array
    {
        $dtoData = $this->toArray();
        $result = [];
        foreach (static::$fields as $frontField => $dbField) {
            $dtoProp = self::toCamel($frontField);
            if (array_key_exists($dtoProp, $dtoData)) {
                $result[$frontField] = $dtoData[$dtoProp];
            }
        }
        return $result;
    }

    public function toDbArray(): array
    {
        $dtoData = $this->toArray();
        $result = [];
        foreach (static::$fields as $frontField => $dbField) {
            $dtoProp = self::toCamel($frontField);
            if (array_key_exists($dtoProp, $dtoData)) {
                $result[$dbField] = $dtoData[$dtoProp];
            }
        }
        return $result;
    }

    public function toDbArrayFiltered(): array
    {
        return array_filter($this->toDbArray(), fn ($value) => $value !== null);
    }

    /**
     * 数据库字段 => 前端字段（静态方法，用于 Action 返回数据）.
     */
    public static function dbToFront(array $dbData): array
    {
        $result = [];
        foreach (static::$fields as $frontField => $dbField) {
            if (array_key_exists($dbField, $dbData)) {
                $result[$frontField] = $dbData[$dbField];
            }
        }
        return $result;
    }

    public static function getFrontFields(): array
    {
        return array_keys(static::$fields);
    }

    public static function getFieldMap(): array
    {
        return static::$fields;
    }

    public static function getDbField(string $frontField): ?string
    {
        return static::$fields[$frontField] ?? null;
    }

    public static function hasField(string $frontField): bool
    {
        return isset(static::$fields[$frontField]);
    }

    protected static function toCamel(string $str): string
    {
        if (! str_contains($str, '_')) {
            return $str;
        }
        return lcfirst(str_replace('_', '', ucwords($str, '_')));
    }
}
