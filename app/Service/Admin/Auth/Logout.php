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

namespace App\Service\Admin\Auth;

use App\Common\CacheKeys;
use App\Model\Admin\SysAdminSessionsModel;
use Hyperf\Context\Context;

class Logout
{
    public function __construct(
        protected SysAdminSessionsModel $sessionsModel,
    ) {
    }

    public function handle(): void
    {
        $adminId = currentUser('id');
        $token = Context::get('auth_token');

        if ($token) {
            $this->sessionsModel
                ->where('admin_id', $adminId)
                ->where('jwt_token', $token)
                ->update(['status' => 0]);

            CacheKeys::delete(CacheKeys::authSession($token));
        } else {
            $this->sessionsModel
                ->where('admin_id', $adminId)
                ->where('status', 1)
                ->update(['status' => 0]);
        }
    }
}
