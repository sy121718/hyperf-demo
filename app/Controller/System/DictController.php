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
use App\Service\Dict as DictAction;

class DictController extends AbstractController
{
    public function tree(): array
    {
        $data = $this->request->all();
        $result = container()->get(DictAction\GetTreeList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(DictAction\GetInfo::class)->handle($id);
        return $this->success($result, '获取成功');
    }

    public function items(): array
    {
        $dictId = (int) $this->request->route('dict_id', 0);
        $result = container()->get(DictAction\GetItems::class)->handle($dictId);
        return $this->success($result, '获取成功');
    }

    public function create(): array
    {
        $data = $this->request->all();
        $result = container()->get(DictAction\Create::class)->handle($data);
        return $this->success($result, '创建成功');
    }

    public function update(): array
    {
        $data = $this->request->all();
        $id = (int) ($data['id'] ?? $this->request->route('id', 0));
        $result = container()->get(DictAction\Update::class)->handle($id, $data);
        return $this->success($result, '更新成功');
    }

    public function delete(): array
    {
        $data = $this->request->all();
        $id = (int) ($data['id'] ?? $this->request->route('id', 0));
        container()->get(DictAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }
}
