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
use App\Model\Dict\SysDictItemModel;
use Psr\SimpleCache\CacheInterface;

class GetItems
{
    public function __construct(
        protected SysDictItemModel $dictItemModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(int $dictId): array
    {
        $dictId > 0 || paramError('字典ID不能为空');

        $cacheKey = CacheKeys::dictItems($dictId);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->dictItemModel
            ->where('dict_id', $dictId)
            ->where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'item_label' => $item->item_label,
                'item_value' => $item->item_value,
                'sort_order' => $item->sort_order,
            ])
            ->toArray();

        $this->cache->set($cacheKey, $result, CacheKeys::TTL_MEDIUM);

        return $result;
    }
}
