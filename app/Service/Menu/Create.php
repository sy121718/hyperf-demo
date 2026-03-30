<?php

declare(strict_types=1);

namespace App\Service\Menu;

use App\Common\CacheKeys;
use App\Model\Menu\SysMenuModel;
use App\Service\Menu\Dto\CreateMenuDto;
use Hyperf\DbConnection\Db;

class Create
{
    public function __construct(
        protected SysMenuModel $menuModel,
    ) {}

    public function handle(CreateMenuDto $dto): array
    {
        !$this->menuModel->isFieldUnique(['menu_code' => $dto->menuCode])
            && paramError("菜单标识码 {$dto->menuCode} 已存在");

        if ($dto->parentId && $dto->parentId > 0) {
            !$this->menuModel->where('id', $dto->parentId)->exists()
                && paramError("父级菜单ID {$dto->parentId} 不存在");
        }

        // 目录类型不能有 permission_code
        if (in_array($dto->type, [0, '0', 'directory'], true)) {
            !empty($dto->permissionCodes) && paramError('目录类型不能设置权限标识');
        } else {
            // 非目录类型必须提供 permission_codes
            empty($dto->permissionCodes) && paramError('非目录类型必须设置权限标识');
        }

        Db::beginTransaction();
        try {
            $dbData = $dto->toDbArray();
            unset($dbData['permission_codes']);

            // permission_codes 存储为逗号分隔的字符串
            $dbData['permission_code'] = !empty($dto->permissionCodes)
                ? implode(',', $dto->permissionCodes)
                : null;
            $dbData['parent_id'] = $dbData['parent_id'] ?? 0;

            $menu = $this->menuModel->create($dbData);

            $result = $menu->toArray();
            $result['permission_codes'] = !empty($result['permission_code'])
                ? explode(',', $result['permission_code'])
                : [];

            Db::commit();
            CacheKeys::flush(CacheKeys::MENU_TREE, CacheKeys::MENU_SUPER, CacheKeys::MENU_USER);
            return CreateMenuDto::dbToFront($result);
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
