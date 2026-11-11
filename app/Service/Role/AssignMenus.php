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
use App\Model\Menu\SysMenuModel;
use App\Model\Role\SysRoleModel;

class AssignMenus
{
    public function __construct(
        protected SysRoleModel $roleModel,
        protected SysMenuModel $menuModel,
    ) {
    }

    public function handle(string $roleCode, array $menuIds): array
    {
        ! empty($roleCode) || paramError('角色标识不能为空');

        $role = $this->roleModel->where('role_code', $roleCode)->first();
        ! $role && paramError('角色不存在', 404);

        // 获取菜单的 permission_code（目录类型没有 permission_code）
        $permissionCodes = [];
        if (! empty($menuIds)) {
            $menus = $this->menuModel->whereIn('id', $menuIds)->get();
            foreach ($menus as $menu) {
                if (! empty($menu->permission_code)) {
                    $permissionCodes[] = $menu->permission_code;
                }
            }
        }

        // 去重
        $permissionCodes = array_unique($permissionCodes);

        // 模型已配 'array' cast，无需手动 json_encode
        $role->update(['permission_codes' => $permissionCodes]);

        CacheKeys::flush(
            CacheKeys::ROLE_OPTIONS,
            CacheKeys::ROLE_ALL,
            CacheKeys::PERM_CODES,
            CacheKeys::MENU_USER,
            CacheKeys::MENU_SUPER
        );

        return ['role_code' => $roleCode, 'menu_ids' => $menuIds, 'permission_codes' => $permissionCodes];
    }
}
