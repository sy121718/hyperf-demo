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

namespace App\Service\Menu;

use App\Common\CacheKeys;
use App\Dto\Menu\MenuRequest;
use App\Model\Rbac\SysMenuModel;
use Psr\SimpleCache\CacheInterface;

class GetTree
{
    public function __construct(
        protected SysMenuModel $menuModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(MenuRequest $request): array
    {
        // 带筛选条件时结果不进缓存，避免筛选结果污染全量树
        $hasFilter = $request->search !== null || $request->status !== null;

        if (! $hasFilter) {
            $cached = $this->cache->get(CacheKeys::MENU_TREE);
            if ($cached !== null) {
                return $cached;
            }
        }

        $menus = $this->menuModel
            ->filter($request->toQueryArray())
            ->when($request->search, fn ($q) => $q->whereAny(
                $this->menuModel->getSearchableFields(),
                'like',
                "%{$request->search}%"
            ))
            ->sort($request->toQueryArray())
            ->get();

        $result = $menus->isEmpty() ? [] : $this->menuModel->buildTree($menus->toArray());

        if (! $hasFilter) {
            $this->cache->set(CacheKeys::MENU_TREE, $result, CacheKeys::TTL_MEDIUM);
        }

        return $result;
    }
}
