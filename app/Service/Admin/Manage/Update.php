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
}
