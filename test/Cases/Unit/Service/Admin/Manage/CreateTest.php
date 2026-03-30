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

use App\Exception\BusinessException;
use App\Model\Admin\SysAdminModel;
use App\Service\Admin\Manage\Create;
use App\Service\Admin\Manage\Dto\CreateAdminDto;
use App\Service\Role\AssignRoles;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class CreateTest extends TestCase
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

    // ==================== 创建成功 ====================

    public function testCreateSuccessReturnsId(): void
    {
        $model = $this->mockModel();
        $action = new Create($model, $this->mockAssignRoles());

        $result = $action->handle($this->dto());

        $this->assertArrayHasKey('id', $result);
        $this->assertSame(100, $result['id']);
    }

    public function testCreateHashesPassword(): void
    {
        $capturedData = null;
        $model = $this->mockModel();
        $model->shouldReceive('create')->once()->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            $obj = Mockery::mock(stdClass::class);
            $obj->id = 1;
            return true;
        })->andReturnUsing(function () {
            $obj = Mockery::mock(stdClass::class);
            $obj->id = 1;
            return $obj;
        });

        $action = new Create($model, $this->mockAssignRoles());
        $action->handle($this->dto(['password' => 'secret123']));

        $this->assertNotSame('secret123', $capturedData['password']);
        $this->assertTrue(password_verify('secret123', $capturedData['password']));
    }

    public function testCreateSetsCreateByAndUpdateBy(): void
    {
        $capturedData = null;
        $model = $this->mockModel();
        $model->shouldReceive('create')->once()->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            return true;
        })->andReturnUsing(function () {
            $obj = Mockery::mock(stdClass::class);
            $obj->id = 1;
            return $obj;
        });

        $action = new Create($model, $this->mockAssignRoles());
        $action->handle($this->dto());

        $this->assertSame(1, $capturedData['create_by']);
        $this->assertSame(1, $capturedData['update_by']);
    }

    public function testCreateWithRolesCallsAssignRoles(): void
    {
        $model = $this->mockModel();
        $created = Mockery::mock(stdClass::class);
        $created->id = 50;
        $model->shouldReceive('create')->andReturn($created);

        $rolesCalled = false;
        $assign = Mockery::mock(AssignRoles::class);
        $assign->shouldReceive('handle')->withArgs(function (int $adminId, array $roleIds) use (&$rolesCalled) {
            $rolesCalled = true;
            return $adminId === 50 && $roleIds === [1, 2, 3];
        })->andReturnNull();

        $action = new Create($model, $assign);
        $action->handle($this->dto(['roleIds' => [1, 2, 3]]));

        $this->assertTrue($rolesCalled);
    }

    public function testCreateWithoutRolesDoesNotCallAssignRoles(): void
    {
        $model = $this->mockModel();
        $assignCalled = false;
        $assign = Mockery::mock(AssignRoles::class);
        $assign->shouldReceive('handle')->andReturnUsing(function () use (&$assignCalled) {
            $assignCalled = true;
        });

        $action = new Create($model, $assign);
        $action->handle($this->dto());

        $this->assertFalse($assignCalled);
    }

    public function testCreateStripsRoleIdsFromDbData(): void
    {
        $capturedData = null;
        $model = $this->mockModel();
        $model->shouldReceive('create')->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            return true;
        })->andReturnUsing(function () {
            $obj = Mockery::mock(stdClass::class);
            $obj->id = 1;
            return $obj;
        });

        $action = new Create($model, $this->mockAssignRoles());
        $action->handle($this->dto(['roleIds' => [1, 2]]));

        $this->assertArrayNotHasKey('role_ids', $capturedData);
    }

    public function testCreateWithDefaultStatus(): void
    {
        $capturedData = null;
        $model = $this->mockModel();
        $model->shouldReceive('create')->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            return true;
        })->andReturnUsing(function () {
            $obj = Mockery::mock(stdClass::class);
            $obj->id = 1;
            return $obj;
        });

        $action = new Create($model, $this->mockAssignRoles());
        $action->handle($this->dto());

        $this->assertSame(1, $capturedData['status']);
    }

    // ==================== 创建失败 ====================

    public function testCreateFailsWithEmptyUsername(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('用户名不能为空');

        $model = $this->mockModel();
        $action = new Create($model, $this->mockAssignRoles());
        $action->handle(new CreateAdminDto(username: '', password: 'pass123'));
    }

    public function testCreateFailsWithEmptyPassword(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('密码不能为空');

        $model = $this->mockModel();
        $action = new Create($model, $this->mockAssignRoles());
        $action->handle(new CreateAdminDto(username: 'user', password: ''));
    }

    public function testCreateFailsWithDuplicateUsername(): void
    {
        $model = $this->mockModel(usernameUnique: false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('用户名已存在');

        $action = new Create($model, $this->mockAssignRoles());
        $action->handle($this->dto());
    }

    // ==================== DTO 映射 ====================

    public function testDtoMobileMapsToDatabasePhone(): void
    {
        $capturedData = null;
        $model = $this->mockModel();
        $model->shouldReceive('create')->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            return true;
        })->andReturnUsing(function () {
            $obj = Mockery::mock(stdClass::class);
            $obj->id = 1;
            return $obj;
        });

        $action = new Create($model, $this->mockAssignRoles());
        $action->handle($this->dto(['mobile' => '13812345678']));

        $this->assertArrayHasKey('phone', $capturedData);
        $this->assertSame('13812345678', $capturedData['phone']);
        $this->assertArrayNotHasKey('mobile', $capturedData);
    }

    private function mockModel(bool $usernameUnique = true): MockInterface
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('isFieldUnique')->byDefault()->andReturn($usernameUnique);

        $created = Mockery::mock(stdClass::class);
        $created->id = 100;
        $model->shouldReceive('create')->byDefault()->andReturn($created);

        return $model;
    }

    private function mockAssignRoles(): MockInterface
    {
        $assign = Mockery::mock(AssignRoles::class);
        $assign->shouldReceive('handle')->byDefault()->andReturnNull();
        return $assign;
    }

    private function dto(array $overrides = []): CreateAdminDto
    {
        $defaults = ['username' => 'newuser', 'password' => 'pass123'];
        return new CreateAdminDto(...array_merge($defaults, $overrides));
    }
}
