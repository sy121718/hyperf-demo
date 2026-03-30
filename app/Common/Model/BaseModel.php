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

namespace App\Common\Model;

use App\Common\Utils\Model\FieldMapTrait;
use App\Common\Utils\Model\QueryBuilder\OrderConditionParser;
use App\Common\Utils\Model\QueryBuilder\WhereConditionParser;
use App\Common\Utils\Model\TreeBuilder;
use App\Common\Utils\Validation\FieldUniquenessChecker;
use Hyperf\DbConnection\Model\Model;
use Throwable;

abstract class BaseModel extends Model
{
    use FieldMapTrait;

    public const CREATED_AT = 'create_time';

    public const UPDATED_AT = 'update_time';

    public bool $timestamps = true;

    protected ?string $dateFormat = 'Y-m-d H:i:s.v';

    protected bool $autoFillCreate = true;

    protected bool $autoFillUpdate = true;

    /**
     * 可筛选字段白名单（支持多种操作符）
     * 简写：'status' => 等价于 ['field' => 'status', 'operator' => '=']
     * 完整：'create_time_min' => ['field' => 'create_time', 'operator' => '>='].
     */
    protected array $whereableFields = [];

    /** 可搜索字段白名单（模糊搜索 OR 逻辑） */
    protected array $searchableFields = [];

    /** 可排序字段白名单 */
    protected array $orderableFields = [];

    public function save(array $options = []): bool
    {
        $userId = null;
        $getUserId = function () use (&$userId): int {
            if ($userId === null) {
                try {
                    $userId = (int) currentUser('id');
                } catch (Throwable) {
                    $userId = 0;
                }
            }
            return $userId;
        };

        if (! $this->exists && $this->autoFillCreate) {
            if (in_array('create_by', $this->getFillable()) && ! $this->getAttribute('create_by')) {
                $this->setAttribute('create_by', $getUserId());
            }
            if (in_array('update_by', $this->getFillable()) && ! $this->getAttribute('update_by')) {
                $this->setAttribute('update_by', $getUserId());
            }
        } elseif ($this->exists && $this->autoFillUpdate) {
            if (in_array('update_by', $this->getFillable()) && ! $this->isDirty('update_by')) {
                $this->setAttribute('update_by', $getUserId());
            }
        }

        return parent::save($options);
    }

    /**
     * 筛选作用域（等值/范围查询 AND 逻辑）.
     * @param mixed $query
     */
    public function scopeFilter($query, array $data)
    {
        if (empty($this->whereableFields)) {
            return $query;
        }

        $conditions = WhereConditionParser::parse($data, $this->whereableFields);
        foreach ($conditions as [$field, $operator, $value]) {
            $query->where($field, $operator, $value);
        }

        return $query;
    }

    public function getSearchableFields(): array
    {
        return $this->searchableFields;
    }

    /**
     * 排序作用域
     * @param mixed $query
     */
    public function scopeSort($query, array $data)
    {
        if (empty($this->orderableFields)) {
            return $query;
        }

        $order = OrderConditionParser::parse($data, $this->orderableFields);
        if (! empty($order)) {
            $query->orderBy($order['field'], $order['direction']);
        }

        return $query;
    }

    /**
     * whereAny 等价实现（Hyperf ORM 不支持原生 whereAny）.
     * @param mixed $query
     */
    public function scopeWhereAny($query, array $fields, string $operator, mixed $value)
    {
        return $query->where(function ($q) use ($fields, $operator, $value) {
            foreach ($fields as $field) {
                $q->orWhere($field, $operator, $value);
            }
        });
    }

    public function isFieldUnique(array $conditions, ?int $excludeId = null): bool
    {
        return FieldUniquenessChecker::isUnique($this, $conditions, $excludeId);
    }

    public function buildTree(array $items, ?int $parentId = null, string $parentColumn = 'parent_id'): array
    {
        return TreeBuilder::build($items, $parentId, $parentColumn);
    }
}
