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

namespace HyperfTest\Cases\Unit\Service\Admin\Manage;

use App\Dto\Admin\AdminRequest;
use App\Exception\BusinessException;
use App\Model\Admin\SysAdminModel;
use App\Service\Admin\Manage\Update;
use App\Service\Role\AssignRoles;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;

/**
 * @internal
 * @coversNothing
 */
class UpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 1, 'username' => 'superadmin', 'is_admin' => 1]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    // ==================== 更新成功 ====================

    public function testUpdateSuccessReturnsData(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->byDefault()->andReturn(true);

        $result = $this->buildAction($model)->handle($this->dto(['username' => 'new_name']));

        $this->assertIsArray($result);
    }

    public function testUpdateCallsUpdateOnModel(): void
    {
        $updateCalled = false;
        $admin = $this->fakeAdmin();
        $admin->shouldReceive('update')->andReturnUsing(function () use (&$updateCalled) {
            $updateCalled = true;
            return true;
        });

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $this->buildAction($model)->handle($this->dto(['name' => 'NewName']));

        $this->assertTrue($updateCalled);
    }

    public function testUpdatePasswordIsHashed(): void
    {
        $capturedData = null;
        $admin = $this->fakeAdmin();
        $admin->shouldReceive('update')->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            return true;
        })->andReturn(true);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $this->buildAction($model)->handle($this->dto(['password' => 'new_pw_123']));

        $this->assertArrayHasKey('password', $capturedData);
        $this->assertTrue(password_verify('new_pw_123', $capturedData['password']));
    }

    public function testUpdateWithoutPasswordDoesNotIncludePasswordField(): void
    {
        $capturedData = null;
        $admin = $this->fakeAdmin();
        $admin->shouldReceive('update')->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            return true;
        })->andReturn(true);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $this->buildAction($model)->handle($this->dto(['name' => 'Bob']));

        $this->assertArrayNotHasKey('password', $capturedData);
    }

    public function testUpdateWithRolesCallsAssignRoles(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $assignCalled = false;
        $assign = Mockery::mock(AssignRoles::class);
        $assign->shouldReceive('handle')->withArgs(function (int $id, array $roles) use (&$assignCalled) {
            $assignCalled = true;
            return $id === 2 && $roles === [3, 4];
        })->andReturnNull();

        (new Update($model, $assign))->handle($this->dto(['role_ids' => [3, 4]]));

        $this->assertTrue($assignCalled);
    }

    // ==================== 更新失败 ====================

    public function testUpdateFailsWhenAdminNotFound(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')
            ->with(999, '管理员')
            ->andThrow(new BusinessException(404, '管理员不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        $this->buildAction($model)->handle(AdminRequest::fromArray(['id' => 999, 'name' => 'x']));
    }

    public function testUpdateFailsWhenDuplicateUsername(): void
    {
        $admin = $this->fakeAdmin(['username' => 'old_name']);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->with(['username' => 'taken_name'], 2)->andReturn(false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('用户名已被占用');

        $this->buildAction($model)->handle($this->dto(['username' => 'taken_name']));
    }

    public function testUpdateFailsWhenDuplicateEmail(): void
    {
        $admin = $this->fakeAdmin(['email' => 'old@test.com']);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')
            ->with(['username' => Mockery::any()], Mockery::any())->andReturn(true);
        $model->shouldReceive('isFieldUnique')
            ->with(['email' => 'taken@test.com'], 2)->andReturn(false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('邮箱已被占用');

        $this->buildAction($model)->handle($this->dto(['email' => 'taken@test.com']));
    }

    // ==================== 权限校验 ====================

    public function testNormalAdminCannotUpdateOthers(): void
    {
        Context::set('auth_user', ['id' => 5, 'username' => 'normal', 'is_admin' => 0]);

        $admin = $this->fakeAdmin(['id' => 10]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(10, '管理员')->andReturn($admin);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('无权修改其他管理员信息');

        $this->buildAction($model)->handle(AdminRequest::fromArray(['id' => 10, 'name' => 'hack']));
    }

    public function testNormalAdminCanUpdateSelf(): void
    {
        Context::set('auth_user', ['id' => 5, 'username' => 'normal', 'is_admin' => 0]);

        $admin = $this->fakeAdmin(['id' => 5]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(5, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $result = $this->buildAction($model)->handle(AdminRequest::fromArray(['id' => 5, 'name' => 'myname']));

        $this->assertIsArray($result);
    }

    public function testNormalAdminCannotChangeIsAdmin(): void
    {
        Context::set('auth_user', ['id' => 5, 'username' => 'normal', 'is_admin' => 0]);

        $admin = $this->fakeAdmin(['id' => 5]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(5, '管理员')->andReturn($admin);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('无权修改管理员类型');

        $this->buildAction($model)->handle(AdminRequest::fromArray(['id' => 5, 'is_admin' => 1]));
    }

    public function testCannotPromoteWhenSuperAdminAlreadyExists(): void
    {
        // 超管全库只能有一个，名额被占用时不能把别人提上来
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->andReturn(true);
        $model->shouldReceive('hasOtherSuperAdmin')->andReturn(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('系统只允许存在一个超级管理员');

        $this->buildAction($model)->handle($this->dto(['is_admin' => 1]));
    }

    public function testCannotDemoteSelf(): void
    {
        // 自己把自己降级会让系统失去唯一超管
        Context::set('auth_user', ['id' => 2, 'username' => 'admin', 'is_admin' => 1]);

        $admin = $this->fakeAdmin(['id' => 2, 'is_admin' => 1]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('不能取消自己的超级管理员身份');

        $this->buildAction($model)->handle($this->dto(['id' => 2, 'is_admin' => 0]));
    }

    public function testDemoteOtherSuperAdminWhenSlotFree(): void
    {
        // 把别的超管降级是允许的（只要不是操作自己）
        $admin = $this->fakeAdmin(['id' => 2, 'is_admin' => 1]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $result = $this->buildAction($model)->handle($this->dto(['id' => 2, 'is_admin' => 0]));

        $this->assertIsArray($result);
    }

    public function testIsAdminUnchangedSkipsSuperAdminChecks(): void
    {
        // 不涉及超管身份变更时不该触发任何额外判断
        $admin = $this->fakeAdmin(['is_admin' => 0]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->andReturn(true);
        $model->shouldReceive('hasOtherSuperAdmin')->never();

        $this->buildAction($model)->handle($this->dto(['id' => 2, 'is_admin' => 0]));
    }

    // ==================== DTO 映射 ====================

    public function testUpdateStripsIdAndRoleIdsFromDbData(): void
    {
        $capturedData = null;
        $admin = $this->fakeAdmin();
        $admin->shouldReceive('update')->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            return true;
        })->andReturn(true);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($admin);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $this->buildAction($model)->handle($this->dto(['name' => 'x', 'role_ids' => [1]]));

        $this->assertArrayNotHasKey('id', $capturedData);
        $this->assertArrayNotHasKey('role_ids', $capturedData);
    }

    private function fakeAdmin(array $overrides = []): MockInterface
    {
        $defaults = [
            'id' => 2, 'username' => 'target', 'name' => 'Target',
            'email' => 'target@test.com', 'phone' => '13800000000',
            'status' => 1, 'is_admin' => 0, 'avatar' => null,
        ];
        $attrs = array_merge($defaults, $overrides);

        $admin = Mockery::mock(SysAdminModel::class);
        $admin->shouldReceive('getAttribute')->andReturnUsing(fn (string $key) => $attrs[$key] ?? null);
        $admin->shouldReceive('__get')->andReturnUsing(fn (string $key) => $attrs[$key] ?? null);
        $admin->shouldReceive('update')->byDefault()->andReturn(true);
        $admin->shouldReceive('fresh')->byDefault()->andReturnSelf();
        $admin->shouldReceive('toArray')->byDefault()->andReturn($attrs);
        return $admin;
    }

    private function buildAction(?MockInterface $model = null, ?MockInterface $assign = null): Update
    {
        $model ??= Mockery::mock(SysAdminModel::class);
        $assign ??= Mockery::mock(AssignRoles::class);
        $assign->shouldReceive('handle')->byDefault()->andReturnNull();
        return new Update($model, $assign);
    }

    private function dto(array $overrides = []): AdminRequest
    {
        return AdminRequest::fromArray(array_merge(['id' => 2], $overrides));
    }
}
