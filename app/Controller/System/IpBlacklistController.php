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
use App\Service\IpBlacklist as IpAction;

class IpBlacklistController extends AbstractController
{
    public function list(): array
    {
        $data = $this->request->all();
        $result = container()->get(IpAction\GetList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function ban(): array
    {
        $data = $this->request->all();
        $result = container()->get(IpAction\Ban::class)->handle($data);
        return $this->success($result, '拉黑成功');
    }

    public function unban(): array
    {
        $data = $this->request->all();
        $id = (int) ($data['id'] ?? 0);
        $result = container()->get(IpAction\Unban::class)->handle($id);
        return $this->success($result, '解除拉黑成功');
    }

    public function check(): array
    {
        $data = $this->request->all();
        $ip = $data['ip_address'] ?? '';
        $result = container()->get(IpAction\CheckIp::class)->handle($ip);

        if (! $result) {
            return $this->success(['is_banned' => false, 'ban_info' => null], 'IP未被拉黑');
        }

        return $this->success(['is_banned' => true, 'ban_info' => $result], 'IP已被拉黑');
    }
}
