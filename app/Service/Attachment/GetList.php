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

namespace App\Service\Attachment;

use App\Dto\Attachment\AttachmentRequest;
use App\Model\Attachment\SysAttachmentModel;

class GetList
{
    public function __construct(
        protected SysAttachmentModel $attachmentModel,
    ) {
    }

    public function handle(AttachmentRequest $request): mixed
    {
        // 列表页只查必要字段，详情页才加载关联
        $fields = $request->withDetail() ? ['*'] : [
            'id', 'category_id', 'file_name', 'file_path', 'file_size',
            'file_type', 'mime_type', 'storage_type', 'status', 'create_time',
        ];

        $query = $this->attachmentModel
            ->filter($request->toQueryArray())
            ->when($request->search, fn ($q) => $q->whereAny($this->attachmentModel->getSearchableFields(), 'like', "%{$request->search}%"))
            ->sort($request->toQueryArray());

        // 条件加载关联
        if ($request->withDetail()) {
            $query->with(['category', 'creator']);
        }

        return $query->paginate($request->pageSize(), $fields, 'page', $request->pageNumber());
    }
}
