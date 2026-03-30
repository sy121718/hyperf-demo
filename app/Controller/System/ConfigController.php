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
use App\Service\Config as ConfigAction;

class ConfigController extends AbstractController
{
    public function list(): array
    {
        $data = $this->request->all();
        $result = container()->get(ConfigAction\GetList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(ConfigAction\GetInfo::class)->handle($id);
        return $this->success($result, '获取成功');
    }

    public function create(): array
    {
        $data = $this->request->all();
        $result = container()->get(ConfigAction\Create::class)->handle($data);
        return $this->success($result, '创建成功');
    }

    public function update(): array
    {
        $id = (int) $this->request->route('id', 0);
        $data = $this->request->all();
        $result = container()->get(ConfigAction\Update::class)->handle($id, $data);
        return $this->success($result, '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(ConfigAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }
}
