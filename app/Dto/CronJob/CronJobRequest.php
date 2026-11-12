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

use App\Dto\BaseRequestDto;

/**
 * 定时任务模块请求参数.
 */
class CronJobRequest extends BaseRequestDto
{
    public ?int $id = null;

    public ?string $job_name = null;

    public ?string $job_category = null;

    public ?string $description = null;

    public ?string $cron_expression = null;

    public ?string $command = null;

    public ?int $status = null;

    public ?int $sort_order = null;

    /**
     * 批量更新涉及的任务 ID 集合.
     */
    public ?array $ids = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'create' => [
                'job_name' => 'required|string|max:100',
                'cron_expression' => 'required|string|max:100',
                'command' => 'required|string|max:500',
                'job_category' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'status' => 'nullable|in:0,1',
                'sort_order' => 'nullable|integer|min:0',
            ],
            'update' => [
                'id' => 'required|integer|min:1',
                'job_name' => 'nullable|string|max:100',
                'cron_expression' => 'nullable|string|max:100',
                'command' => 'nullable|string|max:500',
                'job_category' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'status' => 'nullable|in:0,1',
                'sort_order' => 'nullable|integer|min:0',
            ],
            'list' => [
                ...$this->paginationRules(),
                'job_category' => 'nullable|string|max:50',
                'status' => 'nullable|in:0,1',
            ],
            'batchUpdate' => [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|min:1',
                'status' => 'nullable|in:0,1',
                'job_category' => 'nullable|string|max:50',
            ],
            default => [],
        };
    }

    protected function virtualFields(): array
    {
        return ['id', 'ids'];
    }
}
