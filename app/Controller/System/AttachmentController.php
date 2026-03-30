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

namespace App\Controller\System;

use App\Controller\AbstractController;
use App\Service\Attachment as AttachmentAction;
use App\Service\Attachment\Dto\UpdateAttachmentDto;
use App\Service\Attachment\Dto\UploadAttachmentDto;

class AttachmentController extends AbstractController
{
    public function list(): array
    {
        $data = $this->request->all();
        $result = container()->get(AttachmentAction\GetList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function upload(): array
    {
        $data = $this->request->all();
        $dto = UploadAttachmentDto::fromArray($data);
        $result = container()->get(AttachmentAction\Upload::class)->handle($dto);
        return $this->success($result, '上传成功');
    }

    public function update(): array
    {
        $data = $this->request->all();
        $data['id'] = (int) $this->request->route('id', $data['id'] ?? 0);
        $dto = UpdateAttachmentDto::fromArray($data);
        $result = container()->get(AttachmentAction\Update::class)->handle($dto);
        return $this->success($result, '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(AttachmentAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }
}
