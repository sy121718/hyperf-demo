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

class TreeBuilder
{
    public static function build(array $items, ?int $parentId = null, string $parentColumn = 'parent_id'): array
    {
        if (empty($items)) {
            return [];
        }

        if ($parentId === null) {
            $allIds = array_column($items, 'id');
            $rootParentIds = [];
            foreach ($items as $item) {
                $itemParentId = $item[$parentColumn] ?? 0;
                if ($itemParentId == 0 || ! in_array($itemParentId, $allIds)) {
                    $rootParentIds[] = $itemParentId;
                }
            }
            $parentId = ! empty($rootParentIds) ? min($rootParentIds) : 0;
        }

        $tree = [];
        foreach ($items as $item) {
            if ($item[$parentColumn] == $parentId) {
                $children = static::build($items, $item['id'], $parentColumn);
                if (! empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }

        return $tree;
    }
}
