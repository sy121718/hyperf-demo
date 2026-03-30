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

use App\Model\Admin\SysAdminModel;

class BanAdmin
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    public function handle(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $id > 0 || paramError('管理员ID不能为空');

        $admin = $this->adminModel->find($id);
        ! $admin && paramError('管理员不存在', 404);
        $admin->is_admin === 1 && paramError('超级管理员不能封禁', 403);

        $admin->status = 0;
        $admin->save();
    }
}
