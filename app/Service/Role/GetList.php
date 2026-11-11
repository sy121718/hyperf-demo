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

use App\Dto\Role\RoleRequest;
use App\Model\Rbac\SysRoleModel;

class GetList
{
    public function __construct(
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(RoleRequest $request): mixed
    {
        // 列表页只查必要字段
        $fields = $request->withDetail() ? ['*'] : [
            'id', 'role_code', 'role_name', 'status', 'sort_order', 'remark', 'create_time',
        ];

        return $this->roleModel
            ->filter($request->toQueryArray())
            ->when($request->search, fn ($q) => $q->whereAny(
                $this->roleModel->getSearchableFields(),
                'like',
                "%{$request->search}%"
            ))
            ->sort($request->toQueryArray())
            ->paginate($request->pageSize(), $fields, 'page', $request->pageNumber());
    }
}
