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

use App\Model\Menu\SysMenuModel;

class GetInfo
{
    public function __construct(
        protected SysMenuModel $menuModel,
    ) {
    }

    public function handle(int $id): array
    {
        $id > 0 || paramError('菜单ID不能为空');

        $menu = $this->menuModel
            ->with(['permissions'])
            ->find($id);

        ! $menu && paramError('菜单不存在', 404);

        return $menu->toArray();
    }
}
