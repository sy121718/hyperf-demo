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

namespace App\Service\Ops\LoginLog;

use App\Dto\Log\LogRequest;
use App\Model\Admin\SysLoginLogModel;

/**
 * 我的登录日志：只查当前登录用户本人的记录。
 */
class GetMyList
{
    public function __construct(
        protected SysLoginLogModel $loginLogModel,
    ) {
    }

    public function handle(LogRequest $request): mixed
    {
        return $this->loginLogModel
            ->where('user_id', (int) currentUser('id'))
            // status 0 表示失败，不能用真值判断，必须显式比对 null
            ->when($request->status !== null, fn ($q) => $q->where('status', $request->status))
            ->when($request->ip, fn ($q, $v) => $q->where('ip_address', 'like', "%{$v}%"))
            ->when($request->date_from, fn ($q, $v) => $q->where('login_time', '>=', $v . ' 00:00:00.000'))
            ->when($request->date_to, fn ($q, $v) => $q->where('login_time', '<=', $v . ' 23:59:59.999'))
            ->orderBy('login_time', 'desc')
            ->paginate($request->pageSize(), ['*'], 'page', $request->pageNumber());
    }
}
