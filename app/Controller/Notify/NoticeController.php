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
use App\Service\Notice as NoticeAction;
use App\Service\Notice\Dto\CreateNoticeDto;
use App\Service\Notice\Dto\UpdateNoticeDto;

class NoticeController extends AbstractController
{
    public function create(): array
    {
        $data = $this->request->all();
        $dto = CreateNoticeDto::fromArray($data);
        $result = container()->get(NoticeAction\Create::class)->handle($dto);
        return $this->success(['id' => $result], '创建成功');
    }

    public function update(): array
    {
        $data = $this->request->all();
        $data['id'] = (int) $this->request->route('id', $data['id'] ?? 0);
        $dto = UpdateNoticeDto::fromArray($data);
        container()->get(NoticeAction\Update::class)->handle($dto);
        return $this->success(null, '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(NoticeAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }

    public function publish(): array
    {
        $data = $this->request->all();
        container()->get(NoticeAction\Publish::class)->handle($data);
        return $this->success(null, '发布成功');
    }

    public function withdraw(): array
    {
        $id = (int) ($this->request->all()['id'] ?? 0);
        container()->get(NoticeAction\Withdraw::class)->handle($id);
        return $this->success(null, '撤回成功');
    }

    public function setTargets(): array
    {
        $data = $this->request->all();
        container()->get(NoticeAction\SetTargets::class)->handle($data);
        return $this->success(null, '设置成功');
    }

    public function markRead(): array
    {
        $data = $this->request->all();
        container()->get(NoticeAction\MarkRead::class)->handle($data);
        return $this->success(null, '标记成功');
    }

    public function userNotices(): array
    {
        $data = $this->request->all();
        $result = container()->get(NoticeAction\GetUserNotices::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function unreadCount(): array
    {
        $userId = (int) currentUser('id');
        $result = container()->get(NoticeAction\GetUnreadCount::class)->handle($userId);
        return $this->success(['count' => $result], '获取成功');
    }

    public function statistics(): array
    {
        $data = $this->request->all();
        $result = container()->get(NoticeAction\GetStatistics::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function batchPush(): array
    {
        $data = $this->request->all();
        $result = container()->get(NoticeAction\BatchPushToUsers::class)->handle($data);
        return $this->success(['count' => $result], '推送成功');
    }
}
