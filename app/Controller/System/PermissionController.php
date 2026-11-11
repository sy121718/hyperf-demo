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
use App\Dto\Permission\PermissionRequest;
use App\Dto\Permission\PermissionResponse;
use App\Service\Permission as PermAction;

class PermissionController extends AbstractController
{
    public function tree(PermissionRequest $request): array
    {
        $result = container()->get(PermAction\GetTreeList::class)->handle($request->toQueryArray());
        return $this->success(PermissionResponse::collection($result), '获取成功');
    }

    public function create(PermissionRequest $request): array
    {
        $result = container()->get(PermAction\Create::class)->handle($request);
        return $this->success(PermissionResponse::saved($result), '创建成功');
    }

    public function update(PermissionRequest $request): array
    {
        // 路由参数优先，覆盖请求体里的 id
        $request->id = (int) $this->request->route('id', $request->id ?? 0);
        $result = container()->get(PermAction\Update::class)->handle($request);
        return $this->success(PermissionResponse::saved($result), '更新成功');
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
        return $this->success(PermissionResponse::detail($result), '获取成功');
    }

    public function options(): array
    {
        $result = container()->get(PermAction\GetOptions::class)->handle();
        return $this->success($result, '获取成功');
    }

    public function batchDelete(PermissionRequest $request): array
    {
        $result = container()->get(PermAction\BatchDelete::class)->handle($request->toArray());
        return $this->success(['deleted' => $result], '批量删除成功');
    }

    public function batchCreate(PermissionRequest $request): array
    {
        $items = array_map(
            static fn (array $item) => PermissionRequest::fromArray($item),
            $request->items ?? []
        );

        $result = container()->get(PermAction\BatchCreate::class)->handle($items);
        return $this->success([
            'created_count' => $result['created_count'],
            'skipped_codes' => $result['skipped_codes'],
        ], '批量创建成功');
    }

    public function batchUpdate(PermissionRequest $request): array
    {
        $items = array_map(
            static fn (array $item) => PermissionRequest::fromArray($item),
            $request->items ?? []
        );

        $result = container()->get(PermAction\BatchUpdate::class)->handle($items);
        return $this->success(['updated_count' => $result['updated_count']], '批量更新成功');
    }
}
