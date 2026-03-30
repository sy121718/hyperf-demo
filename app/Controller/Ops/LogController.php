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
use App\Service\Ops\LoginLog;
use App\Service\Ops\OperationLog;

class LogController extends AbstractController
{
    public function loginLog(): array
    {
        $data = $this->request->all();
        $result = container()->get(LoginLog\GetList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function operationLog(): array
    {
        $data = $this->request->all();
        $result = container()->get(OperationLog\GetList::class)->handle($data);
        return $this->success($result, '获取成功');
    }
}
