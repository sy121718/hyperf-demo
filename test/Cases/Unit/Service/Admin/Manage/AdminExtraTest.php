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
use App\Model\Admin\SysAdminSessionsModel;
use App\Service\Admin\Manage\BanAdmin;
use App\Service\Admin\Manage\ForceLogout;
use App\Service\Admin\Manage\GetAllAdmins;
use App\Service\Admin\Manage\KickUser;
use App\Service\Admin\Manage\ResetFailureCount;
use App\Service\Admin\Manage\UnbanAdmin;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Hyperf\Testing\TestCase;
use Mockery;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class AdminExtraTest extends TestCase
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

    // ==================== BanAdmin ====================

    public function testBanAdminSuccess(): void
    {
        $admin = Mockery::mock(stdClass::class);
        $admin->is_admin = 0;
        $admin->shouldReceive('save')->once()->andReturn(true);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(5)->andReturn($admin);

        (new BanAdmin($model))->handle(['id' => 5]);

        $this->assertSame(0, $admin->status);
    }

    public function testBanAdminNotFound(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new BanAdmin($model))->handle(['id' => 999]);
    }

    public function testBanAdminZeroId(): void
    {
        $model = Mockery::mock(SysAdminModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        (new BanAdmin($model))->handle(['id' => 0]);
    }

    public function testBanSuperAdminFails(): void
    {
        $admin = Mockery::mock(stdClass::class);
        $admin->is_admin = 1;

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(1)->andReturn($admin);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('超级管理员不能封禁');

        (new BanAdmin($model))->handle(['id' => 1]);
    }

    public function testBanAdminMissingId(): void
    {
        $model = Mockery::mock(SysAdminModel::class);

        $this->expectException(BusinessException::class);

        (new BanAdmin($model))->handle([]);
    }

    // ==================== UnbanAdmin ====================

    public function testUnbanAdminSuccess(): void
    {
        $admin = Mockery::mock(stdClass::class);
        $admin->shouldReceive('save')->once()->andReturn(true);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(5)->andReturn($admin);

        (new UnbanAdmin($model))->handle(['id' => 5]);

        $this->assertSame(1, $admin->status);
        $this->assertSame(0, $admin->login_failure_count);
    }

    public function testUnbanAdminNotFound(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new UnbanAdmin($model))->handle(['id' => 999]);
    }

    public function testUnbanAdminZeroId(): void
    {
        $model = Mockery::mock(SysAdminModel::class);

        $this->expectException(BusinessException::class);

        (new UnbanAdmin($model))->handle(['id' => 0]);
    }

    // ==================== ForceLogout ====================

    public function testForceLogoutAllSessions(): void
    {
        $admin = Mockery::mock(stdClass::class);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(5)->andReturn($admin);

        $sessBuilder = Mockery::mock(Builder::class);
        $sessBuilder->shouldReceive('update')->with(['status' => 0])->once()->andReturn(3);

        $sessModel = Mockery::mock(SysAdminSessionsModel::class);
        $sessModel->shouldReceive('where')->with('admin_id', 5)->andReturn($sessBuilder);

        (new ForceLogout($model, $sessModel))->handle(['admin_id' => 5]);

        $this->assertTrue(true);
    }

    public function testForceLogoutSpecificSession(): void
    {
        $admin = Mockery::mock(stdClass::class);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(5)->andReturn($admin);

        $sessBuilder = Mockery::mock(Builder::class);
        $sessBuilder->shouldReceive('where')->with('id', 10)->andReturnSelf();
        $sessBuilder->shouldReceive('update')->with(['status' => 0])->once()->andReturn(1);

        $sessModel = Mockery::mock(SysAdminSessionsModel::class);
        $sessModel->shouldReceive('where')->with('admin_id', 5)->andReturn($sessBuilder);

        (new ForceLogout($model, $sessModel))->handle(['admin_id' => 5, 'session_id' => 10]);

        $this->assertTrue(true);
    }

    public function testForceLogoutAdminNotFound(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);
        $sessModel = Mockery::mock(SysAdminSessionsModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new ForceLogout($model, $sessModel))->handle(['admin_id' => 999]);
    }

    public function testForceLogoutZeroAdminId(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $sessModel = Mockery::mock(SysAdminSessionsModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        (new ForceLogout($model, $sessModel))->handle(['admin_id' => 0]);
    }

    // ==================== ResetFailureCount ====================

    public function testResetFailureCountSuccess(): void
    {
        $admin = Mockery::mock(stdClass::class);
        $admin->shouldReceive('save')->once()->andReturn(true);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(5)->andReturn($admin);

        (new ResetFailureCount($model))->handle(['id' => 5]);

        $this->assertSame(0, $admin->login_failure_count);
    }

    public function testResetFailureCountNotFound(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new ResetFailureCount($model))->handle(['id' => 999]);
    }

    public function testResetFailureCountZeroId(): void
    {
        $model = Mockery::mock(SysAdminModel::class);

        $this->expectException(BusinessException::class);

        (new ResetFailureCount($model))->handle(['id' => 0]);
    }

    // ==================== GetAllAdmins ====================

    public function testGetAllAdminsDefault(): void
    {
        $expected = [
            ['id' => 1, 'username' => 'admin', 'name' => '超管'],
            ['id' => 2, 'username' => 'editor', 'name' => '编辑'],
        ];

        $collection = Mockery::mock(Collection::class);
        $collection->shouldReceive('toArray')->andReturn($expected);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('when')->andReturnSelf();
        $builder->shouldReceive('orderBy')->with('id', 'desc')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn($collection);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('select')->andReturn($builder);

        $result = (new GetAllAdmins($model))->handle();

        $this->assertCount(2, $result);
    }

    public function testGetAllAdminsWithStatusFilter(): void
    {
        $collection = Mockery::mock(Collection::class);
        $collection->shouldReceive('toArray')->andReturn([]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('when')->andReturnUsing(function ($cond, $fn) use ($builder) {
            if ($cond) {
                $fn($builder);
            }
            return $builder;
        });
        $builder->shouldReceive('where')->with('status', 1)->andReturnSelf();
        $builder->shouldReceive('orderBy')->with('id', 'desc')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn($collection);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('select')->andReturn($builder);

        $result = (new GetAllAdmins($model))->handle(['status' => 1]);

        $this->assertIsArray($result);
    }

    // ==================== KickUser ====================

    public function testKickUserSuccess(): void
    {
        $admin = Mockery::mock(stdClass::class);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(5)->andReturn($admin);

        $sessBuilder = Mockery::mock(Builder::class);
        $sessBuilder->shouldReceive('where')->with('status', 1)->andReturnSelf();
        $sessBuilder->shouldReceive('update')->with(['status' => 0])->once()->andReturn(2);

        $sessModel = Mockery::mock(SysAdminSessionsModel::class);
        $sessModel->shouldReceive('where')->with('admin_id', 5)->andReturn($sessBuilder);

        (new KickUser($model, $sessModel))->handle(5);

        $this->assertTrue(true);
    }

    public function testKickUserNotFound(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);
        $sessModel = Mockery::mock(SysAdminSessionsModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new KickUser($model, $sessModel))->handle(999);
    }

    public function testKickUserZeroId(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $sessModel = Mockery::mock(SysAdminSessionsModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('用户ID不能为空');

        (new KickUser($model, $sessModel))->handle(0);
    }
}
