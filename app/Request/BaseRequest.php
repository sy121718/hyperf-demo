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

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

/**
 * Request 基类 - 提供 DTO 字段对接能力.
 *
 * validated() 自动将前端字段名转换为数据库字段名
 */
abstract class BaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 重写 validated()，自动通过 DTO 转换字段名.
     */
    public function validated(): array
    {
        $data = parent::validated();

        $dtoClass = $this->getDtoClass();
        if (! $dtoClass || ! class_exists($dtoClass)) {
            return $data;
        }

        return $this->convertFields($data);
    }

    /**
     * 获取验证后的单个字段.
     */
    public function validatedField(string $key, mixed $default = null): mixed
    {
        $data = $this->validated();
        return $data[$key] ?? $default;
    }

    /**
     * 获取关联的 DTO 类名，子类重写.
     */
    protected function getDtoClass(): ?string
    {
        return null;
    }

    protected function convertFields(array $data): array
    {
        $dtoClass = $this->getDtoClass();
        $fieldMap = $dtoClass::getFieldMap();
        $result = [];

        $systemKeys = ['search', 'page', 'limit'];

        foreach ($data as $key => $value) {
            if (in_array($key, $systemKeys, true)) {
                $result[$key] = $value;
                continue;
            }

            if ($key === 'sort' && is_array($value)) {
                $result['sort'] = $this->convertSortFields($value, $fieldMap);
                continue;
            }

            $dbField = $fieldMap[$key] ?? $key;
            $result[$dbField] = $value;
        }

        return $result;
    }

    protected function convertSortFields(array $sort, array $fieldMap): array
    {
        if (! isset($sort['field'])) {
            return $sort;
        }
        return [
            'field' => $fieldMap[$sort['field']] ?? $sort['field'],
            'direction' => $sort['direction'] ?? 'asc',
        ];
    }

    protected function getPaginationRules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ];
    }

    protected function getSearchRules(): array
    {
        return [
            'search' => 'nullable|string|max:100',
        ];
    }

    protected function getStatusRules(): array
    {
        return [
            'status' => 'nullable|in:0,1',
        ];
    }
}
