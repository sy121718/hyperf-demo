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
use App\Service\Permission as PermAction;
use App\Service\Permission\Dto\PermissionItemDto;

class PermissionController extends AbstractController
{
    public function tree(): array
    {
        $data = $this->request->all();
        $result = container()->get(PermAction\GetTreeList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function create(): array
    {
        $data = $this->request->all();
        $dto = PermissionItemDto::fromArray($data);
        $result = container()->get(PermAction\Create::class)->handle($dto);
        return $this->success($result, '创建成功');
    }

    public function update(): array
    {
        $data = $this->request->all();
        $data['id'] = (int) $this->request->route('id', $data['id'] ?? 0);
        $dto = PermissionItemDto::fromArray($data);
        $result = container()->get(PermAction\Update::class)->handle($dto);
        return $this->success($result, '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(PermAction\Delete::class)->handle(['id' => $id]);
        return $this->success(null, '删除成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(PermAction\GetInfo::class)->handle($id);
        return $this->success($result, '获取成功');
    }

    public function options(): array
    {
        $result = container()->get(PermAction\GetOptions::class)->handle();
        return $this->success($result, '获取成功');
    }

    public function batchDelete(): array
    {
        $data = $this->request->all();
        $result = container()->get(PermAction\BatchDelete::class)->handle($data);
        return $this->success(['deleted' => $result], '批量删除成功');
    }

    public function batchCreate(): array
    {
        $data = $this->request->all();
        $items = [];
        foreach ($data['items'] ?? [] as $item) {
            $items[] = PermissionItemDto::fromArray($item);
        }
        $result = container()->get(PermAction\BatchCreate::class)->handle($items);
        return $this->success($result, '批量创建成功');
    }

    public function batchUpdate(): array
    {
        $data = $this->request->all();
        $items = [];
        foreach ($data['items'] ?? [] as $item) {
            $items[] = PermissionItemDto::fromArray($item);
        }
        $result = container()->get(PermAction\BatchUpdate::class)->handle($items);
        return $this->success($result, '批量更新成功');
    }
}
