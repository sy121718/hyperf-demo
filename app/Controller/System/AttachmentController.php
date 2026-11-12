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
use App\Dto\Attachment\AttachmentRequest;
use App\Dto\Attachment\AttachmentResponse;
use App\Service\Attachment as AttachmentAction;

class AttachmentController extends AbstractController
{
    public function list(AttachmentRequest $request): array
    {
        $result = container()->get(AttachmentAction\GetList::class)->handle($request);
        return $this->success(AttachmentResponse::page($result), '获取成功');
    }

    public function upload(AttachmentRequest $request): array
    {
        $result = container()->get(AttachmentAction\Upload::class)->handle($request);
        return $this->success(AttachmentResponse::saved($result), '上传成功');
    }

    public function update(AttachmentRequest $request): array
    {
        $result = container()->get(AttachmentAction\Update::class)->handle($request);
        return $this->success(AttachmentResponse::saved($result), '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(AttachmentAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }
}
