<?php

declare(strict_types=1);

namespace App\Controller\System;

use App\Controller\AbstractController;
use App\Service\Menu as MenuAction;
use App\Service\Menu\Dto\CreateMenuDto;
use App\Service\Menu\Dto\UpdateMenuDto;

class MenuController extends AbstractController
{
    public function tree(): array
    {
        $data = $this->request->all();
        $result = container()->get(MenuAction\GetTree::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function create(): array
    {
        $data = $this->request->all();
        $dto = CreateMenuDto::fromArray($data);
        $result = container()->get(MenuAction\Create::class)->handle($dto);
        return $this->success($result, '创建成功');
    }

    public function update(): array
    {
        $data = $this->request->all();
        $data['id'] = (int) $this->request->route('id', $data['id'] ?? 0);
        $dto = UpdateMenuDto::fromArray($data);
        $result = container()->get(MenuAction\Update::class)->handle($dto);
        return $this->success($result, '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(MenuAction\Delete::class)->handle(['id' => $id]);
        return $this->success(null, '删除成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(MenuAction\GetInfo::class)->handle($id);
        return $this->success($result, '获取成功');
    }

    public function batchDelete(): array
    {
        $data = $this->request->all();
        $result = container()->get(MenuAction\BatchDelete::class)->handle($data);
        return $this->success(['deleted' => $result], '批量删除成功');
    }
}
