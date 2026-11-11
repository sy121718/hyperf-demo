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
use App\Dto\Menu\MenuRequest;
use App\Dto\Menu\MenuResponse;
use App\Service\Menu as MenuAction;

class MenuController extends AbstractController
{
    public function tree(MenuRequest $request): array
    {
        $result = container()->get(MenuAction\GetTree::class)->handle($request->toQueryArray());
        return $this->success(MenuResponse::collection($result), '获取成功');
    }

    public function create(MenuRequest $request): array
    {
        $result = container()->get(MenuAction\Create::class)->handle($request);
        return $this->success(MenuResponse::saved($result), '创建成功');
    }

    public function update(MenuRequest $request): array
    {
        // 路由参数优先，覆盖请求体里的 id
        $request->id = (int) $this->request->route('id', $request->id ?? 0);
        $result = container()->get(MenuAction\Update::class)->handle($request);
        return $this->success(MenuResponse::saved($result), '更新成功');
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
        return $this->success(MenuResponse::detail($result), '获取成功');
    }

    public function batchDelete(MenuRequest $request): array
    {
        $result = container()->get(MenuAction\BatchDelete::class)->handle($request->toArray());
        return $this->success(['deleted' => $result], '批量删除成功');
    }
}
