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

use App\Dto\BaseResponse;

/**
 * 通知公告模块响应字段白名单.
 */
class NoticeResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return [
            'id', 'title', 'notice_type', 'is_top', 'is_popup', 'status',
            'publish_time', 'expire_time', 'read_count', 'is_read', 'create_time',
        ];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'title', 'content', 'notice_type', 'is_top', 'is_popup',
            'status', 'publish_time', 'expire_time', 'read_count',
            'create_time', 'update_time',
        ];
    }

    protected static function savedFields(): array
    {
        return ['id', 'title'];
    }
}
