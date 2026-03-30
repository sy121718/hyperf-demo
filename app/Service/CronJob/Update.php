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
use App\Service\CronJob\Dto\UpdateCronJobDto;

class Update
{
    public function __construct(
        protected SysCronJobModel $cronJobModel,
    ) {
    }

    public function handle(UpdateCronJobDto $dto): array
    {
        $job = $this->cronJobModel->find($dto->id);
        ! $job && paramError('任务不存在');

        $updateData = $dto->toDbArrayFiltered();
        unset($updateData['id']);

        $job->update($updateData);

        return $job->fresh()->toArray();
    }
}
