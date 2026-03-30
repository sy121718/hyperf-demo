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
use App\Model\Permission\SysPermissionDictModel;
use Psr\SimpleCache\CacheInterface;

class GetTreeList
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(array $data = []): array
    {
        $search = $data['search'] ?? null;
        $hasFilter = $search || ! empty($data['status']);

        if (! $hasFilter) {
            $cached = $this->cache->get(CacheKeys::PERM_TREE);
            if ($cached !== null) {
                return $cached;
            }
        }

        $permissions = $this->permissionModel
            ->filter($data)
            ->when($search, fn ($q) => $q->whereAny(
                $this->permissionModel->getSearchableFields(),
                'like',
                "%{$search}%"
            ))
            ->sort($data)
            ->get();

        $result = $permissions->isEmpty() ? [] : $this->permissionModel->buildTree($permissions->toArray());

        if (! $hasFilter) {
            $this->cache->set(CacheKeys::PERM_TREE, $result, CacheKeys::TTL_MEDIUM);
        }

        return $result;
    }
}
