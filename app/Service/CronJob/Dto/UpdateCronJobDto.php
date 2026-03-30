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

namespace App\Service\CronJob\Dto;

use App\Common\Dto\BaseDto;

class UpdateCronJobDto extends BaseDto
{
    protected static array $fields = [
        'id' => 'id',
        'job_name' => 'job_name',
        'cron_expression' => 'cron_expression',
        'command' => 'command',
        'job_category' => 'job_category',
        'description' => 'description',
        'status' => 'status',
        'sort_order' => 'sort_order',
    ];

    public function __construct(
        public readonly int $id,
        public readonly ?string $jobName = null,
        public readonly ?string $cronExpression = null,
        public readonly ?string $command = null,
        public readonly ?string $jobCategory = null,
        public readonly ?string $description = null,
        public readonly ?int $status = null,
        public readonly ?int $sortOrder = null,
    ) {
    }
}
