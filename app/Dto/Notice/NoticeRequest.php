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

namespace App\Dto\Notice;

use App\Dto\BaseRequestDto;

/**
 * 通知公告模块请求参数.
 *
 * 单条 CRUD 与发布/定向/已读等动作共用这一个类，按路由动作切换规则。
 */
class NoticeRequest extends BaseRequestDto
{
    public ?int $id = null;

    public ?string $title = null;

    public ?string $content = null;

    public ?int $notice_type = null;

    public ?int $is_top = null;

    public ?int $is_popup = null;

    public ?string $publish_time = null;

    /**
     * 过期时间，可能传时间戳或日期字符串，保留原始类型。
     */
    public mixed $expire_time = null;

    public ?int $status = null;

    public ?int $notice_id = null;

    public ?int $user_id = null;

    public ?int $target_type = null;

    public ?array $target_ids = null;

    public ?array $target_users = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'create' => [
                'title' => 'required|string|max:200',
                'content' => 'nullable|string',
                'notice_type' => 'nullable|integer|min:0',
                'is_top' => 'nullable|in:0,1',
                'is_popup' => 'nullable|in:0,1',
                'publish_time' => 'nullable|string|max:30',
                'expire_time' => 'nullable',
                'status' => 'nullable|in:0,1,2',
            ],
            'update' => [
                'id' => 'required|integer|min:1',
                'title' => 'nullable|string|max:200',
                'content' => 'nullable|string',
                'notice_type' => 'nullable|integer|min:0',
                'is_top' => 'nullable|in:0,1',
                'is_popup' => 'nullable|in:0,1',
                'publish_time' => 'nullable|string|max:30',
                'expire_time' => 'nullable',
                'status' => 'nullable|in:0,1,2',
            ],
            'publish', 'withdraw' => [
                'id' => 'required|integer|min:1',
                'publish_time' => 'nullable|string|max:30',
            ],
            'setTargets' => [
                'id' => 'required|integer|min:1',
                'target_type' => 'required|integer|min:1',
                'target_ids' => 'nullable|array',
                'target_ids.*' => 'integer|min:1',
            ],
            'markRead' => [
                'notice_id' => 'required|integer|min:1',
                'user_id' => 'required|integer|min:1',
            ],
            'batchPush' => [
                'notice_id' => 'required|integer|min:1',
                'target_users' => 'required|array|min:1',
                'target_users.*' => 'integer|min:1',
            ],
            'statistics' => [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ],
            'userNotices' => [
                ...$this->paginationRules(),
                'notice_type' => 'nullable|integer|min:0',
            ],
            default => [],
        };
    }

    protected function virtualFields(): array
    {
        return [
            'id', 'notice_id', 'user_id', 'target_type', 'target_ids',
            'target_users', 'start_date', 'end_date',
        ];
    }
}
