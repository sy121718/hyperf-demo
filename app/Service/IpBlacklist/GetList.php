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

namespace App\Service\IpBlacklist;

use App\Dto\IpBlacklist\IpBlacklistRequest;
use App\Model\System\IpBlacklistModel;

class GetList
{
    public function __construct(
        protected IpBlacklistModel $model,
    ) {
    }

    public function handle(IpBlacklistRequest $request): mixed
    {
        return $this->model
            ->when($request->ip_address, fn ($q, $v) => $q->where('ip_address', 'like', "%{$v}%"))
            ->when($request->error_type !== null, fn ($q) => $q->where('error_type', $request->error_type))
            ->when($request->status !== null, fn ($q) => $q->where('status', $request->status))
            ->when($request->start_date, fn ($q, $v) => $q->where('banned_time', '>=', $v))
            ->when($request->end_date, fn ($q, $v) => $q->where('banned_time', '<=', $v))
            ->orderByDesc('banned_time')
            ->paginate($request->pageSize(), ['*'], 'page', $request->pageNumber());
    }
}
