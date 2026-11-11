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
use Hyperf\Cache\Driver\RedisDriver;
use Hyperf\Codec\Packer\PhpSerializerPacker;

use function Hyperf\Support\env;

return [
    // 默认使用的 store
    'default' => env('CACHE_DRIVER', 'default'),

    'stores' => [
        'default' => [
            'driver' => RedisDriver::class,
            'packer' => PhpSerializerPacker::class,
            // 注意：这个前缀是缓存 Key 的真实前缀，CacheKeys 也从这里读取
            'prefix' => 'c:',
            'skip_cache_results' => [],
            'options' => [
                'pool' => 'default',
            ],
        ],
    ],
];
