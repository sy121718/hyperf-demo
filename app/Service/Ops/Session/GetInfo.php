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
        requireId($id, '会话');

        $session = $this->sessionModel->with(['admin:id,username,name'])->find($id);
        ! $session && notFound('会话');

        return $session->toArray();
    }
}
