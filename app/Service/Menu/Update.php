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
use App\Model\Menu\SysMenuModel;
use App\Service\Menu\Dto\UpdateMenuDto;
use Hyperf\DbConnection\Db;
use Throwable;

class Update
{
    public function __construct(
        protected SysMenuModel $menuModel,
    ) {
    }

    public function handle(UpdateMenuDto $dto): array
    {
        $menu = $this->menuModel->find($dto->id);
        ! $menu && paramError('菜单不存在', 404);

        if ($dto->menuCode && $dto->menuCode !== $menu->menu_code) {
            ! $this->menuModel->isFieldUnique(['menu_code' => $dto->menuCode], $dto->id)
                && paramError("菜单标识码 {$dto->menuCode} 已存在");
        }

        if ($dto->parentId !== null) {
            $dto->parentId === $dto->id && paramError('不能将自己设为父菜单');
            if ($dto->parentId > 0) {
                ! $this->menuModel->where('id', $dto->parentId)->exists()
                    && paramError("父级菜单ID {$dto->parentId} 不存在");
            }
        }

        // 目录类型不能有 permission_code
        if (in_array($dto->type, [0, '0', 'directory'], true)) {
            ! empty($dto->permissionCodes) && paramError('目录类型不能设置权限标识');
        } elseif (! empty($dto->permissionCodes)) {
            // 非目录类型且提供了 permission_codes 时验证
            empty($dto->permissionCodes) && paramError('非目录类型必须设置权限标识');
        }

        Db::beginTransaction();
        try {
            $updateData = $dto->toDbArrayFiltered();
            unset($updateData['id'], $updateData['permission_codes']);

            // permission_codes 存储为逗号分隔的字符串
            if (array_key_exists('permission_codes', $updateData)) {
                $updateData['permission_code'] = ! empty($dto->permissionCodes)
                    ? implode(',', $dto->permissionCodes)
                    : null;
                unset($updateData['permission_codes']);
            }

            $menu->update($updateData);

            $result = $menu->toArray();
            $result['permission_codes'] = ! empty($result['permission_code'])
                ? explode(',', $result['permission_code'])
                : [];

            Db::commit();
            CacheKeys::flush(CacheKeys::MENU_TREE, CacheKeys::MENU_SUPER, CacheKeys::MENU_USER, CacheKeys::PERM_CODES);
            return UpdateMenuDto::dbToFront($result);
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
