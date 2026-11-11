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

namespace App\Service\Permission;

use App\Common\CacheKeys;
use App\Dto\Permission\PermissionRequest;
use App\Model\Rbac\SysPermissionDictModel;
use Psr\SimpleCache\CacheInterface;

class GetTreeList
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(PermissionRequest $request): array
    {
        // 带筛选条件时结果不进缓存，避免筛选结果污染全量树
        $hasFilter = $request->search !== null || $request->status !== null;

        if (! $hasFilter) {
            $cached = $this->cache->get(CacheKeys::PERM_TREE);
            if ($cached !== null) {
                return $cached;
            }
        }

        $permissions = $this->permissionModel
            ->filter($request->toQueryArray())
            ->when($request->search, fn ($q) => $q->whereAny(
                $this->permissionModel->getSearchableFields(),
                'like',
                "%{$request->search}%"
            ))
            ->sort($request->toQueryArray())
            ->get();

        $result = $permissions->isEmpty() ? [] : $this->permissionModel->buildTree($permissions->toArray());

        if (! $hasFilter) {
            $this->cache->set(CacheKeys::PERM_TREE, $result, CacheKeys::TTL_MEDIUM);
        }

        return $result;
    }
}
