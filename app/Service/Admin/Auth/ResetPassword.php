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

namespace App\Service\Admin\Auth;

use App\Model\Admin\SysAdminModel;

class ResetPassword
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    /**
     * 重置管理员密码
     *
     * @param array $data 包含 admin_id, new_password
     */
    public function handle(array $data): void
    {
        $isSuperAdmin = currentUser('is_admin');
        $currentUserId = (int) currentUser('id');

        $adminId = (int) ($data['admin_id'] ?? 0);
        $newPassword = $data['new_password'] ?? '';

        $adminId > 0 || paramError('管理员ID不能为空');
        ! empty($newPassword) || paramError('新密码不能为空');

        $admin = $this->adminModel->find($adminId);
        ! $admin && paramError('管理员不存在', 404);

        if ($admin->is_admin == 1 && ! $isSuperAdmin) {
            paramError('无权重置超级管理员密码', 403);
        }

        if (! $isSuperAdmin && $currentUserId !== $adminId) {
            paramError('无权重置他人密码', 403);
        }

        $admin->password = password_hash($newPassword, PASSWORD_BCRYPT);
        $admin->save();
    }
}
