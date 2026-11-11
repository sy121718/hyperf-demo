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

namespace App\Dto\Cache;

use App\Dto\BaseRequestDto;

/**
 * 缓存管理请求参数.
 *
 * clear 不传 modules 表示全部清除。
 */
class CacheRequest extends BaseRequestDto
{
    /**
     * 要清除的缓存模块，留空表示全部.
     *
     * @var null|array<int, string>
     */
    public ?array $modules = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'clear' => [
                'modules' => 'nullable|array',
                'modules.*' => 'string|max:50',
            ],
            default => [],
        };
    }
}
