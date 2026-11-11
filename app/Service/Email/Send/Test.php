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

namespace App\Service\Email\Send;

use App\Dto\Email\EmailRequest;

class Test
{
    public function handle(EmailRequest $request): bool
    {
        // 收件人是否为空由 DTO 的 required|email 规则拦截
        return $request->to !== null;
    }
}
