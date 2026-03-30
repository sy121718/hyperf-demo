<?php

declare(strict_types=1);

namespace App\Controller\System;

use App\Controller\AbstractController;
use App\Request\System\RoleRequest;
use App\Service\Role as RoleAction;
use App\Service\Role\Dto\CreateRoleDto;
use App\Service\Role\Dto\UpdateRoleDto;

class RoleController extends AbstractController
{
    public function list(RoleRequest $request): array
    {
        $data = $request->validated();
        $result = container()->get(RoleAction\GetList::class)->handle($data);
        return $this->success($result, '获取成功');
    }

    public function create(RoleRequest $request): array
    {
        $data = $request->validated();
        $dto = CreateRoleDto::fromArray($data);
        $result = container()->get(RoleAction\Create::class)->handle($dto);
        return $this->success($result, '创建成功');
    }

    public function update(RoleRequest $request): array
    {
        $data = $request->validated();
        $data['id'] = (int) $this->request->route('id', $data['id'] ?? 0);
        $dto = UpdateRoleDto::fromArray($data);
        $result = container()->get(RoleAction\Update::class)->handle($dto);
        return $this->success($result, '更新成功');
    }

    public function delete(): array
    {
        $id = (int) $this->request->route('id', 0);
        container()->get(RoleAction\Delete::class)->handle(['id' => $id]);
        return $this->success(null, '删除成功');
    }

    public function info(): array
    {
        $id = (int) $this->request->route('id', 0);
        $result = container()->get(RoleAction\GetInfo::class)->handle($id);
        return $this->success($result, '获取成功');
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

    public function assignPermissions(): array
    {
        $data = $this->request->all();
        $roleCode = $data['role_code'] ?? '';
        $permissionCodes = $data['permission_codes'] ?? [];
        $result = container()->get(RoleAction\AssignPermissions::class)->handle($roleCode, $permissionCodes);
        return $this->success($result, '分配成功');
    }

    public function assignToAdmin(): array
    {
        $data = $this->request->all();
        $result = container()->get(RoleAction\AssignRolesToAdmin::class)->handle($data);
        return $this->success($result, '分配成功');
    }

    public function removeFromAdmin(): array
    {
        $data = $this->request->all();
        $result = container()->get(RoleAction\RemoveRoleFromAdmin::class)->handle($data);
        return $this->success($result, '移除成功');
    }

    public function batchDelete(): array
    {
        $data = $this->request->all();
        $result = container()->get(RoleAction\BatchDelete::class)->handle($data);
        return $this->success(['deleted' => $result], '批量删除成功');
    }
}
