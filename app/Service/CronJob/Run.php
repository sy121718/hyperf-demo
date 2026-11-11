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
use Symfony\Component\Process\Process;
use Throwable;

class Run
{
    public function __construct(
        protected SysCronJobModel $cronJobModel,
    ) {
    }

    public function handle(int $id): array
    {
        $job = $this->cronJobModel->find($id);
        ! $job && notFound('定时任务');

        if ($job->status != 1) {
            paramError('任务未启用，无法执行');
        }

        try {
            $process = new Process(explode(' ', $job->command));
            $process->setTimeout(120);
            $process->run();

            return [
                'job_id' => $job->id,
                'job_name' => $job->job_name,
                'exit_code' => $process->getExitCode(),
                'output' => $process->getOutput(),
                'error_output' => $process->getErrorOutput(),
                'success' => $process->isSuccessful(),
            ];
        } catch (Throwable $e) {
            paramError('执行任务失败: ' . $e->getMessage());
        }
    }
}
