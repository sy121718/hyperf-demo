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

namespace App\Dto\Session;

use App\Dto\BaseRequestDto;

/**
 * 在线会话模块请求参数.
 *
 * 会话由登录流程创建，这里只覆盖查询与踢下线。
 */
class SessionRequest extends BaseRequestDto
{
    public ?int $id = null;

    public ?string $username = null;

    public ?array $ids = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'list', 'myList' => [
                ...$this->paginationRules(),
                'username' => 'nullable|string|max:50',
            ],
            'kickout' => [
                'id' => 'required|integer|min:1',
            ],
            'batchKickout' => [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|min:1',
            ],
            default => [],
        };
    }

    protected function virtualFields(): array
    {
        return ['id', 'username', 'ids'];
    }
}
