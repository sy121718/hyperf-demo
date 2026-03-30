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
use App\Service\CronJob as CronJobAction;
use App\Service\CronJob\Dto\CreateCronJobDto;
use App\Service\CronJob\Dto\UpdateCronJobDto;

class CronJobController extends AbstractController
{
    public function list(): array
    {
        $data = $this->request->all();
        $result = container()->get(CronJobAction\GetList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(CronJobAction\GetInfo::class)->handle($id);
        return $this->success($result, '获取成功');
    }

    public function create(): array
    {
        $data = $this->request->all();
        $dto = CreateCronJobDto::fromArray($data);
        $result = container()->get(CronJobAction\Create::class)->handle($dto);
        return $this->success($result, '创建成功');
    }

    public function update(): array
    {
        $data = $this->request->all();
        $data['id'] = (int) $this->request->route('id', $data['id'] ?? 0);
        $dto = UpdateCronJobDto::fromArray($data);
        $result = container()->get(CronJobAction\Update::class)->handle($dto);
        return $this->success($result, '更新成功');
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

    public function batchUpdate(): array
    {
        $data = $this->request->all();
        $ids = $data['ids'] ?? [];
        unset($data['ids']);
        $result = container()->get(CronJobAction\BatchUpdate::class)->handle($ids, $data);
        return $this->success(['updated' => $result], '批量更新成功');
    }
}
