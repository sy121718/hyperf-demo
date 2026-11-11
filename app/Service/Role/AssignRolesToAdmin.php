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
use App\Model\Admin\SysAdminModel;
use App\Model\Rbac\SysRoleModel;
use Hyperf\DbConnection\Db;
use Throwable;

use function Hyperf\Coroutine\parallel;

class AssignRolesToAdmin
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(array $data): array
    {
        $adminId = (int) ($data['admin_id'] ?? 0);
        $roleIds = $data['role_ids'] ?? [];

        requireId($adminId, '管理员');
        ! empty($roleIds) || paramError('角色ID不能为空');

        [$admin, $existCount] = parallel([
            fn () => $this->adminModel->find($adminId),
            fn () => $this->roleModel->whereIn('id', $roleIds)->count(),
        ]);

        ! $admin && notFound('管理员');
        $existCount === count($roleIds) || paramError('部分角色不存在');

        Db::beginTransaction();
        try {
            $admin->roles()->sync($roleIds);
            Db::commit();
            CacheKeys::flush(CacheKeys::PERM_CODES, CacheKeys::MENU_USER);
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }

        return ['admin_id' => $adminId, 'role_ids' => $roleIds];
    }
}
