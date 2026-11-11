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
use App\Dto\IpBlacklist\IpBlacklistRequest;
use App\Dto\IpBlacklist\IpBlacklistResponse;
use App\Service\IpBlacklist as IpAction;

class IpBlacklistController extends AbstractController
{
    public function list(IpBlacklistRequest $request): array
    {
        $result = container()->get(IpAction\GetList::class)->handle($request);
        return $this->success(IpBlacklistResponse::page($result), '获取成功');
    }

    public function ban(IpBlacklistRequest $request): array
    {
        $result = container()->get(IpAction\Ban::class)->handle($request);
        return $this->success(IpBlacklistResponse::saved($result), '拉黑成功');
    }

    public function unban(IpBlacklistRequest $request): array
    {
        $result = container()->get(IpAction\Unban::class)->handle((int) $request->id);
        return $this->success(IpBlacklistResponse::saved($result), '解除拉黑成功');
    }

    public function check(IpBlacklistRequest $request): array
    {
        $result = container()->get(IpAction\CheckIp::class)->handle((string) $request->ip_address);

        return $this->success([
            'is_banned' => $result !== null,
            'ban_info' => $result ? IpBlacklistResponse::detail($result) : null,
        ], $result ? 'IP已被拉黑' : 'IP未被拉黑');
    }
}
