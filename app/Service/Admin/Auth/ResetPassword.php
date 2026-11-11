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

use App\Dto\Admin\AdminRequest;
use App\Model\Admin\SysAdminModel;

class ResetPassword
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    /**
     * 重置管理员密码
     */
    public function handle(AdminRequest $request): void
    {
        $isSuperAdmin = currentUser('is_admin');
        $currentUserId = (int) currentUser('id');

        $adminId = (int) $request->admin_id;
        $newPassword = (string) $request->new_password;

        ! empty($newPassword) || paramError('新密码不能为空');

        $admin = $this->adminModel->findOrNotFound($adminId, '管理员');

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
