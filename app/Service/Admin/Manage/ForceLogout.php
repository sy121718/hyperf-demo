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

class ForceLogout
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected SysAdminSessionsModel $sessionsModel,
    ) {
    }

    public function handle(array $data): void
    {
        $adminId = (int) ($data['admin_id'] ?? 0);
        $sessionId = (int) ($data['session_id'] ?? 0);

        $adminId > 0 || paramError('管理员ID不能为空');

        $admin = $this->adminModel->find($adminId);
        ! $admin && paramError('管理员不存在', 404);

        $query = $this->sessionsModel->where('admin_id', $adminId);

        if ($sessionId > 0) {
            $query->where('id', $sessionId);
        }

        $query->update(['status' => 0]);
    }
}
