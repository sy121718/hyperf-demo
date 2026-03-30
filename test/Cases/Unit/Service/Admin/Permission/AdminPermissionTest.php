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

namespace HyperfTest\Cases\Unit\Service\Admin\Permission;

use App\Exception\BusinessException;
use App\Model\Admin\SysAdminModel;
use App\Model\Role\SysRoleModel;
use App\Service\Admin\Permission\AssignRoles;
use App\Service\Admin\Permission\GetPermissions;
use App\Service\Admin\Permission\GetRoles;
use App\Service\Admin\Permission\RevokeRole;
use Hyperf\Collection\Collection;
use Hyperf\Context\Context;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class AdminPermissionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('currentUser');
    }

    public function testGetRolesSuccess(): void
    {
        $roles = new Collection([
            (object) ['id' => 1, 'role_name' => 'admin', 'role_code' => 'admin'],
        ]);
        $rolesRelation = Mockery::mock();
        $rolesRelation->shouldReceive('get')->andReturn($roles);

        $admin = Mockery::mock(SysAdminModel::class)->makePartial();
        $admin->shouldReceive('roles')->andReturn($rolesRelation);

        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldReceive('find')->with(1)->andReturn($admin);

        $action = new GetRoles($adminModel);
        $result = $action->handle(1);

        $this->assertEquals(1, $result['admin_id']);
        $this->assertIsArray($result['roles']);
    }

    public function testGetRolesAdminNotFound(): void
    {
        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldReceive('find')->with(999)->andReturn(null);

        $action = new GetRoles($adminModel);

        $this->expectException(BusinessException::class);
        $action->handle(999);
    }

    public function testGetPermissionsClassExists(): void
    {
        $this->assertTrue(class_exists(GetPermissions::class));
    }

    public function testGetPermissionsOutputFormat(): void
    {
        $result = [
            'admin_id' => 1,
            'permissions' => [
                ['role_id' => 1, 'role_name' => 'admin', 'role_code' => 'admin'],
            ],
        ];

        $this->assertArrayHasKey('admin_id', $result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertEquals(1, $result['permissions'][0]['role_id']);
    }

    public function testAssignRolesNotSuperAdmin(): void
    {
        $this->mockCurrentUser(['id' => 2, 'is_admin' => 0]);

        $adminModel = Mockery::mock(SysAdminModel::class);
        $roleModel = Mockery::mock(SysRoleModel::class);

        $action = new AssignRoles($adminModel, $roleModel);

        $this->expectException(BusinessException::class);
        $action->handle(['admin_id' => 2, 'role_ids' => [1]]);
    }

    public function testAssignRolesEmptyAdminId(): void
    {
        $this->mockCurrentUser(['id' => 1, 'is_admin' => 1]);

        $adminModel = Mockery::mock(SysAdminModel::class);
        $roleModel = Mockery::mock(SysRoleModel::class);

        $action = new AssignRoles($adminModel, $roleModel);

        $this->expectException(BusinessException::class);
        $action->handle(['admin_id' => 0, 'role_ids' => [1]]);
    }

    public function testAssignRolesEmptyRoleIds(): void
    {
        $this->mockCurrentUser(['id' => 1, 'is_admin' => 1]);

        $adminModel = Mockery::mock(SysAdminModel::class);
        $roleModel = Mockery::mock(SysRoleModel::class);

        $action = new AssignRoles($adminModel, $roleModel);

        $this->expectException(BusinessException::class);
        $action->handle(['admin_id' => 1, 'role_ids' => []]);
    }

    public function testAssignRolesAdminNotFound(): void
    {
        $this->mockCurrentUser(['id' => 1, 'is_admin' => 1]);

        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldReceive('find')->with(2)->andReturn(null);

        $roleModel = Mockery::mock(SysRoleModel::class);
        $roleModel->shouldReceive('whereIn')->with('id', [1])->andReturnSelf();
        $roleModel->shouldReceive('get')->andReturn(new Collection([]));

        $action = new AssignRoles($adminModel, $roleModel);

        $this->expectException(BusinessException::class);
        $action->handle(['admin_id' => 2, 'role_ids' => [1]]);
    }

    public function testAssignRolesToSuperAdmin(): void
    {
        $this->mockCurrentUser(['id' => 1, 'is_admin' => 1]);

        $admin = Mockery::mock(SysAdminModel::class)->makePartial();
        $admin->is_admin = 1;

        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldReceive('find')->with(1)->andReturn($admin);

        $roleModel = Mockery::mock(SysRoleModel::class);

        $action = new AssignRoles($adminModel, $roleModel);

        $this->expectException(BusinessException::class);
        $action->handle(['admin_id' => 1, 'role_ids' => [1]]);
    }

    public function testRevokeRoleEmptyAdminId(): void
    {
        $adminModel = Mockery::mock(SysAdminModel::class);
        $roleModel = Mockery::mock(SysRoleModel::class);

        $action = new RevokeRole($adminModel, $roleModel);

        $this->expectException(BusinessException::class);
        $action->handle(['admin_id' => 0, 'role_ids' => [1]]);
    }

    public function testRevokeRoleAdminNotFound(): void
    {
        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldReceive('find')->with(999)->andReturn(null);

        $roleModel = Mockery::mock(SysRoleModel::class);
        $roleModel->shouldReceive('whereIn')->with('id', [1])->andReturnSelf();
        $roleModel->shouldReceive('get')->andReturn(new Collection([]));

        $action = new RevokeRole($adminModel, $roleModel);

        $this->expectException(BusinessException::class);
        $action->handle(['admin_id' => 999, 'role_ids' => [1]]);
    }

    public function testRevokeRolePartialRolesNotFound(): void
    {
        $role1 = new stdClass();
        $role1->id = 1;

        $admin = Mockery::mock(SysAdminModel::class)->makePartial();
        $admin->roles = new Collection([$role1]);

        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldReceive('find')->with(1)->andReturn($admin);

        $roles = Mockery::mock();
        $roles->shouldReceive('count')->andReturn(0);

        $roleModel = Mockery::mock(SysRoleModel::class);
        $roleModel->shouldReceive('whereIn')->with('id', [99])->andReturnSelf();
        $roleModel->shouldReceive('get')->andReturn($roles);

        $action = new RevokeRole($adminModel, $roleModel);

        $this->expectException(BusinessException::class);
        $action->handle(['admin_id' => 1, 'role_ids' => [99]]);
    }

    private function mockCurrentUser(array $user): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('current_user')->andReturn($user);
        Context::set(ServerRequestInterface::class, $request);
    }
}
