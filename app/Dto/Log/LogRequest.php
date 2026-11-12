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

namespace App\Dto\Log;

use App\Dto\BaseRequestDto;

/**
 * 日志模块请求参数（登录日志 / 操作日志，均为只读列表）.
 */
class LogRequest extends BaseRequestDto
{
    /**
     * 登录日志按 user_id 过滤.
     */
    public ?int $user_id = null;

    /**
     * 操作日志按 admin_id 过滤.
     */
    public ?int $admin_id = null;

    public ?string $username = null;

    /**
     * 操作日志结果：success / failed，落库前转成 1 / 0.
     */
    public ?string $result = null;

    /**
     * 登录日志结果：0 失败 / 1 成功.
     */
    public ?int $status = null;

    public ?string $ip = null;

    public ?string $http_method = null;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'loginLog', 'myLoginLog' => [
                ...$this->paginationRules(),
                'user_id' => 'nullable|integer|min:1',
                'username' => 'nullable|string|max:50',
                'ip' => 'nullable|string|max:50',
                'status' => 'nullable|in:0,1',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
            ],
            'operationLog', 'myOperationLog' => [
                ...$this->paginationRules(),
                'admin_id' => 'nullable|integer|min:1',
                'result' => 'nullable|string|in:success,failed',
                'ip' => 'nullable|string|max:50',
                'http_method' => 'nullable|string|max:10',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
            ],
            default => [],
        };
    }

    protected function virtualFields(): array
    {
        return ['user_id', 'admin_id', 'username', 'result', 'status', 'ip', 'http_method', 'date_from', 'date_to'];
    }
}
