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

use App\Model\CronJob\SysCronJobModel;

class GetList
{
    public function __construct(
        protected SysCronJobModel $cronJobModel,
    ) {
    }

    public function handle(array $data): mixed
    {
        $search = $data['search'] ?? null;
        $withDetail = ($data['with'] ?? '') === 'detail';

        // 列表页只查必要字段
        $fields = $withDetail ? ['*'] : [
            'id', 'job_name', 'job_category', 'command', 'cron_expression', 'status', 'last_sync_time', 'create_time',
        ];

        return $this->cronJobModel
            ->filter($data)
            ->when($search, fn ($q) => $q->whereAny($this->cronJobModel->getSearchableFields(), 'like', "%{$search}%"))
            ->sort($data)
            ->paginate((int) ($data['limit'] ?? 10), $fields, 'page', (int) ($data['page'] ?? 1));
    }
}
