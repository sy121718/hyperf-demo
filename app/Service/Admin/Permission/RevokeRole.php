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

namespace App\Service\Admin\Permission;

use App\Common\CacheKeys;
use App\Dto\Admin\AdminRequest;
use App\Model\Admin\SysAdminModel;
use App\Model\Rbac\SysRoleModel;
use Hyperf\DbConnection\Db;
use Throwable;

use function Hyperf\Coroutine\parallel;

class RevokeRole
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected SysRoleModel $roleModel,
    ) {
    }

    public function handle(AdminRequest $request): array
    {
        $adminId = (int) $request->admin_id;
        $roleIds = $request->role_ids ?? [];

        requireId($adminId, '管理员');
        ! empty($roleIds) && is_array($roleIds) || paramError('角色ID不能为空');

        [$admin, $roles] = parallel([
            fn () => $this->adminModel->find($adminId),
            fn () => $this->roleModel->whereIn('id', $roleIds)->get(),
        ]);

        ! $admin && notFound('管理员');
        $roles->count() === count($roleIds) || paramError('部分角色不存在');

        // 关联键是 role_code 不是自增 id：roles() 的 belongsToMany 以
        // sys_admin_role.role_code ↔ sys_role.role_code 连接，sync() 会把传入的
        // 值直接写进 role_code 列。之前传 pluck('id')，结果是原有角色全被删掉、
        // 换成一条 role_code='2' 的无效记录。
        $currentRoleCodes = $admin->roles->pluck('role_code')->toArray();
        $revokeCodes = $roles->pluck('role_code')->toArray();
        $remainingRoleCodes = array_values(array_diff($currentRoleCodes, $revokeCodes));

        Db::beginTransaction();
        try {
            $admin->roles()->sync($remainingRoleCodes);
            Db::commit();
            CacheKeys::flush(CacheKeys::PERM_CODES, CacheKeys::MENU_USER);
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }

        return ['admin_id' => $adminId, 'removed_role_ids' => $roleIds];
    }
}
