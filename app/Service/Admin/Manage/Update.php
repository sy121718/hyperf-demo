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

use App\Dto\Admin\AdminRequest;
use App\Model\Admin\SysAdminModel;
use App\Service\Role\AssignRoles;
use Hyperf\DbConnection\Db;
use Throwable;

class Update
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected AssignRoles $assignRolesAction,
    ) {
    }

    public function handle(AdminRequest $request): array
    {
        $id = (int) $request->id;
        $admin = $this->adminModel->findOrNotFound($id, '管理员');

        $currentUserId = (int) currentUser('id');
        $isSuperAdmin = currentUser('is_admin') == 1;

        if (! $isSuperAdmin && $currentUserId !== $id) {
            paramError('无权修改其他管理员信息', 403);
        }

        if (! $isSuperAdmin && $request->is_admin !== null) {
            paramError('无权修改管理员类型', 403);
        }

        $this->assertSuperAdminChange($admin, $request, $currentUserId);

        if ($request->username && $request->username !== $admin->username) {
            ! $this->adminModel->isFieldUnique(['username' => $request->username], $id)
                && paramError('用户名已被占用');
        }

        if ($request->email && $request->email !== $admin->email) {
            ! $this->adminModel->isFieldUnique(['email' => $request->email], $id)
                && paramError('邮箱已被占用');
        }

        Db::beginTransaction();
        try {
            $data = $request->toDbArrayFiltered();
            if ($request->password) {
                $data['password'] = password_hash((string) $request->password, PASSWORD_BCRYPT);
            } else {
                unset($data['password']);
            }

            $admin->update($data);

            // role_ids 为 null 表示不修改角色
            $request->role_ids !== null
                && $this->assignRolesAction->handle($id, $request->role_ids);

            Db::commit();

            return $admin->fresh()->toArray();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 超管身份的变更限制.
     *
     * 系统只允许存在一个超管（数据库 uk_super_slot 兜底），因此：
     * - 不能把普通管理员提升为超管（名额已被占用时）
     * - 不能把自己降级，否则可能把系统锁死在没有管理员的境地
     */
    private function assertSuperAdminChange(SysAdminModel $admin, AdminRequest $request, int $currentUserId): void
    {
        if ($request->is_admin === null) {
            return;
        }

        $wantSuper = $request->is_admin === 1;
        $isSuper = $admin->is_admin === 1;

        if ($wantSuper === $isSuper) {
            return;
        }

        if ($wantSuper) {
            $this->adminModel->hasOtherSuperAdmin()
                && paramError('系统只允许存在一个超级管理员');
            return;
        }

        // 降级：不能操作自己
        if ($admin->id === $currentUserId) {
            paramError('不能取消自己的超级管理员身份');
        }
    }
}
