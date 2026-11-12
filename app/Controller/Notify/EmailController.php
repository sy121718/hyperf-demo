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

namespace App\Controller\Notify;

use App\Controller\AbstractController;
use App\Dto\Email\EmailRequest;
use App\Dto\Email\EmailResponse;
use App\Service\Email\Send as SendAction;
use App\Service\Email\Template as TemplateAction;

class EmailController extends AbstractController
{
    public function send(EmailRequest $request): array
    {
        $result = container()->get(SendAction\Send::class)->handle($request);
        return $this->success($result, '发送成功');
    }

    public function sendRecords(EmailRequest $request): array
    {
        $result = container()->get(SendAction\GetRecordList::class)->handle($request);
        return $this->success(EmailResponse::page($result), '获取成功');
    }

    public function testSend(EmailRequest $request): array
    {
        container()->get(SendAction\Test::class)->handle($request);
        return $this->success(null, '测试发送成功');
    }

    public function templateList(EmailRequest $request): array
    {
        $result = container()->get(TemplateAction\GetList::class)->handle($request);
        return $this->success(EmailResponse::page($result), '获取成功');
    }

    public function templateCreate(EmailRequest $request): array
    {
        $result = container()->get(TemplateAction\Create::class)->handle($request);
        return $this->success(EmailResponse::saved($result), '创建成功');
    }

    public function templateUpdate(EmailRequest $request): array
    {
        $result = container()->get(TemplateAction\Update::class)->handle($request);
        return $this->success(EmailResponse::saved($result), '更新成功');
    }

    public function templateDelete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(TemplateAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }
}
