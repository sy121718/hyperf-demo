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

namespace App\Service\Role;

use App\Common\CacheKeys;
use App\Dto\Role\RoleRequest;
use App\Model\Admin\SysAdminRoleModel;
use App\Model\Rbac\SysRoleModel;
use Hyperf\DbConnection\Db;
use Throwable;

class BatchDelete
{
    public function __construct(
        protected SysRoleModel $roleModel,
        protected SysAdminRoleModel $adminRoleModel,
    ) {
    }

    public function handle(RoleRequest $request): int
    {
        $isSuperAdmin = currentUser('is_admin') == 1;
        if (! $isSuperAdmin) {
            paramError('只有超级管理员可以批量删除角色', 403);
        }

        $ids = $request->ids ?? [];
        empty($ids) && paramError('请选择要删除的角色');

        Db::beginTransaction();
        try {
            // 获取要删除的角色代码
            $roles = $this->roleModel->whereIn('id', $ids)->get();
            $roleCodes = $roles->pluck('role_code')->toArray();

            $count = $this->adminRoleModel->whereIn('role_code', $roleCodes)->count();
            $count > 0 && paramError('角色存在关联的用户，无法删除');

            $deletedCount = $this->roleModel->whereIn('id', $ids)->delete();

            Db::commit();
            CacheKeys::flush(CacheKeys::ROLE_OPTIONS, CacheKeys::ROLE_ALL);
            return $deletedCount;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
