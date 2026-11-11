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

class UnbanAdmin
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    public function handle(int $id): void
    {
        $admin = $this->adminModel->findOrNotFound($id, '管理员');

        $admin->status = 1;
        $admin->login_failure_count = 0;
        $admin->save();
    }
}
