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

namespace App\Service\Admin\Permission;

use App\Model\Admin\SysAdminModel;

/**
 * 当前登录用户的权限码集合。
 *
 * 前端靠它决定「全部 / 我的」两个视图能否切换，以及按钮是否显示。
 * 超管的 is_admin 单独返回，前端 hasPermission() 对其直接放行，
 * 不必在这里枚举全部权限点。
 */
class GetMine
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    public function handle(): array
    {
        $adminId = (int) currentUser('id');
        $adminId > 0 || paramError('用户未登录', 401);

        $admin = $this->adminModel->findOrNotFound($adminId, '用户', ['roles']);

        $codes = [];
        foreach ($admin->roles as $role) {
            if ((int) $role->status !== 1) {
                continue;
            }
            foreach ($role->permission_codes ?? [] as $code) {
                $codes[] = $code;
            }
        }

        return [
            'admin_id' => $adminId,
            'is_admin' => (int) $admin->is_admin,
            'permission_codes' => array_values(array_unique($codes)),
        ];
    }
}
