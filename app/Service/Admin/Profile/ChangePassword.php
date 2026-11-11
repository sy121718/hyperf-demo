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

namespace App\Service\Admin\Profile;

use App\Dto\Admin\AdminRequest;
use App\Model\Admin\SysAdminModel;

class ChangePassword
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    public function handle(AdminRequest $request): bool
    {
        $userId = (int) currentUser('id');
        $userId > 0 || paramError('用户未登录', 401);

        $admin = $this->adminModel->findOrNotFound($userId, '用户');

        ! password_verify((string) $request->old_password, $admin->password) && paramError('旧密码错误');

        $admin->password = password_hash((string) $request->new_password, PASSWORD_DEFAULT);
        $admin->save();

        return true;
    }
}
