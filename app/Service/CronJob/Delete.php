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

class Delete
{
    public function __construct(
        protected SysCronJobModel $cronJobModel,
    ) {
    }

    public function handle(int $id): bool
    {
        $job = $this->cronJobModel->find($id);
        ! $job && notFound('任务');

        $job->delete();

        return true;
    }
}
