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
use App\Dto\Dict\DictRequest;
use App\Model\System\SysDictModel;
use Psr\SimpleCache\CacheInterface;

class GetTreeList
{
    public function __construct(
        protected SysDictModel $dictModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(DictRequest $request): array
    {
        // 带筛选条件时不走缓存，否则会把裁剪后的结果写回全量缓存
        $hasFilter = $request->search !== null || $request->dict_type !== null || $request->status !== null;

        if (! $hasFilter) {
            $cached = $this->cache->get(CacheKeys::DICT_TREE);
            if ($cached !== null) {
                return $cached;
            }
        }

        $dicts = $this->dictModel
            ->filter($request->toQueryArray())
            ->when($request->search, fn ($q) => $q->whereAny(
                $this->dictModel->getSearchableFields(),
                'like',
                "%{$request->search}%"
            ))
            ->sort($request->toQueryArray())
            ->with(['items' => fn ($q) => $q->where('status', 1)])
            ->get();

        $result = $dicts->toArray();

        if (! $hasFilter) {
            $this->cache->set(CacheKeys::DICT_TREE, $result, CacheKeys::TTL_MEDIUM);
        }

        return $result;
    }
}
