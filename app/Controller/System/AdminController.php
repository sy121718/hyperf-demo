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
use App\Request\System\AdminRequest;
use App\Service\Admin\Auth as AuthAction;
use App\Service\Admin\Manage as ManageAction;
use App\Service\Admin\Manage\Dto\CreateAdminDto;
use App\Service\Admin\Manage\Dto\UpdateAdminDto;
use App\Service\Admin\Permission as AdminPermAction;

class AdminController extends AbstractController
{
    public function list(AdminRequest $request): array
    {
        $data = $request->validated();
        $result = container()->get(ManageAction\GetList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function create(AdminRequest $request): array
    {
        $data = $request->validated();
        $dto = CreateAdminDto::fromArray($data);
        $result = container()->get(ManageAction\Create::class)->handle($dto);
        return $this->success($result, '创建成功');
    }

    public function update(AdminRequest $request): array
    {
        $data = $request->validated();
        $data['id'] = (int) $this->request->route('id', $data['id'] ?? 0);
        $dto = UpdateAdminDto::fromArray($data);
        $result = container()->get(ManageAction\Update::class)->handle($dto);
        return $this->success($result, '更新成功');
    }

    public function delete(AdminRequest $request): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(ManageAction\Delete::class)->handle(['id' => $id]);
        return $this->success(null, '删除成功');
    }

    public function info(AdminRequest $request): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(ManageAction\GetInfo::class)->handle($id);
        return $this->success($result, '获取成功');
    }

    public function resetPassword(AdminRequest $request): array
    {
        $data = $request->validated();
        container()->get(AuthAction\ResetPassword::class)->handle($data);
        return $this->success(null, '密码重置成功');
    }

    public function ban(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(ManageAction\BanAdmin::class)->handle(['id' => $id]);
        return $this->success(null, '封禁成功');
    }

    public function unban(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(ManageAction\UnbanAdmin::class)->handle(['id' => $id]);
        return $this->success(null, '解封成功');
    }

    public function forceLogout(): array
    {
        $data = $this->request->all();
        container()->get(ManageAction\ForceLogout::class)->handle($data);
        return $this->success(null, '已强制下线');
    }

    public function resetFailure(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(ManageAction\ResetFailureCount::class)->handle(['id' => $id]);
        return $this->success(null, '已重置');
    }

    public function getMenu(): array
    {
        $result = container()->get(ManageAction\GetMenu::class)->handle();
        return $this->success($result, '获取成功');
    }

    public function all(): array
    {
        $data = $this->request->all();
        $result = container()->get(ManageAction\GetAllAdmins::class)->handle($data);
        return $this->success($result, '获取成功');
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

    public function assignRoles(): array
    {
        $data = $this->request->all();
        $result = container()->get(AdminPermAction\AssignRoles::class)->handle($data);
        return $this->success($result, '分配成功');
    }

    public function revokeRole(): array
    {
        $data = $this->request->all();
        $result = container()->get(AdminPermAction\RevokeRole::class)->handle($data);
        return $this->success($result, '移除成功');
    }
}
