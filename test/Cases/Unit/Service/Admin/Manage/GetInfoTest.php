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
use App\Service\Admin\Manage\GetInfo;
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
class GetInfoTest extends TestCase
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

    // ==================== 获取成功 ====================

    public function testGetInfoSuccess(): void
    {
        $admin = Mockery::mock(stdClass::class);
        $admin->shouldReceive('toArray')->andReturn([
            'id' => 5, 'username' => 'test', 'name' => 'Test',
            'roles' => [['id' => 1, 'name' => 'Admin']],
            'sessions' => [],
        ]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('find')->with(5)->andReturn($admin);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('with')->with(['roles', 'sessions'])->andReturn($builder);

        $result = $this->buildAction($model)->handle(5);

        $this->assertSame(5, $result['id']);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('sessions', $result);
    }

    // ==================== 获取失败 ====================

    public function testGetInfoFailsWithZeroId(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        $this->buildAction()->handle(0);
    }

    public function testGetInfoFailsWithNegativeId(): void
    {
        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        $this->buildAction()->handle(-1);
    }

    public function testGetInfoFailsWhenNotFound(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('find')->with(999)->andReturn(null);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('with')->andReturn($builder);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        $this->buildAction($model)->handle(999);
    }

    private function buildAction(?MockInterface $model = null): GetInfo
    {
        $model ??= Mockery::mock(SysAdminModel::class);
        return new GetInfo($model);
    }
}
