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
use App\Service\CronJob\Dto\CreateCronJobDto;

class Create
{
    public function __construct(
        protected SysCronJobModel $cronJobModel,
    ) {
    }

    public function handle(CreateCronJobDto $dto): array
    {
        empty($dto->jobName) && paramError('任务名称不能为空');
        empty($dto->cronExpression) && paramError('Cron表达式不能为空');
        empty($dto->command) && paramError('执行命令不能为空');

        $dbData = $dto->toDbArray();
        $dbData['job_category'] = $dbData['job_category'] ?? 'default';
        $dbData['sort_order'] = $dbData['sort_order'] ?? 0;

        $job = $this->cronJobModel->create($dbData);

        return $job->fresh()->toArray();
    }
}
