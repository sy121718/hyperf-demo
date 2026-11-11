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

use App\Model\Admin\SysAdminModel;
use App\Model\Admin\SysAdminRoleModel;
use App\Model\Admin\SysAdminSessionsModel;
use Hyperf\DbConnection\Db;
use Throwable;

class Delete
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected SysAdminRoleModel $adminRoleModel,
        protected SysAdminSessionsModel $sessionsModel,
    ) {
    }

    public function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        requireId($id, '管理员');

        $admin = $this->adminModel->find($id);
        ! $admin && notFound('管理员');
        $admin->is_admin === 1 && paramError('不能删除超级管理员', 403);

        Db::beginTransaction();
        try {
            // sys_admin_role 的关联键是 admin_code
            $this->adminRoleModel->where('admin_code', $admin->admin_code)->delete();
            $this->sessionsModel->where('admin_id', $id)->delete();
            $admin->delete();

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
