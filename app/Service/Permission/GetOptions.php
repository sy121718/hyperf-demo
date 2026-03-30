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

class GetOptions
{
    public function __construct(
        protected SysPermissionDictModel $permissionModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(): array
    {
        $cached = $this->cache->get(CacheKeys::PERM_OPTIONS);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->permissionModel
            ->select(['id', 'perm_name', 'perm_code', 'parent_id'])
            ->where('status', 1)
            ->orderBy('route_priority', 'asc')
            ->get()
            ->toArray();

        $this->cache->set(CacheKeys::PERM_OPTIONS, $result, CacheKeys::TTL_MEDIUM);

        return $result;
    }
}
