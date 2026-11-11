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
use App\Dto\CronJob\CronJobRequest;
use App\Dto\CronJob\CronJobResponse;
use App\Service\CronJob as CronJobAction;

class CronJobController extends AbstractController
{
    public function list(CronJobRequest $request): array
    {
        $result = container()->get(CronJobAction\GetList::class)->handle($request);
        return $this->success(CronJobResponse::page($result), '获取成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(CronJobAction\GetInfo::class)->handle($id);
        return $this->success(CronJobResponse::detail($result), '获取成功');
    }

    public function create(CronJobRequest $request): array
    {
        $result = container()->get(CronJobAction\Create::class)->handle($request);
        return $this->success(CronJobResponse::saved($result), '创建成功');
    }

    public function update(CronJobRequest $request): array
    {
        // 路由参数优先，覆盖请求体里的 id
        $request->id = (int) $this->request->route('id', $request->id ?? 0);
        $result = container()->get(CronJobAction\Update::class)->handle($request);
        return $this->success(CronJobResponse::saved($result), '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(CronJobAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }

    public function run(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(CronJobAction\Run::class)->handle($id);
        return $this->success($result, '执行完成');
    }

    public function batchUpdate(CronJobRequest $request): array
    {
        $result = container()->get(CronJobAction\BatchUpdate::class)->handle($request);
        return $this->success(['updated' => $result], '批量更新成功');
    }
}
