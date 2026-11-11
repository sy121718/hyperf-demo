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

class UpdateProfile
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    public function handle(array $data): bool
    {
        $userId = (int) currentUser('id');
        $userId > 0 || paramError('用户未登录', 401);

        $admin = $this->adminModel->findOrNotFound($userId, '用户');

        if (isset($data['email']) && $data['email'] !== $admin->email) {
            $this->adminModel->where('email', $data['email'])
                ->where('id', '!=', $userId)->exists()
                && paramError('邮箱已被占用');
        }

        if (isset($data['phone']) && $data['phone'] !== $admin->phone) {
            $this->adminModel->where('phone', $data['phone'])
                ->where('id', '!=', $userId)->exists()
                && paramError('手机号已被占用');
        }

        $allowedFields = ['name', 'email', 'phone', 'avatar'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (! empty($updateData)) {
            $admin->update($updateData);
        }

        return true;
    }
}
