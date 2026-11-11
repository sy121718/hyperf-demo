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
use App\Model\FileCategory\SysFileCategoryModel;

class Update
{
    public function __construct(
        protected SysAttachmentModel $attachmentModel,
    ) {
    }

    public function handle(AttachmentRequest $request): array
    {
        $id = (int) $request->id;
        $attachment = $this->attachmentModel->find($id);
        ! $attachment && paramError('附件不存在', 404);

        if ($request->category_id !== null && $request->category_id > 0) {
            ! SysFileCategoryModel::where('id', $request->category_id)->exists()
                && paramError('文件分类不存在');
        }

        $attachment->update($request->toDbArrayFiltered());

        return $attachment->fresh()->toArray();
    }
}
