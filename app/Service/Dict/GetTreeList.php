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

namespace App\Service\Dict;

use App\Common\CacheKeys;
use App\Model\Dict\SysDictModel;
use Psr\SimpleCache\CacheInterface;

class GetTreeList
{
    public function __construct(
        protected SysDictModel $dictModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(array $data): array
    {
        $search = $data['search'] ?? null;
        $dictType = $data['dict_type'] ?? null;
        $hasFilter = $search || $dictType !== null || ! empty($data['status']);

        if (! $hasFilter) {
            $cached = $this->cache->get(CacheKeys::DICT_TREE);
            if ($cached !== null) {
                return $cached;
            }
        }

        $dicts = $this->dictModel
            ->filter($data)
            ->when($search, fn ($q) => $q->whereAny($this->dictModel->getSearchableFields(), 'like', "%{$search}%"))
            ->when($dictType !== null, fn ($q) => $q->where('dict_type', $dictType))
            ->with(['items' => fn ($q) => $q->where('status', 1)->orderBy('sort_order')->orderBy('id')])
            ->sort($data)
            ->get();

        $result = $dicts->map(fn ($dict) => [
            'id' => $dict->id,
            'dict_name' => $dict->dict_name,
            'dict_code' => $dict->dict_code,
            'dict_type' => $dict->dict_type ?? 'default',
            'status' => $dict->status,
            'create_time' => $dict->create_time,
            'items' => $dict->items->map(fn ($item) => [
                'id' => $item->id,
                'item_label' => $item->item_label,
                'item_value' => $item->item_value,
                'sort_order' => $item->sort_order,
                'status' => $item->status,
            ])->toArray(),
        ])->toArray();

        if (! $hasFilter) {
            $this->cache->set(CacheKeys::DICT_TREE, $result, CacheKeys::TTL_MEDIUM);
        }

        return $result;
    }
}
