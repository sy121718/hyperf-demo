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
use App\Service\Attachment\Dto\UploadAttachmentDto;

class Upload
{
    public function __construct(
        protected SysAttachmentModel $attachmentModel,
    ) {
    }

    public function handle(UploadAttachmentDto $data): array
    {
        empty($data->fileName) && paramError('文件名不能为空');
        empty($data->filePath) && paramError('文件路径不能为空');
        empty($data->fileType) && paramError('文件类型不能为空');

        $dbData = $data->toDbArray();
        $dbData['status'] = 1;

        $attachment = $this->attachmentModel->create($dbData);

        return ['id' => $attachment->id];
    }
}
