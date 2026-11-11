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

namespace HyperfTest\Cases\Unit\Common\Model;

use App\Exception\BusinessException;
use App\Model\Rbac\SysRoleModel;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;

/**
 * 覆盖 BaseModel 新增的「取值即校验」方法.
 *
 * findOrNotFound / firstOrNotFound 的职责边界：
 * - 只做单表取值 + 非法 ID 校验 + 不存在时抛 404
 * - 不做事务、缓存失效、状态判断（这些必须留在 Action）
 *
 * @internal
 * @coversNothing
 */
class BaseModelLookupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 1, 'username' => 'tester', 'is_admin' => 1]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testFindOrNotFoundRejectsZeroId(): void
    {
        $model = Mockery::mock(SysRoleModel::class)->makePartial();

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('角色ID不能为空');

        $model->findOrNotFound(0, '角色');
    }

    public function testFindOrNotFoundRejectsNegativeId(): void
    {
        $model = Mockery::mock(SysRoleModel::class)->makePartial();

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('角色ID不能为空');

        $model->findOrNotFound(-5, '角色');
    }

    public function testFindOrNotFoundThrows404WhenMissing(): void
    {
        $builder = Mockery::mock();
        $builder->shouldReceive('find')->with(999)->andReturn(null);

        $model = Mockery::mock(SysRoleModel::class)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($builder);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('角色不存在');

        $model->findOrNotFound(999, '角色');
    }

    public function testFindOrNotFoundReturnsModel(): void
    {
        $expected = Mockery::mock(SysRoleModel::class);

        $builder = Mockery::mock();
        $builder->shouldReceive('find')->with(1)->andReturn($expected);

        $model = Mockery::mock(SysRoleModel::class)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($builder);

        $this->assertSame($expected, $model->findOrNotFound(1, '角色'));
    }

    public function testFindOrNotFoundCastsStringId(): void
    {
        $expected = Mockery::mock(SysRoleModel::class);

        $builder = Mockery::mock();
        // 查询串常传字符串，必须转成 int 再查
        $builder->shouldReceive('find')->with(7)->once()->andReturn($expected);

        $model = Mockery::mock(SysRoleModel::class)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($builder);

        $this->assertSame($expected, $model->findOrNotFound('7', '角色'));
    }

    public function testFindOrNotFoundEagerLoadsRelations(): void
    {
        $expected = Mockery::mock(SysRoleModel::class);

        $builder = Mockery::mock();
        $builder->shouldReceive('with')->with(['roles', 'sessions'])->once()->andReturnSelf();
        $builder->shouldReceive('find')->with(1)->andReturn($expected);

        $model = Mockery::mock(SysRoleModel::class)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($builder);

        $this->assertSame($expected, $model->findOrNotFound(1, '角色', ['roles', 'sessions']));
    }

    public function testFirstOrNotFoundThrows404WhenMissing(): void
    {
        $builder = Mockery::mock();
        $builder->shouldReceive('where')->with('role_code', 'nope')->andReturnSelf();
        $builder->shouldReceive('first')->andReturn(null);

        $model = Mockery::mock(SysRoleModel::class)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($builder);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('角色不存在');

        $model->firstOrNotFound(['role_code' => 'nope'], '角色');
    }

    public function testFirstOrNotFoundReturnsModel(): void
    {
        $expected = Mockery::mock(SysRoleModel::class);

        $builder = Mockery::mock();
        $builder->shouldReceive('where')->with('role_code', 'admin')->andReturnSelf();
        $builder->shouldReceive('first')->andReturn($expected);

        $model = Mockery::mock(SysRoleModel::class)->makePartial();
        $model->shouldReceive('newQuery')->andReturn($builder);

        $this->assertSame($expected, $model->firstOrNotFound(['role_code' => 'admin'], '角色'));
    }
}
