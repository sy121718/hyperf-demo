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

namespace App\Dto\Email;

use App\Dto\BaseRequestDto;

/**
 * 邮件模块请求参数（发送 + 模板）.
 *
 * 收件人支持两种写法：to 单个，to_list 批量；后者优先。
 */
class EmailRequest extends BaseRequestDto
{
    public ?int $id = null;

    // ==================== 发送相关 ====================

    public ?string $to = null;

    /**
     * @var null|array<int, string>
     */
    public ?array $to_list = null;

    public ?string $subject = null;

    public ?string $content = null;

    public ?int $template_id = null;

    // ==================== 模板相关 ====================

    public ?string $template_name = null;

    public ?string $template_code = null;

    public ?string $template_type = null;

    /**
     * 模板变量声明，形如 {"name": "用户名"}.
     */
    public ?array $variables = null;

    public ?int $status = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'send' => [
                'subject' => 'required|string|max:200',
                'content' => 'required|string',
                'to' => 'nullable|email|max:100',
                'to_list' => 'nullable|array|min:1',
                'to_list.*' => 'email|max:100',
                'template_id' => 'nullable|integer|min:1',
            ],
            'testSend' => [
                'to' => 'required|email|max:100',
            ],
            'sendRecords' => [
                ...$this->paginationRules(),
                'status' => 'nullable|in:0,1,2,3',
                'template_id' => 'nullable|integer|min:1',
            ],
            'templateList' => [
                ...$this->paginationRules(),
                'template_type' => 'nullable|string|max:20',
                'status' => 'nullable|in:0,1',
            ],
            'templateCreate' => [
                'template_name' => 'required|string|max:100',
                'template_code' => 'required|string|max:50',
                // subject/content 在库里是 NOT NULL 且无默认值，必须先卡住
                'subject' => 'required|string|max:200',
                'content' => 'required|string',
                'template_type' => 'nullable|string|max:20',
                'variables' => 'nullable|array',
                'status' => 'nullable|in:0,1',
            ],
            'templateUpdate' => [
                'id' => 'required|integer|min:1',
                'template_name' => 'nullable|string|max:100',
                'template_code' => 'nullable|string|max:50',
                'subject' => 'nullable|string|max:200',
                'content' => 'nullable|string',
                'template_type' => 'nullable|string|max:20',
                'variables' => 'nullable|array',
                'status' => 'nullable|in:0,1',
            ],
            default => [],
        };
    }

    /**
     * 收件人列表：to 单个时包成数组，to_list 优先.
     *
     * @return array<int, string>
     */
    public function recipients(): array
    {
        if (! empty($this->to_list)) {
            return $this->to_list;
        }

        return $this->to === null ? [] : [$this->to];
    }

    protected function virtualFields(): array
    {
        return ['id', 'to', 'to_list'];
    }
}
