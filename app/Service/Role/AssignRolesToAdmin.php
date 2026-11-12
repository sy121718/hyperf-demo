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

    public function handle(RoleRequest $request): array
    {
        $adminId = (int) $request->admin_id;
        $roleIds = $request->role_ids ?? [];

        requireId($adminId, '管理员');
        ! empty($roleIds) || paramError('角色ID不能为空');

        [$admin, $roles] = parallel([
            fn () => $this->adminModel->find($adminId),
            fn () => $this->roleModel->whereIn('id', $roleIds)->get(['role_code']),
        ]);

        ! $admin && notFound('管理员');
        $roles->count() === count($roleIds) || paramError('部分角色不存在');

        // 关联键是 role_code：roles() 的 belongsToMany 以
        // sys_admin_role.role_code ↔ sys_role.role_code 连接，sync() 会把传入的值
        // 直接写进 role_code 列。传自增 id 会写成 role_code='1' 这种无效记录。
        $roleCodes = $roles->pluck('role_code')->all();

        Db::beginTransaction();
        try {
            $admin->roles()->sync($roleCodes);
            Db::commit();
            CacheKeys::flush(CacheKeys::PERM_CODES, CacheKeys::MENU_USER);
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }

        return ['admin_id' => $adminId, 'role_ids' => $roleIds];
    }
}
