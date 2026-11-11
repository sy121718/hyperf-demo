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

namespace App\Dto\CronJob;

use App\Dto\BaseResponse;

/**
 * 定时任务模块响应字段白名单.
 */
class CronJobResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return [
            'id', 'job_name', 'job_category', 'command', 'cron_expression',
            'status', 'last_sync_time', 'create_time',
        ];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'job_name', 'job_category', 'description', 'cron_expression',
            'command', 'status', 'sort_order', 'last_sync_time',
            'create_time', 'update_time',
        ];
    }

    protected static function savedFields(): array
    {
        return ['id', 'job_name'];
    }
}
