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

namespace App\Dto\Dict;

use App\Dto\BaseRequestDto;

/**
 * 字典模块请求参数.
 *
 * 只覆盖字典主表；字典项（sys_dict_item）当前没有独立的增删改接口。
 */
class DictRequest extends BaseRequestDto
{
    public ?int $id = null;

    public ?string $dict_name = null;

    public ?string $dict_code = null;

    public ?string $dict_type = null;

    public ?int $status = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'create' => [
                'dict_name' => 'required|string|max:100',
                'dict_code' => 'required|string|max:50',
                'dict_type' => 'nullable|string|max:50',
                'status' => 'nullable|in:0,1',
            ],
            'update' => [
                'id' => 'required|integer|min:1',
                'dict_name' => 'nullable|string|max:100',
                'dict_code' => 'nullable|string|max:50',
                'dict_type' => 'nullable|string|max:50',
                'status' => 'nullable|in:0,1',
            ],
            'tree' => [
                ...$this->paginationRules(),
                'dict_type' => 'nullable|string|max:50',
                'status' => 'nullable|in:0,1',
            ],
            default => [],
        };
    }

    protected function virtualFields(): array
    {
        return ['id'];
    }
}
