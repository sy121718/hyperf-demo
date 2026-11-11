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

class GetAllAdmins
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    public function handle(AdminRequest $request): array
    {
        return $this->adminModel
            ->select(['id', 'username', 'name', 'avatar', 'email', 'phone', 'status', 'is_admin', 'last_login_time'])
            ->when($request->status !== null, fn ($q) => $q->where('status', $request->status))
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();
    }
}
