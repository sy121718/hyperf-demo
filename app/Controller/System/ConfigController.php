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
use App\Dto\Config\ConfigRequest;
use App\Dto\Config\ConfigResponse;
use App\Service\Config as ConfigAction;

class ConfigController extends AbstractController
{
    public function list(ConfigRequest $request): array
    {
        $result = container()->get(ConfigAction\GetList::class)->handle($request);
        return $this->success(ConfigResponse::page($result), '获取成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(ConfigAction\GetInfo::class)->handle($id);
        return $this->success(ConfigResponse::detail($result), '获取成功');
    }

    public function create(ConfigRequest $request): array
    {
        $result = container()->get(ConfigAction\Create::class)->handle($request);
        return $this->success(ConfigResponse::saved($result), '创建成功');
    }

    public function update(ConfigRequest $request): array
    {
        // 路由参数优先，覆盖请求体里的 id
        $request->id = (int) $this->request->route('id', $request->id ?? 0);
        $result = container()->get(ConfigAction\Update::class)->handle($request);
        return $this->success(ConfigResponse::saved($result), '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(ConfigAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }
}
