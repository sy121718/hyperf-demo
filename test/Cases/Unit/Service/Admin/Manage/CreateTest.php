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
use App\Service\Admin\Manage\Create;
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

    public function testCreateSuccessReturnsData(): void
    {
        $result = (new Create($this->mockModel(), $this->mockAssignRoles()))
            ->handle($this->dto());

        $this->assertArrayHasKey('id', $result);
        $this->assertSame(100, $result['id']);
    }

    public function testCreateHashesPassword(): void
    {
        $capturedData = null;
        $model = $this->mockModel();
        $model->shouldReceive('create')->once()->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            return true;
        })->andReturn($this->fakeCreated());

        (new Create($model, $this->mockAssignRoles()))
            ->handle($this->dto(['password' => 'secret123']));

        $this->assertNotSame('secret123', $capturedData['password']);
        $this->assertTrue(password_verify('secret123', $capturedData['password']));
    }

    public function testCreateWithRolesCallsAssignRoles(): void
    {
        $rolesCalled = false;

        $assign = Mockery::mock(AssignRoles::class);
        $assign->shouldReceive('handle')->withArgs(function (int $adminId, array $roleIds) use (&$rolesCalled) {
            $rolesCalled = true;
            return $adminId === 100 && $roleIds === [1, 2, 3];
        })->andReturnNull();

        (new Create($this->mockModel(), $assign))
            ->handle($this->dto(['role_ids' => [1, 2, 3]]));

        $this->assertTrue($rolesCalled);
    }

    public function testCreateWithoutRolesDoesNotCallAssignRoles(): void
    {
        $assignCalled = false;
        $assign = Mockery::mock(AssignRoles::class);
        $assign->shouldReceive('handle')->andReturnUsing(function () use (&$assignCalled) {
            $assignCalled = true;
        });

        (new Create($this->mockModel(), $assign))->handle($this->dto());

        $this->assertFalse($assignCalled);
    }

    public function testCreateStripsRoleIdsFromDbData(): void
    {
        $capturedData = null;
        $model = $this->mockModel();
        $model->shouldReceive('create')->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            return true;
        })->andReturn($this->fakeCreated());

        (new Create($model, $this->mockAssignRoles()))
            ->handle($this->dto(['role_ids' => [1, 2]]));

        $this->assertArrayNotHasKey('role_ids', $capturedData);
    }

    public function testCreateOmitsUnsubmittedFields(): void
    {
        $capturedData = null;
        $model = $this->mockModel();
        $model->shouldReceive('create')->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            return true;
        })->andReturn($this->fakeCreated());

        (new Create($model, $this->mockAssignRoles()))->handle($this->dto());

        // 未提交的字段不写入，交给数据库默认值
        $this->assertArrayNotHasKey('status', $capturedData);
        $this->assertArrayNotHasKey('phone', $capturedData);
    }

    public function testCreateKeepsPhoneField(): void
    {
        $capturedData = null;
        $model = $this->mockModel();
        $model->shouldReceive('create')->withArgs(function (array $data) use (&$capturedData) {
            $capturedData = $data;
            return true;
        })->andReturn($this->fakeCreated());

        (new Create($model, $this->mockAssignRoles()))
            ->handle($this->dto(['phone' => '13812345678']));

        $this->assertSame('13812345678', $capturedData['phone']);
    }

    // ==================== 创建失败 ====================

    public function testCreateFailsWithDuplicateUsername(): void
    {
        $model = $this->mockModel(usernameUnique: false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('用户名已存在');

        (new Create($model, $this->mockAssignRoles()))->handle($this->dto());
    }

    public function testCreateRejectsSuperAdminFlag(): void
    {
        // 超管只由初始化脚本产生，创建接口不允许设置
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);
        $model->shouldReceive('create')->never();

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('不能通过创建接口设置超级管理员');

        (new Create($model, $this->mockAssignRoles()))->handle($this->dto(['is_admin' => 1]));
    }

    public function testCreateStoresZeroWhenIsAdminExplicitlyFalse(): void
    {
        // 显式传 0 是允许的，用来表达“这是普通管理员”
        $captured = null;
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);
        $model->shouldReceive('create')->withArgs(function (array $data) use (&$captured) {
            $captured = $data;
            return true;
        })->andReturn($this->fakeCreated());

        (new Create($model, $this->mockAssignRoles()))->handle($this->dto(['is_admin' => 0]));

        $this->assertSame(0, $captured['is_admin']);
    }

    private function mockModel(bool $usernameUnique = true): MockInterface
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('isFieldUnique')->byDefault()->andReturn($usernameUnique);
        $model->shouldReceive('create')->byDefault()->andReturn($this->fakeCreated());

        return $model;
    }

    private function fakeCreated(): stdClass
    {
        $created = Mockery::mock(stdClass::class);
        $created->id = 100;
        $created->shouldReceive('fresh->toArray')->andReturn(['id' => 100, 'username' => 'newuser']);

        return $created;
    }

    private function mockAssignRoles(): MockInterface
    {
        $assign = Mockery::mock(AssignRoles::class);
        $assign->shouldReceive('handle')->byDefault()->andReturnNull();
        return $assign;
    }

    private function dto(array $overrides = []): AdminRequest
    {
        return AdminRequest::fromArray(array_merge([
            'username' => 'newuser',
            'password' => 'pass123',
        ], $overrides));
    }
}
