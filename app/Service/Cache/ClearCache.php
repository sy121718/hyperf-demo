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

namespace App\Service\Cache;

use App\Common\CacheKeys;

class ClearCache
{
    /**
     * 模块 → 需要清除的 key/前缀 映射
     * 末尾带冒号的走 SCAN 前缀删除，不带的精确删除.
     */
    private const MODULES = [
        'role' => ['label' => '角色', 'keys' => [CacheKeys::ROLE_OPTIONS, CacheKeys::ROLE_ALL]],
        'menu' => ['label' => '菜单', 'keys' => [CacheKeys::MENU_TREE, CacheKeys::MENU_SUPER, CacheKeys::MENU_USER]],
        'permission' => ['label' => '权限', 'keys' => [CacheKeys::PERM_OPTIONS, CacheKeys::PERM_TREE, CacheKeys::PERM_API]],
        'dict' => ['label' => '字典', 'keys' => [CacheKeys::DICT_TREE, CacheKeys::DICT_ITEMS]],
        'config' => ['label' => '系统配置', 'keys' => [CacheKeys::CONFIG]],
        'ip' => ['label' => 'IP黑名单', 'keys' => [CacheKeys::IP_BLACKLIST]],
        'auth' => ['label' => '登录会话', 'keys' => [CacheKeys::AUTH_SESSION]],
        'user_perm' => ['label' => '用户权限', 'keys' => [CacheKeys::PERM_USER, CacheKeys::MENU_USER, CacheKeys::MENU_SUPER]],
    ];

    public function handle(array $modules = []): array
    {
        $targets = empty($modules) ? array_keys(self::MODULES) : $modules;
        $cleared = [];
        $totalDeleted = 0;

        foreach ($targets as $module) {
            if (! isset(self::MODULES[$module])) {
                continue;
            }

            $def = self::MODULES[$module];
            $deleted = CacheKeys::flush(...$def['keys']);
            $totalDeleted += $deleted;
            $cleared[] = ['module' => $module, 'label' => $def['label'], 'deleted_keys' => $deleted];
        }

        return [
            'cleared' => $cleared,
            'total_deleted' => $totalDeleted,
            'is_full_clear' => empty($modules),
        ];
    }

    public function getModules(): array
    {
        $result = [];
        foreach (self::MODULES as $key => $def) {
            $result[] = ['value' => $key, 'label' => $def['label']];
        }
        return $result;
    }
}
