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
use App\Service\Admin\Manage\Dto\CreateAdminDto;
use App\Service\Role\AssignRoles;
use Hyperf\DbConnection\Db;
use Throwable;

class Create
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected AssignRoles $assignRolesAction,
    ) {
    }

    public function handle(CreateAdminDto $dto): array
    {
        empty($dto->username) && paramError('用户名不能为空');
        empty($dto->password) && paramError('密码不能为空');

        ! $this->adminModel->isFieldUnique(['username' => $dto->username])
            && paramError('用户名已存在');

        $data = $this->prepareData($dto);

        $admin = null;
        Db::beginTransaction();
        try {
            $admin = $this->adminModel->create($data);

            $dto->hasRoles()
                && $this->assignRolesAction->handle($admin->id, $dto->roleIds);

            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }

        return ['id' => $admin->id];
    }

    private function prepareData(CreateAdminDto $dto): array
    {
        $dbData = $dto->toDbArray();
        unset($dbData['role_ids']);

        $dbData['password'] = password_hash($dto->password, PASSWORD_BCRYPT);
        $dbData['create_by'] = (int) currentUser('id');
        $dbData['update_by'] = (int) currentUser('id');

        return $dbData;
    }
}
