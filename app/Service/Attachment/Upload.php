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

class Upload
{
    public function __construct(
        protected SysAttachmentModel $attachmentModel,
    ) {
    }

    public function handle(AttachmentRequest $request): array
    {
        $dbData = $request->toDbArrayFiltered();
        $dbData['storage_type'] = $dbData['storage_type'] ?? 'local';
        $dbData['status'] = 1;

        $attachment = $this->attachmentModel->create($dbData);

        return $attachment->toArray();
    }
}
