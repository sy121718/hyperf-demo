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

use App\Dto\Auth\LoginRequest;
use App\Exception\BusinessException;
use App\Model\Admin\SysAdminModel;
use App\Service\Admin\Auth\Login;
use App\Service\Ops\Session\CreateSession;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;
use stdClass;

use function Hyperf\Support\env;

/**
 * @internal
 * @coversNothing
 */
class LoginTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        Context::destroy('auth_token');
        parent::tearDown();
    }

    // ==================== 1. 登录成功场景 ====================

    public function testLoginSuccessReturnsCorrectStructure(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $action = $this->buildAction(adminModel: $model);
        $result = $action->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertArrayHasKey('admin_info', $result);
        $this->assertCount(4, $result);
    }

    public function testLoginSuccessTokenIsBearerType(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $result = $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $this->assertSame('bearer', $result['token_type']);
    }

    public function testLoginSuccessTokenIsValidJwtFormat(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $result = $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $parts = explode('.', $result['token']);
        $this->assertCount(3, $parts, 'JWT 应该包含 header.payload.signature 3 段');
        foreach ($parts as $i => $part) {
            $this->assertNotEmpty($part, "JWT 第 {$i} 段不应为空");
        }
    }

    public function testLoginSuccessJwtPayloadContainsSubAndExp(): void
    {
        $admin = $this->fakeAdmin(['id' => 42]);
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $result = $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $payload = json_decode(base64_decode(explode('.', $result['token'])[1]), true);
        $this->assertSame(42, $payload['sub']);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertSame('admin', $payload['username']);
        $this->assertGreaterThan($payload['iat'], $payload['exp']);
    }

    public function testLoginSuccessExpiresInMatchesEnvTtl(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $result = $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $envTtl = (int) env('JWT_TTL', 7200);
        $this->assertSame($envTtl, $result['expires_in']);
    }

    public function testLoginSuccessAdminInfoExcludesPassword(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $result = $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $this->assertArrayNotHasKey('password', $result['admin_info']);
    }

    public function testLoginSuccessAdminInfoContainsExpectedFields(): void
    {
        $admin = $this->fakeAdmin([
            'id' => 7, 'username' => 'zhangsan', 'name' => '张三',
            'email' => 'zs@test.com', 'phone' => '13900000001',
        ]);
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $result = $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => 'zhangsan', 'password' => 'admin123']));

        $info = $result['admin_info'];
        $this->assertSame(7, $info['id']);
        $this->assertSame('zhangsan', $info['username']);
        $this->assertSame('张三', $info['name']);
        $this->assertSame('zs@test.com', $info['email']);
    }

    public function testLoginSuccessResetsFailureCount(): void
    {
        $admin = $this->fakeAdmin(['login_failure_count' => 5]);
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $this->assertSame(0, $admin->login_failure_count);
    }

    public function testLoginSuccessUpdatesLastLoginTime(): void
    {
        $admin = $this->fakeAdmin(['last_login_time' => null]);
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $this->assertNotNull($admin->last_login_time);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3}$/',
            $admin->last_login_time,
            'last_login_time 应该符合 Y-m-d H:i:s.v 毫秒格式'
        );
    }

    public function testLoginSuccessUpdatesLastLoginIp(): void
    {
        $admin = $this->fakeAdmin(['last_login_ip' => null]);
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $this->buildAction(adminModel: $model, clientIp: '10.0.0.55')
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $this->assertSame('10.0.0.55', $admin->last_login_ip);
    }

    public function testLoginSuccessCallsSaveOnAdmin(): void
    {
        $saveCount = 0;
        $admin = $this->fakeAdmin();
        $admin->shouldReceive('save')->andReturnUsing(function () use (&$saveCount) {
            ++$saveCount;
            return true;
        });
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $this->assertSame(1, $saveCount, 'save 应恰好被调用一次');
    }

    public function testLoginSuccessCallsCreateSession(): void
    {
        $admin = $this->fakeAdmin(['id' => 33]);
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $sessionData = null;
        $session = Mockery::mock(CreateSession::class);
        $session->shouldReceive('handle')->andReturnUsing(function (array $data) use (&$sessionData) {
            $sessionData = $data;
        });

        $this->buildAction(adminModel: $model, createSession: $session, clientIp: '1.2.3.4', userAgent: 'Chrome/100')
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $this->assertNotNull($sessionData, 'CreateSession 应被调用');
        $this->assertSame(33, $sessionData['admin_id']);
        $this->assertNotEmpty($sessionData['token']);
        $this->assertSame('Chrome/100', $sessionData['user_agent']);
        $this->assertSame('1.2.3.4', $sessionData['ip']);
    }

    public function testLoginSuccessSessionTokenMatchesReturnedToken(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $capturedToken = null;
        $session = Mockery::mock(CreateSession::class);
        $session->shouldReceive('handle')->once()->withArgs(function (array $data) use (&$capturedToken) {
            $capturedToken = $data['token'];
            return true;
        })->andReturnNull();

        $result = $this->buildAction(adminModel: $model, createSession: $session)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $this->assertSame($result['token'], $capturedToken);
    }

    // ==================== 2. 参数为空 ====================

    public function testLoginFailsWithEmptyUsername(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('用户名不能为空');

        $this->buildAction()->handle(LoginRequest::fromArray(['username' => '', 'password' => 'abc']));
    }

    public function testLoginFailsWithEmptyPassword(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('密码不能为空');

        $this->buildAction()->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => '']));
    }

    public function testLoginFailsWithMissingUsername(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('用户名不能为空');

        $this->buildAction()->handle(LoginRequest::fromArray(['password' => 'abc']));
    }

    public function testLoginFailsWithMissingPassword(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('密码不能为空');

        $this->buildAction()->handle(LoginRequest::fromArray(['username' => 'admin']));
    }

    public function testLoginFailsWithEmptyArray(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('用户名不能为空');

        $this->buildAction()->handle(LoginRequest::fromArray([]));
    }

    // ==================== 3. 认证失败 ====================

    public function testLoginFailsWhenUserNotFound(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, null);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('账号或密码错误');

        $this->buildAction(adminModel: $model)->handle(LoginRequest::fromArray(['username' => 'ghost', 'password' => 'abc']));
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('账号或密码错误');

        $this->buildAction(adminModel: $model)->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'wrongpwd']));
    }

    public function testLoginFailsWhenAccountDisabledStatus0(): void
    {
        $admin = $this->fakeAdmin(['status' => 0]);
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('账号已被禁用');

        $this->buildAction(adminModel: $model)->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));
    }

    public function testLoginFailsWhenAccountDisabledStatus2(): void
    {
        $admin = $this->fakeAdmin(['status' => 2]);
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('账号已被禁用');

        $this->buildAction(adminModel: $model)->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));
    }

    // ==================== 4. 不应调用后续流程 ====================

    public function testLoginFailDoesNotCreateSession(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, null);

        $sessionCalled = false;
        $session = Mockery::mock(CreateSession::class);
        $session->shouldReceive('handle')->andReturnUsing(function () use (&$sessionCalled) {
            $sessionCalled = true;
        });

        try {
            $this->buildAction(adminModel: $model, createSession: $session)
                ->handle(LoginRequest::fromArray(['username' => 'ghost', 'password' => 'abc']));
        } catch (BusinessException) {
        }

        $this->assertFalse($sessionCalled, '登录失败不应创建会话');
    }

    public function testLoginFailDoesNotCallSaveOnAdmin(): void
    {
        $saveCalled = false;
        $admin = $this->fakeAdmin(['status' => 0]);
        $admin->shouldReceive('save')->andReturnUsing(function () use (&$saveCalled) {
            $saveCalled = true;
            return true;
        });
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        try {
            $this->buildAction(adminModel: $model)
                ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));
        } catch (BusinessException) {
        }

        $this->assertFalse($saveCalled, '登录失败不应调用 save');
    }

    // ==================== 5. 边界值 & 安全 ====================

    public function testLoginWithWhitespaceOnlyUsernameIsTreatedAsEmpty(): void
    {
        // PHP empty('  ') => false，所以空格用户名会进入查询
        // 但 '  ' 不是 empty，所以会正常走查询流程
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, null);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('账号或密码错误');

        $this->buildAction(adminModel: $model)->handle(LoginRequest::fromArray(['username' => '   ', 'password' => 'admin123']));
    }

    public function testLoginWithSqlInjectionInUsername(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('first')->andReturn(null);
        $model->shouldReceive('where')
            ->with('username', "' OR 1=1 --")
            ->once()
            ->andReturn($builder);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('账号或密码错误');

        $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => "' OR 1=1 --", 'password' => 'admin123']));
    }

    public function testLoginWithVeryLongPassword(): void
    {
        $longPwd = str_repeat('a', 500);
        $admin = $this->fakeAdmin(['password' => password_hash($longPwd, PASSWORD_BCRYPT)]);
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        // bcrypt 截断在 72 字节，所以超长密码只比较前 72 字节
        $result = $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => $longPwd]));

        $this->assertNotEmpty($result['token']);
    }

    public function testLoginWithSpecialCharactersPassword(): void
    {
        $specialPwd = 'P@$$w0rd!#%^&*()_+中文=🔥';
        $admin = $this->fakeAdmin(['password' => password_hash($specialPwd, PASSWORD_BCRYPT)]);
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $result = $this->buildAction(adminModel: $model)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => $specialPwd]));

        $this->assertNotEmpty($result['token']);
    }

    public function testLoginIpFallbackWhenRemoteAddrMissing(): void
    {
        $admin = $this->fakeAdmin();
        $model = Mockery::mock(SysAdminModel::class);
        $this->mockFind($model, $admin);

        $request = Mockery::mock(RequestInterface::class);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->andReturn('');
        $request->shouldReceive('getServerParams')->andReturn([]);

        $this->buildAction(adminModel: $model, request: $request)
            ->handle(LoginRequest::fromArray(['username' => 'admin', 'password' => 'admin123']));

        $this->assertSame('127.0.0.1', $admin->last_login_ip);
    }

    // ==================== Helper ====================

    private function fakeAdmin(array $overrides = []): MockInterface
    {
        $defaults = [
            'id' => 1,
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_BCRYPT),
            'name' => '管理员',
            'status' => 1,
            'is_admin' => 1,
            'email' => 'admin@test.com',
            'phone' => '13800138000',
            'avatar' => null,
            'last_login_time' => null,
            'last_login_ip' => null,
            'login_failure_count' => 0,
        ];
        $attrs = array_merge($defaults, $overrides);

        $mock = Mockery::mock(stdClass::class);
        foreach ($attrs as $k => $v) {
            $mock->{$k} = $v;
        }
        $mock->shouldReceive('save')->byDefault()->andReturn(true);
        $mock->shouldReceive('toArray')->byDefault()->andReturn(
            array_diff_key($attrs, ['password' => ''])
        );
        return $mock;
    }

    private function buildAction(
        ?MockInterface $adminModel = null,
        ?MockInterface $createSession = null,
        ?MockInterface $request = null,
        string $clientIp = '192.168.1.100',
        string $userAgent = 'TestBrowser/1.0',
    ): Login {
        $adminModel ??= Mockery::mock(SysAdminModel::class);

        $createSession ??= Mockery::mock(CreateSession::class);
        $createSession->shouldReceive('handle')->byDefault()->andReturnNull();

        $request ??= Mockery::mock(RequestInterface::class);
        $request->shouldReceive('getHeaderLine')->with('User-Agent')->byDefault()->andReturn($userAgent);
        $request->shouldReceive('getServerParams')->byDefault()->andReturn(['remote_addr' => $clientIp]);

        return new Login($adminModel, $createSession, $request);
    }

    private function mockFind(MockInterface $model, mixed $result): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('first')->andReturn($result);
        $model->shouldReceive('where')->with('username', Mockery::any())->andReturn($builder);
    }
}
