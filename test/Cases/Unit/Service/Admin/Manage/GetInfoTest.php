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
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;

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
        $admin = Mockery::mock(SysAdminModel::class);
        $admin->shouldReceive('toArray')->andReturn([
            'id' => 5, 'username' => 'test', 'name' => 'Test',
            'roles' => [['id' => 1, 'name' => 'Admin']],
            'sessions' => [],
        ]);

        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')
            ->with(5, '管理员', ['roles', 'sessions'])
            ->andReturn($admin);

        $result = $this->buildAction($model)->handle(5);

        $this->assertSame(5, $result['id']);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('sessions', $result);
    }

    // ==================== 获取失败 ====================

    public function testGetInfoFailsWithZeroId(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '管理员ID不能为空'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        $this->buildAction($model)->handle(0);
    }

    public function testGetInfoFailsWithNegativeId(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '管理员ID不能为空'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('管理员ID不能为空');

        $this->buildAction($model)->handle(-1);
    }

    public function testGetInfoFailsWhenNotFound(): void
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')
            ->with(999, '管理员', ['roles', 'sessions'])
            ->andThrow(new BusinessException(404, '管理员不存在'));

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
