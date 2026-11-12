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
use App\Service\Ops\LoginLog as LoginLogAction;
use App\Service\Ops\OperationLog as OperationLogAction;

class LogController extends AbstractController
{
    public function loginLog(LogRequest $request): array
    {
        $result = container()->get(LoginLogAction\GetList::class)->handle($request);
        return $this->success(LogResponse::page($result), '获取成功');
    }

    public function operationLog(LogRequest $request): array
    {
        $result = container()->get(OperationLogAction\GetList::class)->handle($request);
        return $this->success(LogResponse::page($result), '获取成功');
    }
}
