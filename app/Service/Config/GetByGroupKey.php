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

namespace App\Service\Config;

use App\Common\CacheKeys;
use App\Model\System\SysConfigModel;
use Psr\SimpleCache\CacheInterface;

class GetByGroupKey
{
    public function __construct(
        protected SysConfigModel $configModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(string $groupKey): ?array
    {
        $cacheKey = CacheKeys::config($groupKey);
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached ?: null;
        }

        $result = $this->configModel
            ->where('group_key', $groupKey)
            ->where('status', 1)
            ->first();

        if (! $result) {
            $this->cache->set($cacheKey, [], CacheKeys::TTL_LONG);
            return null;
        }

        $data = $result->toArray();
        $this->cache->set($cacheKey, $data, CacheKeys::TTL_LONG);

        return $data;
    }
}
