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

class UploadAttachmentDto extends BaseDto
{
    protected static array $fields = [
        'file_name' => 'file_name',
        'file_path' => 'file_path',
        'file_size' => 'file_size',
        'file_type' => 'file_type',
        'mime_type' => 'mime_type',
        'storage_type' => 'storage_type',
        'storage_path' => 'storage_path',
        'url' => 'url',
        'md5' => 'md5',
        'extra_info' => 'extra_info',
        'category_id' => 'category_id',
    ];

    public function __construct(
        public readonly string $fileName,
        public readonly string $filePath,
        public readonly int $fileSize,
        public readonly string $fileType,
        public readonly ?string $mimeType = null,
        public readonly string $storageType = 'local',
        public readonly ?string $storagePath = null,
        public readonly ?string $url = null,
        public readonly ?string $md5 = null,
        public readonly array $extraInfo = [],
        public readonly ?int $categoryId = null,
    ) {
    }
}
