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
use App\Model\Admin\SysAdminRoleModel;
use App\Model\Admin\SysAdminSessionsModel;
use App\Service\Admin\Manage\Delete;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class DeleteTest extends TestCase
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

    // ==================== 删除成功 ====================

    public function testDeleteSuccess(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(10)->andReturn($admin);

        $result = $this->buildAction($model)->handle(['id' => 10]);

        $this->assertTrue($result);
    }

    public function testDeleteCallsSoftDeleteOnAdmin(): void
    {
        $deleteCalled = false;
        $admin = $this->fakeAdmin();
        $admin->shouldReceive('delete')->andReturnUsing(function () use (&$deleteCalled) {
            $deleteCalled = true;
            return true;
        });

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(10)->andReturn($admin);

        $this->buildAction($model)->handle(['id' => 10]);

        $this->assertTrue($deleteCalled);
    }

    // ==================== 删除失败 ====================

    public function testDeleteFailsWithZeroId(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        $this->buildAction()->handle(['id' => 0]);
    }

    public function testDeleteFailsWithMissingId(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        $this->buildAction()->handle([]);
    }

    public function testDeleteFailsWhenAdminNotFound(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        $this->buildAction($model)->handle(['id' => 999]);
    }

    public function testDeleteFailsForSuperAdmin(): void
    {
        $admin = $this->fakeAdmin(['is_admin' => 1]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(10)->andReturn($admin);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('不能删除超级管理员');

        $this->buildAction($model)->handle(['id' => 10]);
    }

    public function testDeleteSuperAdminDoesNotDeleteAnything(): void
    {
        $admin = $this->fakeAdmin(['is_admin' => 1]);
        $admin->shouldNotReceive('delete');
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(10)->andReturn($admin);

        try {
            $this->buildAction($model)->handle(['id' => 10]);
        } catch (BusinessException) {
        }

        $this->addToAssertionCount(1);
    }

    // ==================== 关联数据清理 ====================

    public function testDeleteCleansUpRoles(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(10)->andReturn($admin);

        $roleDel = false;
        $roleBuilder = Mockery::mock(Builder::class);
        $roleBuilder->shouldReceive('delete')->andReturnUsing(function () use (&$roleDel) {
            $roleDel = true;
            return 1;
        });
        $roleModel = Mockery::mock(SysAdminRoleModel::class);
        $roleModel->shouldReceive('where')->with('admin_id', 10)->andReturn($roleBuilder);

        $sessionModel = Mockery::mock(SysAdminSessionsModel::class);
        $this->mockDeleteChain($sessionModel);

        $action = new Delete($model, $roleModel, $sessionModel);
        $action->handle(['id' => 10]);

        $this->assertTrue($roleDel);
    }

    public function testDeleteCleansUpSessions(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(10)->andReturn($admin);

        $roleModel = Mockery::mock(SysAdminRoleModel::class);
        $this->mockDeleteChain($roleModel);

        $sessionDel = false;
        $sessionBuilder = Mockery::mock(Builder::class);
        $sessionBuilder->shouldReceive('delete')->andReturnUsing(function () use (&$sessionDel) {
            $sessionDel = true;
            return 1;
        });
        $sessionModel = Mockery::mock(SysAdminSessionsModel::class);
        $sessionModel->shouldReceive('where')->with('admin_id', 10)->andReturn($sessionBuilder);

        $action = new Delete($model, $roleModel, $sessionModel);
        $action->handle(['id' => 10]);

        $this->assertTrue($sessionDel);
    }

    private function fakeAdmin(array $overrides = []): MockInterface
    {
        $defaults = ['id' => 10, 'username' => 'victim', 'is_admin' => 0, 'status' => 1];
        $attrs = array_merge($defaults, $overrides);

        $admin = Mockery::mock(stdClass::class);
        foreach ($attrs as $k => $v) {
            $admin->{$k} = $v;
        }
        $admin->shouldReceive('delete')->byDefault()->andReturn(true);
        $admin->shouldReceive('toArray')->byDefault()->andReturn($attrs);
        return $admin;
    }

    private function mockDeleteChain(MockInterface $model): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('delete')->andReturn(1);
        $model->shouldReceive('where')->with('admin_id', Mockery::any())->andReturn($builder);
    }

    private function buildAction(?MockInterface $adminModel = null): Delete
    {
        $adminModel ??= Mockery::mock(SysAdminModel::class);

        $roleModel = Mockery::mock(SysAdminRoleModel::class);
        $this->mockDeleteChain($roleModel);

        $sessionModel = Mockery::mock(SysAdminSessionsModel::class);
        $this->mockDeleteChain($sessionModel);

        return new Delete($adminModel, $roleModel, $sessionModel);
    }
}
