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

class Update
{
    public function __construct(
        protected SysCronJobModel $cronJobModel,
    ) {
    }

    public function handle(CronJobRequest $request): array
    {
        $id = (int) $request->id;
        $job = $this->cronJobModel->find($id);
        ! $job && notFound('任务');

        $job->update($request->toDbArrayFiltered());

        return $job->fresh()->toArray();
    }
}
