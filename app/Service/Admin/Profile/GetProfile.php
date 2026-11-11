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

use App\Model\Admin\SysAdminModel;

class GetProfile
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    public function handle(): array
    {
        $userId = (int) currentUser('id');
        $userId > 0 || paramError('用户未登录', 401);

        $admin = $this->adminModel->with(['roles:sys_role.id,sys_role.role_name,sys_role.role_code'])->find($userId);
        ! $admin && notFound('用户');

        $data = $admin->toArray();
        unset($data['password']);

        return $data;
    }
}
