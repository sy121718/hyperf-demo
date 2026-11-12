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

class Update
{
    public function __construct(
        protected SysMenuModel $menuModel,
    ) {
    }

    public function handle(MenuRequest $request): array
    {
        $id = (int) $request->id;
        $menu = $this->menuModel->findOrNotFound($id, '菜单');

        if ($request->menu_code && $request->menu_code !== $menu->menu_code) {
            ! $this->menuModel->isFieldUnique(['menu_code' => $request->menu_code], $id)
                && paramError("菜单标识码 {$request->menu_code} 已存在");
        }

        if ($request->parent_id !== null) {
            $request->parent_id === $id && paramError('不能将自己设为父菜单');
            if ($request->parent_id > 0) {
                ! $this->menuModel->where('id', $request->parent_id)->exists()
                    && paramError("父级菜单ID {$request->parent_id} 不存在");
            }
        }

        Db::beginTransaction();
        try {
            // permission_codes 已在 virtualFields 里剔除，这里只需补 permission_code
            $updateData = $request->toDbArrayFiltered();

            // 显式提交了 permission_codes 才更新权限标识
            if ($request->permission_codes !== null) {
                $this->assertPermissionCodes(
                    (int) ($request->type ?? $menu->type),
                    $request->permission_codes
                );
                $updateData['permission_code'] = $request->permission_codes === []
                    ? null
                    : implode(',', $request->permission_codes);
            }

            $updateData['update_by'] = (int) currentUser('id');
            $menu->update($updateData);

            Db::commit();
            // 菜单变更不影响任何人的权限码集合，无需清 PERM_CODES
            CacheKeys::flushMenuRelated();

            return $menu->fresh()->toArray();
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
