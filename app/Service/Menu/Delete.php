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
use Hyperf\DbConnection\Db;
use Throwable;

class Delete
{
    public function __construct(
        protected SysMenuModel $menuModel,
    ) {
    }

    public function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $id > 0 || paramError('菜单ID不能为空');

        $menu = $this->menuModel->find($id);
        ! $menu && paramError('菜单不存在', 404);

        Db::beginTransaction();
        try {
            $childIds = $this->getMenuAndChildrenIds($id);

            $this->menuModel->whereIn('id', $childIds)->delete();

            Db::commit();
            CacheKeys::flush(CacheKeys::MENU_TREE, CacheKeys::MENU_SUPER, CacheKeys::MENU_USER, CacheKeys::PERM_USER);
            return true;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    private function getMenuAndChildrenIds(int $menuId): array
    {
        $ids = [$menuId];
        $children = $this->menuModel->where('parent_id', $menuId)->pluck('id')->toArray();
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getMenuAndChildrenIds($childId));
        }
        return $ids;
    }
}
