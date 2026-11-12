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

class GetList
{
    public function __construct(
        protected SysAdminSessionsModel $sessionModel,
    ) {
    }

    public function handle(SessionRequest $request): mixed
    {
        return $this->sessionModel
            // 会话列表要显示「是谁的会话」，不预加载前端只能看到 admin_id
            ->with(['admin:id,username,name'])
            ->where('status', 1)
            ->when($request->username, fn ($q, $v) => $q->whereHas(
                'admin',
                fn ($sub) => $sub->where('username', 'like', "%{$v}%")
            ))
            ->orderBy('last_active_time', 'desc')
            ->paginate($request->pageSize(), ['*'], 'page', $request->pageNumber());
    }
}
