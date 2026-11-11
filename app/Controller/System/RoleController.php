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
use App\Dto\Role\RoleRequest;
use App\Dto\Role\RoleResponse;
use App\Service\Role as RoleAction;

class RoleController extends AbstractController
{
    public function list(RoleRequest $request): array
    {
        $result = container()->get(RoleAction\GetList::class)->handle($request);
        return $this->success(RoleResponse::page($result), '获取成功');
    }

    public function create(RoleRequest $request): array
    {
        $result = container()->get(RoleAction\Create::class)->handle($request);
        return $this->success(RoleResponse::saved($result), '创建成功');
    }

    public function update(RoleRequest $request): array
    {
        // 路由参数优先，覆盖请求体里的 id
        $request->id = (int) $this->request->route('id', $request->id ?? 0);
        $result = container()->get(RoleAction\Update::class)->handle($request);
        return $this->success(RoleResponse::saved($result), '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(RoleAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(RoleAction\GetInfo::class)->handle($id);
        return $this->success(RoleResponse::detail($result), '获取成功');
    }

    public function all(): array
    {
        $result = container()->get(RoleAction\AllRoles::class)->handle();
        return $this->success($result, '获取成功');
    }

    public function options(): array
    {
        $result = container()->get(RoleAction\GetOptions::class)->handle();
        return $this->success($result, '获取成功');
    }

    public function assignMenus(RoleRequest $request): array
    {
        $result = container()->get(RoleAction\AssignMenus::class)
            ->handle((string) $request->role_code, $request->menu_ids ?? []);
        return $this->success($result, '分配成功');
    }

    public function assignToAdmin(RoleRequest $request): array
    {
        $result = container()->get(RoleAction\AssignRolesToAdmin::class)->handle($request);
        return $this->success($result, '分配成功');
    }

    public function removeFromAdmin(RoleRequest $request): array
    {
        $result = container()->get(RoleAction\RemoveRoleFromAdmin::class)->handle($request);
        return $this->success($result, '移除成功');
    }

    public function batchDelete(RoleRequest $request): array
    {
        $result = container()->get(RoleAction\BatchDelete::class)->handle($request);
        return $this->success(['deleted' => $result], '批量删除成功');
    }
}
