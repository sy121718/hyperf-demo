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

namespace App\Service\Role;

use App\Common\CacheKeys;
use App\Model\Role\SysRoleModel;
use Psr\SimpleCache\CacheInterface;

class AllRoles
{
    public function __construct(
        protected SysRoleModel $roleModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(array $data = []): array
    {
        $cached = $this->cache->get(CacheKeys::ROLE_ALL);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->roleModel
            ->select(['id', 'role_name', 'role_code', 'remark', 'status'])
            ->where('status', 1)
            ->get()
            ->toArray();

        $this->cache->set(CacheKeys::ROLE_ALL, $result, CacheKeys::TTL_MEDIUM);

        return $result;
    }
}
