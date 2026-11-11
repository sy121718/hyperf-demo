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

use App\Model\Rbac\SysMenuModel;

class GetInfo
{
    public function __construct(
        protected SysMenuModel $menuModel,
    ) {
    }

    public function handle(int $id): array
    {
        $menu = $this->menuModel->findOrNotFound($id, '菜单');

        return $menu->toArray();
    }
}
