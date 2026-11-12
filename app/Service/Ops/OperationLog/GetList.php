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

class GetList
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
            ->when($resultValue !== null, fn ($q) => $q->where('result', $resultValue))
            // result 已映射成 1/0，不能再交给通用 filter 按 success/failed 原文比较
            ->filter(array_diff_key($request->toQueryArray(), ['result' => true]))
            ->when($request->search, fn ($q) => $q->whereAny(
                $this->operationLogModel->getSearchableFields(),
                'like',
                "%{$request->search}%"
            ))
            ->when($request->date_from, fn ($q, $v) => $q->where('create_time', '>=', $v . ' 00:00:00.000'))
            ->when($request->date_to, fn ($q, $v) => $q->where('create_time', '<=', $v . ' 23:59:59.999'))
            ->sort($request->toQueryArray())
            ->paginate($request->pageSize(), ['*'], 'page', $request->pageNumber());
    }
}
