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

use App\Exception\BusinessException;
use App\Model\Admin\SysAdminSessionsModel;
use App\Service\Admin\Auth\Logout;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;

/**
 * @internal
 * @coversNothing
 */
class LogoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', [
            'id' => 1,
            'username' => 'admin',
            'is_admin' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        Context::destroy('auth_token');
        parent::tearDown();
    }

    // ==================== 1. 带 token 退出 ====================

    public function testLogoutWithTokenInvalidatesSpecificSession(): void
    {
        Context::set('auth_token', 'eyJ.test.token');

        $model = $this->mockSessionModel();
        $this->expectChain($model, 1, 'eyJ.test.token', 1);

        (new Logout($model))->handle();
        $this->addToAssertionCount(1);
    }

    public function testLogoutWithTokenOnlyAffectsMatchingSession(): void
    {
        Context::set('auth_user', ['id' => 42, 'username' => 'u42', 'is_admin' => 0]);
        Context::set('auth_token', 'specific.token');

        $model = $this->mockSessionModel();
        $this->expectChain($model, 42, 'specific.token', 1);

        (new Logout($model))->handle();
        $this->addToAssertionCount(1);
    }

    // ==================== 2. 无 token 退出（全部失效） ====================

    public function testLogoutWithoutTokenInvalidatesAllActiveSessions(): void
    {
        Context::set('auth_token', null);

        $model = $this->mockSessionModel();
        $this->expectChain($model, 1, null, 3);

        (new Logout($model))->handle();
        $this->addToAssertionCount(1);
    }

    public function testLogoutWithEmptyStringTokenInvalidatesAllActiveSessions(): void
    {
        Context::set('auth_token', '');

        $model = $this->mockSessionModel();
        $this->expectChain($model, 1, null, 2);

        (new Logout($model))->handle();
        $this->addToAssertionCount(1);
    }

    // ==================== 3. 无活跃会话 ====================

    public function testLogoutWhenNoActiveSessionsDoesNotThrow(): void
    {
        Context::set('auth_token', 'some.token');

        $model = $this->mockSessionModel();
        $this->expectChain($model, 1, 'some.token', 0);

        (new Logout($model))->handle();
        $this->addToAssertionCount(1);
    }

    // ==================== 4. admin_id 正确性 ====================

    public function testLogoutUsesCorrectAdminIdFromContext(): void
    {
        Context::set('auth_user', ['id' => 999, 'username' => 'u999', 'is_admin' => 0]);
        Context::set('auth_token', 'tok');

        $model = $this->mockSessionModel();
        $this->expectChain($model, 999, 'tok', 1);

        (new Logout($model))->handle();
        $this->addToAssertionCount(1);
    }

    // ==================== 5. 异常场景 ====================

    public function testLogoutThrows401WhenNoAuthUserInContext(): void
    {
        Context::destroy('auth_user');

        $model = $this->mockSessionModel();

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(401);

        (new Logout($model))->handle();
    }

    public function testLogoutWithFalsyTokenFallsToAllSessionsPath(): void
    {
        Context::set('auth_token', 0);

        $model = $this->mockSessionModel();
        $this->expectChain($model, 1, null, 1);

        (new Logout($model))->handle();
        $this->addToAssertionCount(1);
    }

    private function mockSessionModel(): MockInterface
    {
        return Mockery::mock(SysAdminSessionsModel::class);
    }

    private function expectChain(MockInterface $model, int $adminId, ?string $token, int $updateCount): void
    {
        $builder = Mockery::mock(Builder::class);

        if ($token !== null) {
            $builder->shouldReceive('where')->with('jwt_token', $token)->once()->andReturnSelf();
        } else {
            $builder->shouldReceive('where')->with('status', 1)->once()->andReturnSelf();
        }

        $builder->shouldReceive('update')->with(['status' => 0])->once()->andReturn($updateCount);
        $model->shouldReceive('where')->with('admin_id', $adminId)->once()->andReturn($builder);
    }
}
