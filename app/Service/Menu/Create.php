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

namespace App\Service\Menu;

use App\Common\CacheKeys;
use App\Dto\Menu\MenuRequest;
use App\Model\Rbac\SysMenuModel;
use Hyperf\DbConnection\Db;
use Throwable;

class Create
{
    public function __construct(
        protected SysMenuModel $menuModel,
    ) {
    }

    public function handle(MenuRequest $request): array
    {
        $menuCode = (string) $request->menu_code;
        ! $this->menuModel->isFieldUnique(['menu_code' => $menuCode])
            && paramError("菜单标识码 {$menuCode} 已存在");

        $parentId = (int) ($request->parent_id ?? 0);
        if ($parentId > 0) {
            ! $this->menuModel->where('id', $parentId)->exists()
                && paramError("父级菜单ID {$parentId} 不存在");
        }

        $permissionCodes = $request->permission_codes ?? [];
        $this->assertPermissionCodes((int) ($request->type ?? 2), $permissionCodes);

        Db::beginTransaction();
        try {
            $dbData = $request->toDbArrayFiltered();
            $dbData['parent_id'] = $parentId;
            // permission_codes 落库为逗号分隔字符串，字段是 permission_code
            $dbData['permission_code'] = $permissionCodes === [] ? null : implode(',', $permissionCodes);
            $dbData['create_by'] = (int) currentUser('id');

            $menu = $this->menuModel->create($dbData);

            Db::commit();
            CacheKeys::flush(CacheKeys::MENU_TREE, CacheKeys::MENU_SUPER, CacheKeys::MENU_USER);

            return $menu->toArray();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 目录类型不能有权限标识，非目录类型必须有.
     *
     * @param array<int, string> $permissionCodes
     */
    private function assertPermissionCodes(int $type, array $permissionCodes): void
    {
        if ($type === 0) {
            $permissionCodes !== [] && paramError('目录类型不能设置权限标识');

            return;
        }

        $permissionCodes === [] && paramError('非目录类型必须设置权限标识');
    }
}
