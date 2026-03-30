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
use App\Model\Menu\SysMenuModel;
use Psr\SimpleCache\CacheInterface;

class GetTree
{
    public function __construct(
        protected SysMenuModel $menuModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(array $data = []): array
    {
        $search = $data['search'] ?? null;
        $hasFilter = $search || ! empty($data['status']);

        if (! $hasFilter) {
            $cached = $this->cache->get(CacheKeys::MENU_TREE);
            if ($cached !== null) {
                return $cached;
            }
        }

        $menus = $this->menuModel
            ->with(['permissions'])
            ->filter($data)
            ->when($search, fn ($q) => $q->whereAny(
                $this->menuModel->getSearchableFields(),
                'like',
                "%{$search}%"
            ))
            ->sort($data)
            ->get();

        $result = $menus->isEmpty() ? [] : $this->menuModel->buildTree($menus->toArray());

        if (! $hasFilter) {
            $this->cache->set(CacheKeys::MENU_TREE, $result, CacheKeys::TTL_MEDIUM);
        }

        return $result;
    }
}
