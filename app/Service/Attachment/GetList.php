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

use App\Model\Attachment\SysAttachmentModel;

class GetList
{
    public function __construct(
        protected SysAttachmentModel $attachmentModel,
    ) {
    }

    public function handle(array $data): mixed
    {
        $search = $data['search'] ?? null;
        $withDetail = ($data['with'] ?? '') === 'detail';

        // 列表页只查必要字段，详情页才加载关联
        $fields = $withDetail ? ['*'] : [
            'id', 'category_id', 'file_name', 'file_path', 'file_size',
            'file_type', 'mime_type', 'storage_type', 'status', 'create_time',
        ];

        $query = $this->attachmentModel
            ->filter($data)
            ->when($search, fn ($q) => $q->whereAny($this->attachmentModel->getSearchableFields(), 'like', "%{$search}%"))
            ->sort($data);

        // 条件加载关联
        if ($withDetail) {
            $query->with(['category', 'creator']);
        }

        return $query->paginate((int) ($data['limit'] ?? 10), $fields, 'page', (int) ($data['page'] ?? 1));
    }
}
