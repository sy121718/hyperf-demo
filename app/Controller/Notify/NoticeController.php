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
use App\Dto\Notice\NoticeRequest;
use App\Dto\Notice\NoticeResponse;
use App\Service\Notice as NoticeAction;

class NoticeController extends AbstractController
{
    public function list(NoticeRequest $request): array
    {
        $result = container()->get(NoticeAction\GetList::class)->handle($request);
        return $this->success(NoticeResponse::page($result), '获取成功');
    }

    public function myList(NoticeRequest $request): array
    {
        $result = container()->get(NoticeAction\GetMyList::class)->handle($request);
        return $this->success(NoticeResponse::page($result), '获取成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(NoticeAction\GetInfo::class)->handle($id);
        return $this->success(NoticeResponse::detail($result), '获取成功');
    }

    public function create(NoticeRequest $request): array
    {
        $id = container()->get(NoticeAction\Create::class)->handle($request);
        return $this->success(['id' => $id], '创建成功');
    }

    public function update(NoticeRequest $request): array
    {
        container()->get(NoticeAction\Update::class)->handle($request);
        return $this->success(null, '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(NoticeAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }

    public function publish(NoticeRequest $request): array
    {
        container()->get(NoticeAction\Publish::class)->handle($request);
        return $this->success(null, '发布成功');
    }

    public function withdraw(NoticeRequest $request): array
    {
        // 撤回接口的 id 在请求体里（路由未带参数）
        $id = (int) ($request->id ?? $this->request->route('id', 0));
        container()->get(NoticeAction\Withdraw::class)->handle($id);
        return $this->success(null, '撤回成功');
    }

    public function setTargets(NoticeRequest $request): array
    {
        container()->get(NoticeAction\SetTargets::class)->handle($request);
        return $this->success(null, '设置成功');
    }

    public function markRead(NoticeRequest $request): array
    {
        // 只能标记自己的通知，userId 由登录态决定，不接受请求体传入
        container()->get(NoticeAction\MarkRead::class)->handle($request, (int) currentUser('id'));
        return $this->success(null, '标记成功');
    }

    public function userNotices(NoticeRequest $request): array
    {
        // 普通用户只能查自己的通知，userId 由登录态决定
        $result = container()->get(NoticeAction\GetUserNotices::class)
            ->handle($request, (int) currentUser('id'));
        return $this->success(NoticeResponse::page($result), '获取成功');
    }

    public function unreadCount(): array
    {
        $userId = (int) currentUser('id');
        $result = container()->get(NoticeAction\GetUnreadCount::class)->handle($userId);
        return $this->success(['count' => $result], '获取成功');
    }

    public function statistics(NoticeRequest $request): array
    {
        $result = container()->get(NoticeAction\GetStatistics::class)->handle($request);
        return $this->success($result, '获取成功');
    }

    public function batchPush(NoticeRequest $request): array
    {
        $result = container()->get(NoticeAction\BatchPushToUsers::class)->handle($request);
        return $this->success(['count' => $result], '推送成功');
    }
}
