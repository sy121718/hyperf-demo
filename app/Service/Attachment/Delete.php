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

class Delete
{
    public function __construct(
        protected SysAttachmentModel $attachmentModel,
    ) {
    }

    public function handle(int $id): bool
    {
        $attachment = $this->attachmentModel->find($id);
        ! $attachment && notFound('附件');

        $attachment->delete();

        return true;
    }
}
