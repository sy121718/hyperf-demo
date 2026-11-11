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

namespace App\Dto\Config;

use App\Dto\BaseRequestDto;

/**
 * 系统配置模块请求参数.
 *
 * config_data 是一组键值对，整体落库到 sys_config.config_data 这个 json 列。
 */
class ConfigRequest extends BaseRequestDto
{
    public ?int $id = null;

    public ?string $group_key = null;

    public ?string $group_name = null;

    public ?array $config_data = null;

    public ?string $remark = null;

    public ?int $status = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'create' => [
                'group_key' => 'required|string|max:50',
                'group_name' => 'required|string|max:100',
                'config_data' => 'nullable|array',
                'remark' => 'nullable|string|max:500',
                'status' => 'nullable|in:0,1',
            ],
            'update' => [
                'id' => 'required|integer|min:1',
                'group_key' => 'nullable|string|max:50',
                'group_name' => 'nullable|string|max:100',
                'config_data' => 'nullable|array',
                'remark' => 'nullable|string|max:500',
                'status' => 'nullable|in:0,1',
            ],
            'list' => [
                ...$this->paginationRules(),
                'group_key' => 'nullable|string|max:50',
                'status' => 'nullable|in:0,1',
            ],
            'info' => [
                'id' => 'required|integer|min:1',
            ],
            default => [],
        };
    }

    protected function virtualFields(): array
    {
        return ['id'];
    }
}
