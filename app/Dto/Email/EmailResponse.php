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

use App\Dto\BaseResponse;

/**
 * 邮件模块响应字段白名单.
 *
 * 发送记录与邮件模板字段不同，两张表取并集，缺失的键会被自动跳过。
 */
class EmailResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return [
            // 发送记录
            'id', 'template_id', 'subject', 'status', 'total_recipients',
            'success_count', 'failed_count', 'error_message', 'sent_time', 'create_time',
            // 邮件模板
            'template_name', 'template_code', 'template_type', 'variables',
        ];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'template_id', 'subject', 'content', 'status', 'total_recipients',
            'success_count', 'failed_count', 'error_message', 'sent_time',
            'template_name', 'template_code', 'template_type', 'variables',
            'create_time', 'update_time',
        ];
    }

    protected static function savedFields(): array
    {
        return ['id', 'template_code', 'template_name', 'status'];
    }
}
