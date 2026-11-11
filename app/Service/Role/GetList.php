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

use App\Model\Rbac\SysRoleModel;

class GetList
{
    public function __construct(
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(array $data = []): mixed
    {
        $search = $data['search'] ?? null;
        $withDetail = ($data['with'] ?? '') === 'detail';

        // 列表页只查必要字段
        $fields = $withDetail ? ['*'] : [
            'id', 'role_code', 'role_name', 'status', 'sort_order', 'remark', 'create_time',
        ];

        return $this->roleModel
            ->filter($data)
            ->when($search, fn ($q) => $q->whereAny(
                $this->roleModel->getSearchableFields(),
                'like',
                "%{$search}%"
            ))
            ->sort($data)
            ->paginate((int) ($data['limit'] ?? 10), $fields, 'page', (int) ($data['page'] ?? 1));
    }
}
