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

    // =================== 业务变更 → 需失效的缓存 ===================

    /**
     * 权限相关变更后必须失效的缓存集合.
     *
     * 角色、角色-权限、管理员-角色这三类变更都会影响「谁能看到什么」，
     * 必须同时失效：角色下拉、全量角色、用户权限码、用户菜单、超管菜单。
     * 这几个 key 之前散落在十几个 Action 里各写各的组合——有的只清 2 个、
     * 有的清 5 个，改完角色权限后部分用户仍会看到旧菜单。收敛到这里，
     * 新增调用方不需要再自己判断该清哪些。
     *
     * @return array<int, string>
     */
    public static function permissionRelatedKeys(): array
    {
        return [
            self::ROLE_OPTIONS,
            self::ROLE_ALL,
            self::PERM_CODES,
            self::MENU_USER,
            self::MENU_SUPER,
        ];
    }

    /**
     * 清除权限相关变更影响的全部缓存.
     */
    public static function flushPermissionRelated(): int
    {
        return self::flush(...self::permissionRelatedKeys());
    }

    /**
     * 菜单增删改后必须失效的缓存集合.
     *
     * 只有菜单自身的三份缓存：菜单树、超管菜单、按用户过滤的菜单。
     *
     * 不包含 PERM_CODES：它缓存的是 SysAdminModel::permissionCodes() 的结果，
     * 数据源是 sys_admin_role + sys_role.permission_codes，与 sys_menus 无关。
     * 菜单过滤是拿「用户的权限码集合」去匹配菜单的 permission_code，
     * 改菜单不会改变任何人的权限码集合。
     *
     * 之前 Update / Delete 会多清一次 PERM_CODES，是无效操作：该 key 是前缀，
     * 每次都会 SCAN 全量 perm:codes:*，代价不小却什么也没修正。
     *
     * 前提：菜单 CRUD 只写 sys_menus，不写角色权限。若将来加了
     * 「新建菜单时自动给某角色授权」这类联动，必须把它移到
     * flushPermissionRelated() 或两者都调。
     *
     * @return array<int, string>
     */
    public static function menuRelatedKeys(): array
    {
        return [
            self::MENU_TREE,
            self::MENU_SUPER,
            self::MENU_USER,
        ];
    }

    /**
     * 清除菜单变更影响的缓存.
     */
    public static function flushMenuRelated(): int
    {
        return self::flush(...self::menuRelatedKeys());
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

    /**
     * 读取真实缓存前缀.
     *
     * 3.2 的配置结构是 cache.stores.{store}.prefix；cache.default 只是 store 名。
     * 若这里读 cache.default.prefix 会永远落到默认值，改前缀时 SCAN 会全部落空。
     */
    private static function getCachePrefix(): string
    {
        $config = container()->get(ConfigInterface::class);
        $store = $config->get('cache.default', 'default');
        if (! is_string($store)) {
            $store = 'default';
        }

        return (string) $config->get("cache.stores.{$store}.prefix", '');
    }
}
