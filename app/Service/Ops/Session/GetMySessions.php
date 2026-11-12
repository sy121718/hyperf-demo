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

use App\Dto\Session\SessionRequest;
use App\Model\Admin\SysAdminSessionsModel;

/**
 * 我的登录会话：只查当前登录用户自己的设备，跳过 username 筛选。
 */
class GetMySessions
{
    public function __construct(
        protected SysAdminSessionsModel $sessionModel,
    ) {
    }

    public function handle(SessionRequest $request): mixed
    {
        return $this->sessionModel
            ->where('admin_id', (int) currentUser('id'))
            ->where('status', 1)
            ->orderBy('last_active_time', 'desc')
            ->paginate($request->pageSize(), ['*'], 'page', $request->pageNumber());
    }
}
