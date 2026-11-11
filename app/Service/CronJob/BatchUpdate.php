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
use Hyperf\DbConnection\Db;
use Throwable;

class BatchUpdate
{
    public function __construct(
        protected SysCronJobModel $cronJobModel,
    ) {
    }

    public function handle(CronJobRequest $request): int
    {
        $ids = $request->ids ?? [];
        empty($ids) && paramError('请选择要更新的任务');

        $updateData = array_filter([
            'status' => $request->status,
            'job_category' => $request->job_category,
        ], static fn ($v) => $v !== null);

        empty($updateData) && paramError('没有可更新的字段');

        Db::beginTransaction();
        try {
            $result = $this->cronJobModel->whereIn('id', $ids)->update($updateData);
            Db::commit();
            return $result;
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
