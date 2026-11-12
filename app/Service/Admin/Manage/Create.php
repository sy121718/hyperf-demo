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

class Create
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected AssignRoles $assignRolesAction,
    ) {
    }

    public function handle(AdminRequest $request): array
    {
        $username = (string) $request->username;
        ! $this->adminModel->isFieldUnique(['username' => $username])
            && paramError('用户名已存在');

        // 建成超管没有任何入口：超管只由初始化脚本产生，且全库仅一个
        $request->is_admin === 1 && paramError('不能通过创建接口设置超级管理员');

        $data = $request->toDbArrayFiltered();
        $data['password'] = password_hash((string) $request->password, PASSWORD_BCRYPT);

        $admin = null;
        Db::beginTransaction();
        try {
            $admin = $this->adminModel->create($data);

            // role_ids 是关联 ID 集合，不写进 sys_admin 表
            ! empty($request->role_ids)
                && $this->assignRolesAction->handle($admin->id, $request->role_ids);

            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }

        return $admin->fresh()->toArray();
    }
}
