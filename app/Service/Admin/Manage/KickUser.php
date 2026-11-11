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
use App\Model\Admin\SysAdminSessionsModel;

class KickUser
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected SysAdminSessionsModel $sessionsModel,
    ) {
    }

    public function handle(int $userId): void
    {
        requireId($userId, '用户');

        $admin = $this->adminModel->find($userId);
        ! $admin && notFound('用户');

        $this->sessionsModel
            ->where('admin_id', $userId)
            ->where('status', 1)
            ->update(['status' => 0]);
    }
}
