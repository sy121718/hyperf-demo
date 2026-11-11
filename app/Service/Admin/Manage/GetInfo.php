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
        $admin = $this->adminModel->findOrNotFound($id, '管理员', ['roles', 'sessions']);

        return $admin->toArray();
    }
}
