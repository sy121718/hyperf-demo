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
use App\Model\Admin\SysAdminRoleModel;
use App\Model\Rbac\SysRoleModel;
use Hyperf\DbConnection\Db;
use Throwable;

class Delete
{
    public function __construct(
        protected SysRoleModel $roleModel,
        protected SysAdminRoleModel $adminRoleModel,
    ) {
    }

    public function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);

        $role = $this->roleModel->findOrNotFound($id, '角色');

        $this->adminRoleModel->where('role_code', $role->role_code)->exists()
            && paramError('角色存在关联的用户，无法删除');

        Db::beginTransaction();
        try {
            $role->delete();

            Db::commit();
            CacheKeys::flush(CacheKeys::ROLE_OPTIONS, CacheKeys::ROLE_ALL);
            return true;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
