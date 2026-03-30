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
use App\Model\Config\SysConfigModel;
use Psr\SimpleCache\CacheInterface;

class Get
{
    public function __construct(
        protected SysConfigModel $configModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(string $groupKey, ?string $configKey = null, mixed $default = null): mixed
    {
        $cacheKey = CacheKeys::config($groupKey);

        $config = $this->cache->get($cacheKey);
        if ($config === null) {
            $record = $this->configModel
                ->where('group_key', $groupKey)
                ->where('status', 1)
                ->first();

            if (! $record) {
                return $default;
            }

            $config = $record->toArray();
            $this->cache->set($cacheKey, $config, CacheKeys::TTL_LONG);
        }

        if ($configKey === null) {
            return $config['config_data'] ?? $default;
        }

        return $config['config_data'][$configKey] ?? $default;
    }
}
