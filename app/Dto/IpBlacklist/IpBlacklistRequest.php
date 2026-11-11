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

namespace App\Dto\IpBlacklist;

use App\Dto\BaseRequestDto;

/**
 * IP 黑名单模块请求参数.
 */
class IpBlacklistRequest extends BaseRequestDto
{
    public ?int $id = null;

    public ?string $ip_address = null;

    public ?string $error_type = null;

    public ?string $ban_reason = null;

    public ?int $ban_duration = null;

    public ?string $remark = null;

    public ?int $status = null;

    /**
     * 列表页按拉黑时间区间筛选.
     */
    public ?string $start_date = null;

    public ?string $end_date = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'ban' => [
                'ip_address' => 'required|ip',
                'error_type' => 'nullable|string|max:50',
                'ban_reason' => 'nullable|string|max:500',
                'ban_duration' => 'nullable|integer|min:1|max:525600',
                'remark' => 'nullable|string|max:500',
            ],
            'unban' => [
                'id' => 'required|integer|min:1',
            ],
            'check' => [
                'ip_address' => 'required|ip',
            ],
            'list' => [
                ...$this->paginationRules(),
                'ip_address' => 'nullable|string|max:45',
                'error_type' => 'nullable|string|max:50',
                'status' => 'nullable|in:0,1,2',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ],
            default => [],
        };
    }

    protected function virtualFields(): array
    {
        return ['id', 'start_date', 'end_date'];
    }
}
