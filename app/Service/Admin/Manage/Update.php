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

namespace App\Service\Admin\Manage;

use App\Model\Admin\SysAdminModel;
use App\Service\Admin\Manage\Dto\UpdateAdminDto;
use App\Service\Role\AssignRoles;
use Hyperf\DbConnection\Db;
use Throwable;

class Update
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected AssignRoles $assignRolesAction,
    ) {
    }

    public function handle(UpdateAdminDto $dto): array
    {
        $admin = $this->adminModel->find($dto->id);
        ! $admin && paramError('管理员不存在', 404);

        $currentUserId = (int) currentUser('id');
        $isSuperAdmin = currentUser('is_admin') == 1;

        if (! $isSuperAdmin && $currentUserId !== $dto->id) {
            paramError('无权修改其他管理员信息', 403);
        }

        if (! $isSuperAdmin && $dto->isAdmin !== null) {
            paramError('无权修改管理员类型', 403);
        }

        $this->validateUniqueFields($dto, $admin);
        $data = $this->prepareData($dto);

        Db::beginTransaction();
        try {
            $admin->update($data);

            $dto->shouldUpdateRoles()
                && $this->assignRolesAction->handle($dto->id, $dto->roleIds);

            Db::commit();
            return UpdateAdminDto::dbToFront($admin->fresh()->toArray());
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    private function validateUniqueFields(UpdateAdminDto $dto, SysAdminModel $admin): void
    {
        if ($dto->username !== null && $dto->username !== $admin->username) {
            ! $this->adminModel->isFieldUnique(['username' => $dto->username], $dto->id)
                && paramError('用户名已被占用');
        }

        if ($dto->email !== null && $dto->email !== $admin->email) {
            ! $this->adminModel->isFieldUnique(['email' => $dto->email], $dto->id)
                && paramError('邮箱已被占用');
        }
    }

    private function prepareData(UpdateAdminDto $dto): array
    {
        $dbData = $dto->toDbArrayFiltered();
        unset($dbData['id'], $dbData['role_ids'], $dbData['password']);

        if ($dto->shouldUpdatePassword()) {
            $dbData['password'] = password_hash($dto->password, PASSWORD_BCRYPT);
        }

        $dbData['update_by'] = (int) currentUser('id');

        return $dbData;
    }
}
