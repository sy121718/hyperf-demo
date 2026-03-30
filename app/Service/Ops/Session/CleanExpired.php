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

class CleanExpired
{
    public function __construct(
        protected SysAdminSessionsModel $sessionModel,
    ) {
    }

    public function handle(): int
    {
        return $this->sessionModel
            ->where('expires_time', '<', date('Y-m-d H:i:s.v'))
            ->where('status', 1)
            ->update(['status' => 0]);
    }
}
