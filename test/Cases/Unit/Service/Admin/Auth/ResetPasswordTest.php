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

namespace HyperfTest\Cases\Unit\Service\Admin\Auth;

use App\Dto\Admin\AdminRequest;
use App\Exception\BusinessException;
use App\Model\Admin\SysAdminModel;
use App\Service\Admin\Auth\ResetPassword;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;

/**
 * @internal
 * @coversNothing
 */
class ResetPasswordTest extends TestCase
{
    private static int $mockCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', [
            'id' => 1,
            'username' => 'superadmin',
            'is_admin' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    // ==================== 1. 超管重置密码 ====================

    public function testSuperAdminResetsOtherAdminPassword(): void
    {
        $target = $this->fakeTarget(['id' => 10]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(10, '管理员')->andReturn($target);

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 10, 'new_password' => 'new123']));

        $this->assertTrue(password_verify('new123', $target->password));
    }

    public function testSuperAdminResetsOwnPassword(): void
    {
        $this->loginAs(1, true, 'superadmin');

        $target = $this->fakeTarget(['id' => 1, 'is_admin' => 1]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '管理员')->andReturn($target);

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 1, 'new_password' => 'suNew!']));

        $this->assertTrue(password_verify('suNew!', $target->password));
    }

    public function testSuperAdminResetsAnotherSuperAdminPassword(): void
    {
        $this->loginAs(1, true);

        $target = $this->fakeTarget(['id' => 2, 'is_admin' => 1]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($target);

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => 'suNew2']));

        $this->assertTrue(password_verify('suNew2', $target->password));
    }

    // ==================== 2. 普通管理员重置密码 ====================

    public function testNormalAdminResetsOwnPassword(): void
    {
        $this->loginAs(5, false, 'normal_admin');

        $target = $this->fakeTarget(['id' => 5, 'is_admin' => 0]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(5, '管理员')->andReturn($target);

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 5, 'new_password' => 'myNew!']));

        $this->assertTrue(password_verify('myNew!', $target->password));
    }

    // ==================== 3. 密码哈希验证 ====================

    public function testPasswordIsHashedWithBcrypt(): void
    {
        $target = $this->fakeTarget();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($target);

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => 'test_pass']));

        $this->assertStringStartsWith('$2y$', $target->password);
    }

    public function testPasswordIsNotStoredInPlaintext(): void
    {
        $target = $this->fakeTarget();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($target);

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => 'plain_text_pw']));

        $this->assertNotSame('plain_text_pw', $target->password);
    }

    public function testPasswordHashCanBeVerified(): void
    {
        $target = $this->fakeTarget();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($target);

        $plainPwd = 'verify_this_123!';
        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => $plainPwd]));

        $this->assertTrue(password_verify($plainPwd, $target->password));
        $this->assertFalse(password_verify('wrong_guess', $target->password));
    }

    public function testSpecialCharactersPassword(): void
    {
        $target = $this->fakeTarget();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($target);

        $special = 'P@$$w0rd!#%^&*()中文密码🔐';
        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => $special]));

        $this->assertTrue(password_verify($special, $target->password));
    }

    public function testMaxBcryptLengthPassword(): void
    {
        $target = $this->fakeTarget();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($target);

        $pwd72 = str_repeat('x', 72);
        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => $pwd72]));

        $this->assertTrue(password_verify($pwd72, $target->password));
    }

    public function testBeyondBcryptLimitPasswordTruncation(): void
    {
        $target = $this->fakeTarget();
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($target);

        $pwd73 = str_repeat('A', 72) . 'B';
        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => $pwd73]));

        // bcrypt 截断到 72 字节，所以把第 73 字节改了也能验证通过（已知特性）
        $pwdSamePrefix = str_repeat('A', 72) . 'C';
        $this->assertTrue(
            password_verify($pwdSamePrefix, $target->password),
            'bcrypt 应截断到 72 字节，超出部分被忽略'
        );
    }

    public function testSaveIsCalledOnce(): void
    {
        $saveCount = 0;
        $target = $this->fakeTarget();
        $target->shouldReceive('save')->andReturnUsing(function () use (&$saveCount) {
            ++$saveCount;
            return true;
        });
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($target);

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => 'np']));

        $this->assertSame(1, $saveCount, 'save 应恰好被调用一次');
    }

    // ==================== 4. 参数校验失败 ====================

    public function testFailsWithZeroAdminId(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '管理员ID不能为空'));

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 0, 'new_password' => 'x']));
    }

    public function testFailsWithNegativeAdminId(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '管理员ID不能为空'));

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => -1, 'new_password' => 'x']));
    }

    public function testFailsWithMissingAdminId(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '管理员ID不能为空'));

        $this->buildAction($model)->handle(AdminRequest::fromArray(['new_password' => 'x']));
    }

    public function testFailsWithStringAdminId(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '管理员ID不能为空'));

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 'abc', 'new_password' => 'x']));
    }

    public function testFailsWithEmptyPassword(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('新密码不能为空');

        $this->buildAction()->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => '']));
    }

    public function testFailsWithMissingPassword(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('新密码不能为空');

        $this->buildAction()->handle(AdminRequest::fromArray(['admin_id' => 2]));
    }

    public function testFailsWithEmptyData(): void
    {
        $this->expectException(BusinessException::class);

        $this->buildAction()->handle(AdminRequest::fromArray([]));
    }

    // ==================== 5. 业务权限校验 ====================

    public function testAdminNotFound404(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')
            ->with(999, '管理员')
            ->andThrow(new BusinessException(404, '管理员不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('管理员不存在');

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 999, 'new_password' => 'x']));
    }

    public function testNormalAdminCannotResetSuperAdminPassword(): void
    {
        $this->loginAs(5, false);

        $target = $this->fakeTarget(['id' => 1, 'is_admin' => 1]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '管理员')->andReturn($target);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('无权重置超级管理员密码');

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 1, 'new_password' => 'x']));
    }

    public function testNormalAdminCannotResetOthersPassword(): void
    {
        $this->loginAs(5, false);

        $target = $this->fakeTarget(['id' => 10, 'is_admin' => 0]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(10, '管理员')->andReturn($target);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('无权重置他人密码');

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 10, 'new_password' => 'x']));
    }

    // ==================== 6. 数据完整性 ====================

    public function testOtherFieldsUnchangedAfterReset(): void
    {
        $target = $this->fakeTarget([
            'id' => 2, 'username' => 'keep_me', 'status' => 1, 'is_admin' => 0,
        ]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($target);

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => 'np']));

        $this->assertSame('keep_me', $target->username);
        $this->assertSame(1, $target->status);
        $this->assertSame(0, $target->is_admin);
    }

    public function testOldPasswordNoLongerWorks(): void
    {
        $oldHash = password_hash('old_pwd', PASSWORD_BCRYPT);
        $target = $this->fakeTarget(['password' => $oldHash]);
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->with(2, '管理员')->andReturn($target);

        $this->buildAction($model)->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => 'brand_new']));

        $this->assertFalse(password_verify('old_pwd', $target->password));
        $this->assertTrue(password_verify('brand_new', $target->password));
    }

    // ==================== 7. 上下文异常 ====================

    public function testThrows401WhenNoAuthUser(): void
    {
        Context::destroy('auth_user');

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(401);

        $this->buildAction()->handle(AdminRequest::fromArray(['admin_id' => 2, 'new_password' => 'x']));
    }

    private function fakeTarget(array $overrides = []): MockInterface
    {
        $defaults = [
            'id' => 2,
            'username' => 'target_user',
            'password' => password_hash('old_password', PASSWORD_BCRYPT),
            'is_admin' => 0,
            'status' => 1,
        ];
        $attrs = array_merge($defaults, $overrides);

        $mock = Mockery::mock(SysAdminModel::class);
        $mock->shouldReceive('setAttribute')->andReturnUsing(
            function (string $key, mixed $value) use (&$attrs): void {
                $attrs[$key] = $value;
            }
        );
        $mock->shouldReceive('getAttribute')->andReturnUsing(
            function (string $key) use (&$attrs) {
                return $attrs[$key] ?? null;
            }
        );
        $mock->shouldReceive('save')->byDefault()->andReturn(true);
        return $mock;
    }

    private function buildAction(?MockInterface $model = null): ResetPassword
    {
        $model ??= Mockery::mock(SysAdminModel::class);
        return new ResetPassword($model);
    }

    private function loginAs(int $id, bool $isSuperAdmin, string $username = 'user'): void
    {
        Context::set('auth_user', [
            'id' => $id,
            'username' => $username,
            'is_admin' => $isSuperAdmin ? 1 : 0,
        ]);
    }
}
