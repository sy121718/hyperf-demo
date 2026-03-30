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

namespace App\Controller\Ops;

use App\Controller\AbstractController;
use App\Service\Ops\Session as SessionAction;

class SessionController extends AbstractController
{
    public function list(): array
    {
        $data = $this->request->all();
        $result = container()->get(SessionAction\GetList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(SessionAction\GetInfo::class)->handle($id);
        return $this->success($result, '获取成功');
    }

    public function kickout(): array
    {
        $data = $this->request->all();
        $id = (int) ($data['id'] ?? $data['session_id'] ?? 0);
        container()->get(SessionAction\Kickout::class)->handle($id);
        return $this->success(null, '强制下线成功');
    }

    public function clean(): array
    {
        $count = container()->get(SessionAction\CleanExpired::class)->handle();
        return $this->success(['count' => $count], "清理 {$count} 个过期会话");
    }

    public function batchKickout(): array
    {
        $data = $this->request->all();
        $count = container()->get(SessionAction\BatchKickout::class)->handle($data);
        return $this->success(['count' => $count], "批量踢出 {$count} 个会话");
    }
}
