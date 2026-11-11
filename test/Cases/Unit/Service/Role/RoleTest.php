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

namespace HyperfTest\Cases\Unit\Service\Role;

use App\Dto\Role\RoleRequest;
use App\Exception\BusinessException;
use App\Model\Admin\SysAdminModel;
use App\Model\Admin\SysAdminRoleModel;
use App\Model\Rbac\SysMenuModel;
use App\Model\Rbac\SysPermissionDictModel;
use App\Model\Rbac\SysRoleModel;
use App\Service\Role\AssignMenus;
use App\Service\Role\AssignPermissions;
use App\Service\Role\BatchDelete;
use App\Service\Role\Create;
use App\Service\Role\Delete;
use App\Service\Role\GetInfo;
use App\Service\Role\RemoveRoleFromAdmin;
use App\Service\Role\Update;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class RoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 1, 'username' => 'admin', 'is_admin' => 1]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    // ==================== Create ====================

    public function testCreateSuccess(): void
    {
        $role = Mockery::mock(stdClass::class);
        $role->shouldReceive('toArray')->andReturn(['id' => 1, 'role_code' => 'ops']);

        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);
        $model->shouldReceive('create')->once()->with(Mockery::on(
            static fn (array $data) => $data['role_code'] === 'ops'
                && $data['create_by'] === 1
                // 未提交 permission_codes 时补空数组，避免写 NULL
                && $data['permission_codes'] === []
        ))->andReturn($role);

        $result = (new Create($model))->handle(RoleRequest::fromArray([
            'role_code' => 'ops',
            'role_name' => '运维',
        ]));

        $this->assertSame(1, $result['id']);
    }

    public function testCreateDuplicateCode(): void
    {
        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('角色标识 ops 已存在');

        (new Create($model))->handle(RoleRequest::fromArray([
            'role_code' => 'ops',
            'role_name' => '运维',
        ]));
    }

    public function testCreateDropsNullFields(): void
    {
        // 未提交的字段不能显式写 NULL，否则会覆盖数据库默认值
        $role = Mockery::mock(stdClass::class);
        $role->shouldReceive('toArray')->andReturn([]);

        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);
        $model->shouldReceive('create')->once()->with(Mockery::on(
            static fn (array $data) => ! array_key_exists('remark', $data)
                && ! array_key_exists('sort_order', $data)
        ))->andReturn($role);

        (new Create($model))->handle(RoleRequest::fromArray(['role_code' => 'ops']));
    }

    // ==================== Update ====================

    public function testUpdateSuccess(): void
    {
        $role = Mockery::mock(SysRoleModel::class)->makePartial();
        $role->shouldReceive('update')->once()->with(['role_name' => '运维组'])->andReturn(true);
        $role->shouldReceive('fresh')->andReturn($role);
        $role->shouldReceive('toArray')->andReturn(['id' => 1, 'role_name' => '运维组']);

        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '角色')->andReturn($role);

        $result = (new Update($model))->handle(RoleRequest::fromArray([
            'id' => 1,
            'role_name' => '运维组',
        ]));

        $this->assertSame('运维组', $result['role_name']);
    }

    public function testUpdateNotFound(): void
    {
        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '角色不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Update($model))->handle(RoleRequest::fromArray(['id' => 999]));
    }

    public function testUpdateDuplicateCode(): void
    {
        $role = Mockery::mock(SysRoleModel::class)->makePartial();
        $role->role_code = 'old';

        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('findOrNotFound')->andReturn($role);
        $model->shouldReceive('isFieldUnique')->andReturn(false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('角色标识 new 已存在');

        (new Update($model))->handle(RoleRequest::fromArray([
            'id' => 1,
            'role_code' => 'new',
        ]));
    }

    public function testUpdateSkipsUniquenessWhenCodeUnchanged(): void
    {
        // 编码没变时不该去查重，否则自己会和自己冲突
        $role = Mockery::mock(SysRoleModel::class)->makePartial();
        $role->role_code = 'same';
        $role->shouldReceive('update')->once()->andReturn(true);
        $role->shouldReceive('fresh')->andReturn($role);
        $role->shouldReceive('toArray')->andReturn(['id' => 1]);

        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('findOrNotFound')->andReturn($role);
        $model->shouldReceive('isFieldUnique')->never();

        (new Update($model))->handle(RoleRequest::fromArray([
            'id' => 1,
            'role_code' => 'same',
        ]));
    }

    // ==================== Delete ====================

    public function testDeleteSuccess(): void
    {
        $role = Mockery::mock(SysRoleModel::class)->makePartial();
        $role->role_code = 'ops';
        $role->shouldReceive('delete')->once()->andReturn(true);

        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '角色')->andReturn($role);

        $adminRoleModel = Mockery::mock(SysAdminRoleModel::class);
        $adminRoleModel->shouldReceive('where->exists')->andReturn(false);

        $this->assertTrue((new Delete($model, $adminRoleModel))->handle(1));
    }

    public function testDeleteRejectedWhenAssignedToAdmin(): void
    {
        $role = Mockery::mock(SysRoleModel::class)->makePartial();
        $role->role_code = 'ops';

        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('findOrNotFound')->andReturn($role);

        $adminRoleModel = Mockery::mock(SysAdminRoleModel::class);
        $adminRoleModel->shouldReceive('where->exists')->andReturn(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('角色存在关联的用户，无法删除');

        (new Delete($model, $adminRoleModel))->handle(1);
    }

    public function testDeleteZeroId(): void
    {
        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '角色ID不能为空'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('角色ID不能为空');

        (new Delete($model, Mockery::mock(SysAdminRoleModel::class)))->handle(0);
    }

    // ==================== GetInfo ====================

    public function testGetInfoSuccess(): void
    {
        $role = Mockery::mock(SysRoleModel::class)->makePartial();
        $role->shouldReceive('toArray')->andReturn(['id' => 1, 'role_code' => 'ops']);

        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '角色')->andReturn($role);

        $result = (new GetInfo($model))->handle(1);

        $this->assertSame('ops', $result['role_code']);
    }

    public function testGetInfoNotFound(): void
    {
        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '角色不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new GetInfo($model))->handle(999);
    }

    // ==================== BatchDelete ====================

    public function testBatchDeleteRequiresSuperAdmin(): void
    {
        Context::set('auth_user', ['id' => 2, 'is_admin' => 0]);

        $model = Mockery::mock(SysRoleModel::class);
        $adminRoleModel = Mockery::mock(SysAdminRoleModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(403);

        (new BatchDelete($model, $adminRoleModel))->handle(
            RoleRequest::fromArray(['ids' => [1, 2]])
        );
    }

    public function testBatchDeleteRejectsEmptyIds(): void
    {
        $model = Mockery::mock(SysRoleModel::class);
        $adminRoleModel = Mockery::mock(SysAdminRoleModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('请选择要删除的角色');

        (new BatchDelete($model, $adminRoleModel))->handle(RoleRequest::fromArray([]));
    }

    // ==================== AssignMenus ====================

    public function testAssignMenusCollectsPermissionCodes(): void
    {
        // 菜单里只有非空的 permission_code 会被收进角色权限
        $role = Mockery::mock(SysRoleModel::class)->makePartial();
        $role->shouldReceive('update')->once()->with([
            'permission_codes' => ['sys:user:list', 'sys:user:add'],
        ])->andReturn(true);

        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('firstOrNotFound')->with(['role_code' => 'ops'], '角色')->andReturn($role);

        // 目录型菜单没有 permission_code，应被跳过
        $menuA = new stdClass();
        $menuA->permission_code = 'sys:user:list';
        $menuB = new stdClass();
        $menuB->permission_code = null;
        $menuC = new stdClass();
        $menuC->permission_code = 'sys:user:add';

        $menuModel = Mockery::mock(SysMenuModel::class);
        $menuModel->shouldReceive('whereIn->get')->andReturn([$menuA, $menuB, $menuC]);

        $result = (new AssignMenus($model, $menuModel))->handle('ops', [1, 2, 3]);

        $this->assertSame('ops', $result['role_code']);
    }

    public function testAssignMenusRejectsEmptyRoleCode(): void
    {
        $model = Mockery::mock(SysRoleModel::class);
        $menuModel = Mockery::mock(SysMenuModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('角色标识不能为空');

        (new AssignMenus($model, $menuModel))->handle('', [1]);
    }

    // ==================== AssignPermissions ====================

    public function testAssignPermissionsRejectsUnknownCode(): void
    {
        $role = Mockery::mock(SysRoleModel::class)->makePartial();

        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('firstOrNotFound')->andReturn($role);

        $permModel = Mockery::mock(SysPermissionDictModel::class);
        // 提交 2 个，库里只存在 1 个
        $permModel->shouldReceive('whereIn->count')->andReturn(1);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('部分权限不存在');

        (new AssignPermissions($model, $permModel))->handle('ops', ['a', 'b']);
    }

    public function testAssignPermissionsSuccess(): void
    {
        $role = Mockery::mock(SysRoleModel::class)->makePartial();
        $role->shouldReceive('update')->once()->with(['permission_codes' => ['a']])->andReturn(true);

        $model = Mockery::mock(SysRoleModel::class);
        $model->shouldReceive('firstOrNotFound')->andReturn($role);

        $permModel = Mockery::mock(SysPermissionDictModel::class);
        $permModel->shouldReceive('whereIn->count')->andReturn(1);

        $result = (new AssignPermissions($model, $permModel))->handle('ops', ['a']);

        $this->assertSame(['a'], $result['permission_codes']);
    }

    // ==================== RemoveRoleFromAdmin ====================

    public function testRemoveRoleFromAdminSuccess(): void
    {
        $admin = Mockery::mock(SysAdminModel::class)->makePartial();
        $admin->shouldReceive('roles->detach')->once()->with(9)->andReturn(true);

        $role = Mockery::mock(SysRoleModel::class)->makePartial();

        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldReceive('findOrNotFound')->with(1, '管理员')->andReturn($admin);

        $roleModel = Mockery::mock(SysRoleModel::class);
        $roleModel->shouldReceive('findOrNotFound')->with(9, '角色')->andReturn($role);

        $result = (new RemoveRoleFromAdmin($adminModel, $roleModel))->handle(
            RoleRequest::fromArray(['admin_id' => 1, 'role_id' => 9])
        );

        $this->assertSame(['admin_id' => 1, 'role_id' => 9], $result);
    }

    public function testRemoveRoleFromAdminRejectsMissingRole(): void
    {
        $admin = Mockery::mock(SysAdminModel::class)->makePartial();

        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldReceive('findOrNotFound')->andReturn($admin);

        $roleModel = Mockery::mock(SysRoleModel::class);
        $roleModel->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '角色不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new RemoveRoleFromAdmin($adminModel, $roleModel))->handle(
            RoleRequest::fromArray(['admin_id' => 1, 'role_id' => 999])
        );
    }
}
