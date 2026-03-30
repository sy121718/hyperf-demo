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

namespace HyperfTest\Cases\Unit\Service\Admin\Profile;

use App\Exception\BusinessException;
use App\Model\Admin\SysAdminModel;
use App\Service\Admin\Profile\ChangePassword;
use App\Service\Admin\Profile\GetProfile;
use App\Service\Admin\Profile\UpdateProfile;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\Testing\TestCase;
use Mockery;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class ProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 5, 'username' => 'testuser', 'is_admin' => 0]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testGetProfileSuccess(): void
    {
        $admin = Mockery::mock(stdClass::class);
        $admin->shouldReceive('toArray')->andReturn([
            'id' => 5, 'username' => 'testuser', 'password' => 'hashed', 'roles' => [],
        ]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('find')->with(5)->andReturn($admin);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('with')->andReturn($builder);

        $result = (new GetProfile($model))->handle();

        $this->assertSame(5, $result['id']);
        $this->assertArrayNotHasKey('password', $result);
    }

    public function testGetProfileNotLoggedIn(): void
    {
        Context::set('auth_user', ['id' => 0]);

        $model = Mockery::mock(SysAdminModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(401);

        (new GetProfile($model))->handle();
    }

    public function testUpdateProfileSuccess(): void
    {
        $admin = Mockery::mock(stdClass::class);
        $admin->email = 'old@test.com';
        $admin->phone = '111';
        $admin->shouldReceive('update')->once()->andReturn(true);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(5)->andReturn($admin);

        $result = (new UpdateProfile($model))->handle(['name' => 'New Name']);

        $this->assertTrue($result);
    }

    public function testUpdateProfileEmailTaken(): void
    {
        $admin = Mockery::mock(stdClass::class);
        $admin->email = 'old@test.com';
        $admin->phone = '111';

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(5)->andReturn($admin);
        $model->shouldReceive('where->where->exists')->andReturn(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('邮箱已被占用');

        (new UpdateProfile($model))->handle(['email' => 'taken@test.com']);
    }

    public function testChangePasswordSuccess(): void
    {
        $hashed = password_hash('old123', PASSWORD_DEFAULT);

        $admin = Mockery::mock(stdClass::class);
        $admin->password = $hashed;
        $admin->shouldReceive('save')->once()->andReturn(true);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(5)->andReturn($admin);

        $result = (new ChangePassword($model))->handle('old123', 'new456');

        $this->assertTrue($result);
    }

    public function testChangePasswordWrongOld(): void
    {
        $hashed = password_hash('correct', PASSWORD_DEFAULT);

        $admin = Mockery::mock(stdClass::class);
        $admin->password = $hashed;

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('find')->with(5)->andReturn($admin);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('旧密码错误');

        (new ChangePassword($model))->handle('wrong', 'new456');
    }

    public function testChangePasswordNotLoggedIn(): void
    {
        Context::set('auth_user', ['id' => 0]);

        $model = Mockery::mock(SysAdminModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(401);

        (new ChangePassword($model))->handle('old', 'new');
    }
}
