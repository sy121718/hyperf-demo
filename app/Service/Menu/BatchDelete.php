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

class BatchDelete
{
    public function __construct(
        protected SysMenuModel $menuModel,
    ) {
    }

    public function handle(MenuRequest $request): int
    {
        $ids = $request->ids ?? [];
        empty($ids) && paramError('请选择要删除的菜单');

        Db::beginTransaction();
        try {
            $deletedCount = 0;
            foreach ($ids as $id) {
                $menu = $this->menuModel->find($id);
                if (! $menu) {
                    continue;
                }

                $isDirectory = ($menu->type === 'directory' || $menu->type === 0);
                if ($isDirectory) {
                    $this->menuModel->where('parent_id', $id)->update(['parent_id' => 0]);
                    $deletedCount += $this->menuModel->where('id', $id)->delete();
                } else {
                    $menuIds = $this->getMenuAndChildrenIds($id);
                    $deletedCount += $this->menuModel->whereIn('id', $menuIds)->delete();
                }
            }

            Db::commit();
            CacheKeys::flush(CacheKeys::MENU_TREE, CacheKeys::MENU_SUPER, CacheKeys::MENU_USER, CacheKeys::PERM_CODES);
            return $deletedCount;
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
