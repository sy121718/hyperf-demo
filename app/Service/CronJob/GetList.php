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

namespace App\Service\CronJob;

use App\Dto\CronJob\CronJobRequest;
use App\Model\CronJob\SysCronJobModel;

class GetList
{
    public function __construct(
        protected SysCronJobModel $cronJobModel,
    ) {
    }

    public function handle(CronJobRequest $request): mixed
    {
        // 列表页只查必要字段
        $fields = $request->withDetail() ? ['*'] : [
            'id', 'job_name', 'job_category', 'command', 'cron_expression', 'status', 'last_sync_time', 'create_time',
        ];

        return $this->cronJobModel
            ->filter($request->toQueryArray())
            ->when($request->search, fn ($q) => $q->whereAny($this->cronJobModel->getSearchableFields(), 'like', "%{$request->search}%"))
            ->sort($request->toQueryArray())
            ->paginate($request->pageSize(), $fields, 'page', $request->pageNumber());
    }
}
