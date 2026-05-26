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

namespace App\Common;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;
use Psr\SimpleCache\CacheInterface;

/**
 * 统一缓存 Key 管理 + 通用清除.
 *
 * 用法：
 *   读缓存时用常量做 key
 *   写操作后调用 CacheKeys::flush(CacheKeys::ROLE_OPTIONS, CacheKeys::ROLE_ALL)
 *   前缀清除（带 * 的动态 key）：CacheKeys::flush(CacheKeys::PERM_CODES)  ← 末尾带冒号自动走 SCAN
 */
final class CacheKeys
{
    // =================== 精确 Key（无后缀，直接 delete） ===================

    public const ROLE_OPTIONS = 'biz:role:options';

    public const ROLE_ALL = 'biz:role:all';

    public const MENU_TREE = 'biz:menu:tree';

    public const MENU_SUPER = 'biz:menu:super';

    public const PERM_OPTIONS = 'biz:perm:options';

    public const PERM_TREE = 'biz:perm:tree';

    public const DICT_TREE = 'biz:dict:tree';

    // =================== 前缀 Key（末尾带冒号，走 SCAN 批量删除） ===================

    public const AUTH_SESSION = 'auth:session:';

    public const PERM_API = 'perm:api:';

    public const PERM_CODES = 'perm:codes:';  // 用户的所有权限码集合

    public const MENU_USER = 'biz:menu:user:';

    public const DICT_ITEMS = 'biz:dict:items:';

    public const IP_BLACKLIST = 'biz:ip:ban:';

    public const CONFIG = 'config:';

    // =================== TTL ===================

    public const TTL_SHORT = 120;

    public const TTL_MEDIUM = 600;

    public const TTL_LONG = 3600;

    // =================== Key 构建（动态后缀） ===================

    public static function authSession(string $token): string
    {
        return self::AUTH_SESSION . md5($token);
    }

    public static function permApi(string $method, string $path): string
    {
        return self::PERM_API . "{$method}:" . md5($path);
    }

    public static function permCodes(string $adminCode): string
    {
        return self::PERM_CODES . $adminCode;
    }

    public static function menuUser(int $userId): string
    {
        return self::MENU_USER . $userId;
    }

    public static function dictItems(int $dictId): string
    {
        return self::DICT_ITEMS . $dictId;
    }

    public static function ipBlacklist(string $ip): string
    {
        return self::IP_BLACKLIST . $ip;
    }

    public static function config(string $groupKey): string
    {
        return self::CONFIG . $groupKey;
    }

    // =================== 通用清除（核心方法，只需这一个） ===================

    /**
     * 清除指定的缓存 key / 前缀
     *
     * - 末尾带冒号的 key：视为前缀，走 Redis SCAN 批量删除
     * - 其他 key：精确删除
     *
     * 用法：CacheKeys::flush(CacheKeys::ROLE_OPTIONS, CacheKeys::ROLE_ALL, CacheKeys::MENU_USER)
     */
    public static function flush(string ...$keys): int
    {
        $exactKeys = [];
        $deleted = 0;

        foreach ($keys as $key) {
            if (str_ends_with($key, ':')) {
                $deleted += self::deleteByPrefix($key);
            } else {
                $exactKeys[] = $key;
            }
        }

        if (! empty($exactKeys)) {
            self::deleteMultiple($exactKeys);
            $deleted += count($exactKeys);
        }

        return $deleted;
    }

    /**
     * 删除单个精确 key.
     */
    public static function delete(string $key): bool
    {
        return container()->get(CacheInterface::class)->delete($key);
    }

    // =================== 内部方法 ===================

    private static function deleteMultiple(array $keys): bool
    {
        return container()->get(CacheInterface::class)->deleteMultiple($keys);
    }

    private static function deleteByPrefix(string $prefix): int
    {
        $redis = container()->get(Redis::class);
        $cachePrefix = self::getCachePrefix();
        $pattern = $cachePrefix . $prefix . '*';

        $deleted = 0;
        $cursor = null;
        do {
            $result = $redis->scan($cursor, $pattern, 200);
            if ($result === false) {
                break;
            }
            if (! empty($result)) {
                $deleted += $redis->del(...$result);
            }
        } while ($cursor > 0);

        return $deleted;
    }

    private static function getCachePrefix(): string
    {
        $config = container()->get(ConfigInterface::class);
        return (string) $config->get('cache.default.prefix', 'c:');
    }
}
