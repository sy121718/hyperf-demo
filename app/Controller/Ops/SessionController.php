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
use App\Dto\Session\SessionRequest;
use App\Dto\Session\SessionResponse;
use App\Service\Ops\Session as SessionAction;

class SessionController extends AbstractController
{
    public function list(SessionRequest $request): array
    {
        $result = container()->get(SessionAction\GetList::class)->handle($request);
        return $this->success(SessionResponse::page($result), '获取成功');
    }

    public function myList(SessionRequest $request): array
    {
        $result = container()->get(SessionAction\GetMySessions::class)->handle($request);
        return $this->success(SessionResponse::page($result), '获取成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(SessionAction\GetInfo::class)->handle($id);
        return $this->success(SessionResponse::detail($result), '获取成功');
    }

    public function kickout(SessionRequest $request): array
    {
        container()->get(SessionAction\Kickout::class)->handle((int) $request->id);
        return $this->success(null, '强制下线成功');
    }

    public function clean(): array
    {
        $count = container()->get(SessionAction\CleanExpired::class)->handle();
        return $this->success(['count' => $count], "清理 {$count} 个过期会话");
    }

    public function batchKickout(SessionRequest $request): array
    {
        $count = container()->get(SessionAction\BatchKickout::class)->handle($request);
        return $this->success(['count' => $count], "批量踢出 {$count} 个会话");
    }
}
