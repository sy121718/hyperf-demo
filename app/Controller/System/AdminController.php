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
use App\Dto\Admin\AdminRequest;
use App\Dto\Admin\AdminResponse;
use App\Service\Admin\Auth as AuthAction;
use App\Service\Admin\Manage as ManageAction;
use App\Service\Admin\Permission as AdminPermAction;

class AdminController extends AbstractController
{
    public function list(AdminRequest $request): array
    {
        $result = container()->get(ManageAction\GetList::class)->handle($request);
        return $this->success(AdminResponse::page($result), '获取成功');
    }

    public function create(AdminRequest $request): array
    {
        $result = container()->get(ManageAction\Create::class)->handle($request);
        return $this->success(AdminResponse::saved($result), '创建成功');
    }

    public function update(AdminRequest $request): array
    {
        // 路由参数优先，覆盖请求体里的 id
        $request->id = (int) $this->request->route('id', $request->id ?? 0);
        $result = container()->get(ManageAction\Update::class)->handle($request);
        return $this->success(AdminResponse::saved($result), '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(ManageAction\Delete::class)->handle($id);
        return $this->success(null, '删除成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(ManageAction\GetInfo::class)->handle($id);
        return $this->success(AdminResponse::detail($result), '获取成功');
    }

    public function resetPassword(AdminRequest $request): array
    {
        container()->get(AuthAction\ResetPassword::class)->handle($request);
        return $this->success(null, '密码重置成功');
    }

    public function ban(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(ManageAction\BanAdmin::class)->handle($id);
        return $this->success(null, '封禁成功');
    }

    public function unban(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(ManageAction\UnbanAdmin::class)->handle($id);
        return $this->success(null, '解封成功');
    }

    public function forceLogout(AdminRequest $request): array
    {
        container()->get(ManageAction\ForceLogout::class)->handle($request);
        return $this->success(null, '已强制下线');
    }

    public function resetFailure(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(ManageAction\ResetFailureCount::class)->handle($id);
        return $this->success(null, '已重置');
    }

    public function getMenu(): array
    {
        $result = container()->get(ManageAction\GetMenu::class)->handle();
        return $this->success($result, '获取成功');
    }

    public function all(AdminRequest $request): array
    {
        $result = container()->get(ManageAction\GetAllAdmins::class)->handle($request);
        return $this->success(AdminResponse::collection($result), '获取成功');
    }

    public function kick(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(ManageAction\KickUser::class)->handle($id);
        return $this->success(null, '已踢出');
    }

    public function fullInfo(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(ManageAction\GetAdminFullInfo::class)->handle($id);
        return $this->success($result, '获取成功');
    }

    public function getRoles(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(AdminPermAction\GetRoles::class)->handle($id);
        return $this->success($result, '获取成功');
    }

    public function getPermissions(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(AdminPermAction\GetPermissions::class)->handle($id);
        return $this->success($result, '获取成功');
    }

    public function assignRoles(AdminRequest $request): array
    {
        $result = container()->get(AdminPermAction\AssignRoles::class)->handle($request);
        return $this->success($result, '分配成功');
    }

    public function revokeRole(AdminRequest $request): array
    {
        $result = container()->get(AdminPermAction\RevokeRole::class)->handle($request);
        return $this->success($result, '移除成功');
    }
}
