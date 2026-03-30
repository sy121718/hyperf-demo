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
use App\Model\FileCategory\SysFileCategoryModel;
use App\Service\Attachment\Dto\UpdateAttachmentDto;

class Update
{
    public function __construct(
        protected SysAttachmentModel $attachmentModel,
    ) {
    }

    public function handle(UpdateAttachmentDto $dto): array
    {
        $attachment = $this->attachmentModel->find($dto->id);
        ! $attachment && paramError('附件不存在');

        if ($dto->categoryId !== null && $dto->categoryId > 0) {
            $categoryExists = SysFileCategoryModel::where('id', $dto->categoryId)->exists();
            ! $categoryExists && paramError('文件分类不存在');
        }

        $updateData = $dto->toDbArrayFiltered();
        unset($updateData['id']);

        $attachment->update($updateData);

        return $attachment->fresh()->toArray();
    }
}
