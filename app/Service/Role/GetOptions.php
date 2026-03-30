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

class GetOptions
{
    public function __construct(
        protected SysRoleModel $roleModel,
        protected CacheInterface $cache,
    ) {
    }

    public function handle(array $data = []): array
    {
        $cached = $this->cache->get(CacheKeys::ROLE_OPTIONS);
        if ($cached !== null) {
            return $cached;
        }

        $roles = $this->roleModel
            ->where('status', 1)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->select(['id', 'role_name', 'role_code'])
            ->get();

        $result = $roles->map(fn ($r) => [
            'value' => $r->id,
            'label' => $r->role_name,
            'code' => $r->role_code,
        ])->toArray();

        $this->cache->set(CacheKeys::ROLE_OPTIONS, $result, CacheKeys::TTL_MEDIUM);

        return $result;
    }
}
