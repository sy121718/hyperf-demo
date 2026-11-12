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
use App\Model\Rbac\SysMenuModel;
use Hyperf\DbConnection\Db;
use Throwable;

class Delete
{
    public function __construct(
        protected SysMenuModel $menuModel,
    ) {
    }

    public function handle(int $id): bool
    {
        $menu = $this->menuModel->findOrNotFound($id, '菜单');

        Db::beginTransaction();
        try {
            $childIds = $this->getMenuAndChildrenIds($id);

            $this->menuModel->whereIn('id', $childIds)->delete();

            Db::commit();
            // 菜单变更不影响任何人的权限码集合，无需清 PERM_CODES
            CacheKeys::flushMenuRelated();
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
