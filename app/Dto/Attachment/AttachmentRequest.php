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

use App\Dto\BaseRequestDto;

/**
 * 附件模块请求参数.
 */
class AttachmentRequest extends BaseRequestDto
{
    public ?int $id = null;

    public ?string $file_name = null;

    public ?string $file_path = null;

    public ?int $file_size = null;

    public ?string $file_type = null;

    public ?string $mime_type = null;

    public ?string $storage_type = null;

    public ?string $storage_path = null;

    public ?string $url = null;

    public ?string $md5 = null;

    public ?string $sha256 = null;

    public ?int $width = null;

    public ?int $height = null;

    public ?int $category_id = null;

    public ?int $status = null;

    public ?array $extra_info = null;

    public function rules(): array
    {
        return match ($this->getRouteAction()) {
            'upload' => [
                'file_name' => 'required|string|max:255',
                'file_path' => 'required|string|max:500',
                'file_size' => 'required|integer|min:0',
                'file_type' => 'required|string|max:50',
                'mime_type' => 'nullable|string|max:100',
                'storage_type' => 'nullable|string|max:50',
                'storage_path' => 'nullable|string|max:500',
                'url' => 'nullable|string|max:500',
                'md5' => 'nullable|string|max:32',
                'category_id' => 'nullable|integer|min:1',
                'extra_info' => 'nullable|array',
            ],
            'update' => [
                'file_name' => 'nullable|string|max:255',
                'file_type' => 'nullable|string|max:50',
                'storage_type' => 'nullable|string|max:50',
                'status' => 'nullable|in:0,1',
                'category_id' => 'nullable|integer|min:1',
                'mime_type' => 'nullable|string|max:100',
                'storage_path' => 'nullable|string|max:500',
                'url' => 'nullable|string|max:500',
                'extra_info' => 'nullable|array',
            ],
            'list' => [
                ...$this->paginationRules(),
                'category_id' => 'nullable|integer|min:1',
                'file_type' => 'nullable|string|max:50',
                'storage_type' => 'nullable|string|max:50',
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
