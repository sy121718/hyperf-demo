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

use App\Model\Admin\SysAdminRoleModel;
use Hyperf\DbConnection\Db;
use Throwable;

class AssignRoles
{
    public function __construct(
        protected SysAdminRoleModel $adminRoleModel,
    ) {
    }

    public function handle(int $adminId, array $roleIds): void
    {
        Db::beginTransaction();
        try {
            $this->adminRoleModel->where('admin_id', $adminId)->delete();

            if (! empty($roleIds)) {
                $rows = array_map(fn (int $roleId) => [
                    'admin_id' => $adminId,
                    'role_id' => $roleId,
                    'create_by' => $adminId,
                ], $roleIds);
                $this->adminRoleModel->insert($rows);
            }

            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
