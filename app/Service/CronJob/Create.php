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

class Create
{
    public function __construct(
        protected SysCronJobModel $cronJobModel,
    ) {
    }

    public function handle(CronJobRequest $request): array
    {
        $dbData = $request->toDbArrayFiltered();
        $dbData['job_category'] = $dbData['job_category'] ?? 'default';
        $dbData['sort_order'] = $dbData['sort_order'] ?? 0;

        $job = $this->cronJobModel->create($dbData);

        return $job->fresh()->toArray();
    }
}
