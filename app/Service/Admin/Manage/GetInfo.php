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

namespace App\Service\Admin\Manage;

use App\Model\Admin\SysAdminModel;

class GetInfo
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    public function handle(int $id): array
    {
        $id > 0 || paramError('管理员ID不能为空');

        $admin = $this->adminModel
            ->with(['roles', 'sessions'])
            ->find($id);

        ! $admin && paramError('管理员不存在', 404);

        return $admin->toArray();
    }
}
