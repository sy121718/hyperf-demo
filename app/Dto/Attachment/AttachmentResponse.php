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

namespace App\Dto\Attachment;

use App\Dto\BaseResponse;

/**
 * 附件模块响应字段白名单.
 */
class AttachmentResponse extends BaseResponse
{
    protected static function listFields(): array
    {
        return [
            'id', 'category_id', 'file_name', 'file_path', 'file_size',
            'file_type', 'mime_type', 'storage_type', 'url', 'status', 'create_time',
        ];
    }

    protected static function detailFields(): array
    {
        return [
            'id', 'category_id', 'file_name', 'original_name', 'file_path', 'file_size',
            'file_type', 'mime_type', 'storage_type', 'storage_path', 'url',
            'width', 'height', 'md5', 'sha256', 'extra_info', 'status',
            'create_time', 'update_time',
        ];
    }

    protected static function savedFields(): array
    {
        return ['id', 'file_name', 'url'];
    }
}
