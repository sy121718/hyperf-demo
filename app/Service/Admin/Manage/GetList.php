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

namespace App\Service\Admin\Manage;

use App\Dto\Admin\AdminRequest;
use App\Model\Admin\SysAdminModel;

class GetList
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    public function handle(AdminRequest $request): mixed
    {
        // 列表页只查必要字段
        $fields = $request->withDetail() ? ['*'] : [
            'id', 'username', 'name', 'email', 'phone', 'status',
            'is_admin', 'last_login_ip', 'last_login_time', 'create_time',
        ];

        return $this->adminModel
            ->filter($request->toQueryArray())
            ->when($request->search, fn ($q) => $q->whereAny(
                $this->adminModel->getSearchableFields(),
                'like',
                "%{$request->search}%"
            ))
            ->sort($request->toQueryArray())
            ->paginate($request->pageSize(), $fields, 'page', $request->pageNumber());
    }
}
