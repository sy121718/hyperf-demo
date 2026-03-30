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

namespace App\Service\IpBlacklist;

use App\Common\CacheKeys;
use App\Model\IpBlacklist\IpBlacklistModel;
use Psr\SimpleCache\CacheInterface;

class CheckIp
{
    public function __construct(
        protected IpBlacklistModel $model,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(string $ip): ?array
    {
        $cacheKey = CacheKeys::ipBlacklist($ip);
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached ?: null;
        }

        $ban = $this->model->activeForIp($ip)->first();
        $result = $ban ? $ban->toArray() : [];

        $this->cache->set($cacheKey, $result, CacheKeys::TTL_SHORT);

        return $result ?: null;
    }

    public static function isBanned(string $ip): bool
    {
        $cache = container()->get(CacheInterface::class);
        $cacheKey = CacheKeys::ipBlacklist($ip);
        $cached = $cache->get($cacheKey);

        if ($cached !== null) {
            return ! empty($cached);
        }

        $banned = container()->get(IpBlacklistModel::class)->activeForIp($ip)->exists();
        $cache->set($cacheKey, $banned ? ['banned' => true] : [], CacheKeys::TTL_SHORT);

        return $banned;
    }
}
