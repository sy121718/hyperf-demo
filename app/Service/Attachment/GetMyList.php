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

/**
 * 我的附件：只查当前登录用户上传的记录。
 *
 * 数据隔离靠 create_by 条件完成，不依赖接口权限——
 * 能否查看全量由 PermissionMiddleware 的 attachment:list 决定。
 */
class GetMyList
{
    public function __construct(
        protected SysAttachmentModel $attachmentModel,
    ) {
    }

    public function handle(AttachmentRequest $request): mixed
    {
        return $this->attachmentModel
            ->where('create_by', (int) currentUser('id'))
            ->filter($request->toQueryArray())
            ->when($request->search, fn ($q) => $q->whereAny($this->attachmentModel->getSearchableFields(), 'like', "%{$request->search}%"))
            ->sort($request->toQueryArray())
            ->paginate($request->pageSize(), ['*'], 'page', $request->pageNumber());
    }
}
