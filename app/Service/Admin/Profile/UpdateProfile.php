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

class UpdateProfile
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

        if ($request->email !== null && $request->email !== $admin->email) {
            $this->adminModel->where('email', $request->email)
                ->where('id', '!=', $userId)->exists()
                && paramError('邮箱已被占用');
        }

        if ($request->phone !== null && $request->phone !== $admin->phone) {
            $this->adminModel->where('phone', $request->phone)
                ->where('id', '!=', $userId)->exists()
                && paramError('手机号已被占用');
        }

        // 只允许改这几个字段，其余（含状态、角色）不通过个人中心改
        $updateData = array_filter([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'avatar' => $request->avatar,
        ], static fn ($v) => $v !== null);

        if (! empty($updateData)) {
            $admin->update($updateData);
        }

        return true;
    }
}
