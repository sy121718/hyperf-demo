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

namespace App\Service\Ops\Session;

use App\Model\Admin\SysAdminSessionsModel;

class GetInfo
{
    public function __construct(
        protected SysAdminSessionsModel $sessionModel,
    ) {
    }

    public function handle(int $id): array
    {
        $id > 0 || paramError('会话ID不能为空');

        $session = $this->sessionModel->with(['admin:id,username,name'])->find($id);
        ! $session && paramError('会话不存在', 404);

        return $session->toArray();
    }
}
