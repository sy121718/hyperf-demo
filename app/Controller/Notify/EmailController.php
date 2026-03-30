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
use App\Service\Email\Send as SendAction;
use App\Service\Email\Template as TemplateAction;

class EmailController extends AbstractController
{
    public function send(): array
    {
        $data = $this->request->all();
        $result = container()->get(SendAction\Send::class)->handle($data);
        return $this->success($result, '发送成功');
    }

    public function sendRecords(): array
    {
        $data = $this->request->all();
        $result = container()->get(SendAction\GetRecordList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function testSend(): array
    {
        $data = $this->request->all();
        container()->get(SendAction\Test::class)->handle($data);
        return $this->success(null, '测试发送成功');
    }

    public function templateList(): array
    {
        $data = $this->request->all();
        $result = container()->get(TemplateAction\GetList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function templateCreate(): array
    {
        $data = $this->request->all();
        $result = container()->get(TemplateAction\Create::class)->handle($data);
        return $this->success($result, '创建成功');
    }

    public function templateUpdate(): array
    {
        $data = $this->request->all();
        $data['id'] = (int) $this->request->route('id', $data['id'] ?? 0);
        $result = container()->get(TemplateAction\Update::class)->handle($data);
        return $this->success($result, '更新成功');
    }

    public function templateDelete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(TemplateAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }
}
