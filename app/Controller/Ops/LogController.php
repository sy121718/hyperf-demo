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
use App\Dto\Log\LogRequest;
use App\Dto\Log\LogResponse;
use App\Service\Ops\LoginLog;
use App\Service\Ops\OperationLog;

class LogController extends AbstractController
{
    public function loginLog(LogRequest $request): array
    {
        $result = container()->get(LoginLog\GetList::class)->handle($request);
        return $this->success(LogResponse::page($result), '获取成功');
    }

    public function operationLog(LogRequest $request): array
    {
        $result = container()->get(OperationLog\GetList::class)->handle($request);
        return $this->success(LogResponse::page($result), '获取成功');
    }
}
