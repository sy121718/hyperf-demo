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

namespace App\Service\Ops\OperationLog;

use App\Dto\Log\LogRequest;
use App\Model\Admin\SysOperationLogModel;

/**
 * 我的操作日志：只查当前登录用户本人的操作记录。
 */
class GetMyList
{
    public function __construct(
        protected SysOperationLogModel $operationLogModel,
    ) {
    }

    public function handle(LogRequest $request): mixed
    {
        // 接口层用 success / failed，库里存 1 / 0
        $resultValue = match ($request->result) {
            'success' => 1,
            'failed' => 0,
            default => null,
        };

        return $this->operationLogModel
            ->where('admin_id', (int) currentUser('id'))
            ->when($resultValue !== null, fn ($q) => $q->where('result', $resultValue))
            ->when($request->ip, fn ($q, $v) => $q->where('ip', 'like', "%{$v}%"))
            ->when($request->date_from, fn ($q, $v) => $q->where('create_time', '>=', $v . ' 00:00:00.000'))
            ->when($request->date_to, fn ($q, $v) => $q->where('create_time', '<=', $v . ' 23:59:59.999'))
            ->orderBy('create_time', 'desc')
            ->paginate($request->pageSize(), ['*'], 'page', $request->pageNumber());
    }
}
