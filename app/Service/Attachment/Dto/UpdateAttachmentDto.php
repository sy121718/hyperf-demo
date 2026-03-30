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

namespace App\Service\Attachment\Dto;

use App\Common\Dto\BaseDto;

class UpdateAttachmentDto extends BaseDto
{
    protected static array $fields = [
        'id' => 'id',
        'file_name' => 'file_name',
        'file_type' => 'file_type',
        'storage_type' => 'storage_type',
        'status' => 'status',
        'category_id' => 'category_id',
        'mime_type' => 'mime_type',
        'storage_path' => 'storage_path',
        'url' => 'url',
        'extra_info' => 'extra_info',
    ];

    public function __construct(
        public readonly int $id,
        public readonly ?string $fileName = null,
        public readonly ?string $fileType = null,
        public readonly ?string $storageType = null,
        public readonly ?int $status = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $mimeType = null,
        public readonly ?string $storagePath = null,
        public readonly ?string $url = null,
        public readonly ?array $extraInfo = null,
    ) {
    }
}
