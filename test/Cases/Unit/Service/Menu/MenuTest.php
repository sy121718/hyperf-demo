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

namespace HyperfTest\Cases\Unit\Service\Menu;

use App\Exception\BusinessException;
use App\Model\Menu\SysMenuModel;
use App\Model\Menu\SysMenuPermissionModel;
use App\Service\Menu\Create;
use App\Service\Menu\Delete;
use App\Service\Menu\Dto\CreateMenuDto;
use App\Service\Menu\Dto\UpdateMenuDto;
use App\Service\Menu\GetInfo;
use App\Service\Menu\Update;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\Testing\TestCase;
use Mockery;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class MenuTest extends TestCase
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

    // ==================== Create ====================

    public function testCreateMenuSuccess(): void
    {
        $menu = Mockery::mock(stdClass::class);
        $menu->id = 10;
        $menu->shouldReceive('permissions->sync')->andReturnNull();
        $menu->shouldReceive('load')->andReturnSelf();
        $menu->shouldReceive('toArray')->andReturn([
            'id' => 10, 'menu_code' => 'user_mgmt', 'menu_name' => '用户管理',
            'permissions' => [],
        ]);

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);
        $model->shouldReceive('where->exists')->andReturn(true);
        $model->shouldReceive('create')->andReturn($menu);

        $result = (new Create($model))->handle(
            new CreateMenuDto(menuCode: 'user_mgmt', menuName: '用户管理', parentId: 1)
        );

        $this->assertArrayHasKey('id', $result);
    }

    public function testCreateMenuDuplicateCode(): void
    {
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('菜单标识码 dup 已存在');

        (new Create($model))->handle(new CreateMenuDto(menuCode: 'dup', menuName: 'X'));
    }

    public function testCreateMenuParentNotExists(): void
    {
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('exists')->andReturn(false);
        $model->shouldReceive('where')->with('id', 999)->andReturn($builder);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('父级菜单ID 999 不存在');

        (new Create($model))->handle(new CreateMenuDto(menuCode: 'x', menuName: 'X', parentId: 999));
    }

    // ==================== Update ====================

    public function testUpdateMenuSuccess(): void
    {
        $menu = Mockery::mock(SysMenuModel::class);
        $menu->shouldReceive('__get')->with('menu_code')->andReturn('old');
        $menu->shouldReceive('getAttribute')->andReturnUsing(fn ($k) => match ($k) {
            'menu_code' => 'old', default => null,
        });
        $menu->shouldReceive('update')->andReturn(true);
        $menu->shouldReceive('permissions->sync')->andReturnNull();
        $menu->shouldReceive('load')->andReturnSelf();
        $menu->shouldReceive('toArray')->andReturn([
            'id' => 1, 'menu_code' => 'new', 'menu_name' => '新菜单', 'permissions' => [],
        ]);

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('find')->with(1)->andReturn($menu);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $result = (new Update($model))->handle(new UpdateMenuDto(id: 1, menuCode: 'new', menuName: '新菜单'));

        $this->assertIsArray($result);
    }

    public function testUpdateMenuNotFound(): void
    {
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('find')->andReturn(null);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Update($model))->handle(new UpdateMenuDto(id: 999));
    }

    public function testUpdateMenuCannotSetSelfAsParent(): void
    {
        $menu = Mockery::mock(SysMenuModel::class);
        $menu->shouldReceive('__get')->andReturn(null);
        $menu->shouldReceive('getAttribute')->andReturn(null);

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('find')->with(5)->andReturn($menu);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('不能将自己设为父菜单');

        (new Update($model))->handle(new UpdateMenuDto(id: 5, parentId: 5));
    }

    // ==================== Delete ====================

    public function testDeleteMenuSuccess(): void
    {
        $menu = Mockery::mock(stdClass::class);

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('find')->with(3)->andReturn($menu);
        $model->shouldReceive('where->pluck->toArray')->andReturn([]);
        $model->shouldReceive('whereIn->delete')->andReturn(1);

        $permModel = Mockery::mock(SysMenuPermissionModel::class);
        $permModel->shouldReceive('whereIn->delete')->andReturn(0);

        $result = (new Delete($model, $permModel))->handle(['id' => 3]);

        $this->assertTrue($result);
    }

    public function testDeleteMenuNotFound(): void
    {
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('find')->andReturn(null);
        $permModel = Mockery::mock(SysMenuPermissionModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Delete($model, $permModel))->handle(['id' => 999]);
    }

    public function testDeleteMenuZeroId(): void
    {
        $model = Mockery::mock(SysMenuModel::class);
        $permModel = Mockery::mock(SysMenuPermissionModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('菜单ID不能为空');

        (new Delete($model, $permModel))->handle(['id' => 0]);
    }

    // ==================== GetInfo ====================

    public function testGetInfoSuccess(): void
    {
        $menu = Mockery::mock(stdClass::class);
        $menu->shouldReceive('toArray')->andReturn(['id' => 1, 'menu_code' => 'test']);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('find')->with(1)->andReturn($menu);

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('with')->with(['permissions'])->andReturn($builder);

        $result = (new GetInfo($model))->handle(1);

        $this->assertSame(1, $result['id']);
    }

    public function testGetInfoNotFound(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('find')->andReturn(null);

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('with')->andReturn($builder);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new GetInfo($model))->handle(999);
    }

    public function testGetInfoZeroId(): void
    {
        $model = Mockery::mock(SysMenuModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('菜单ID不能为空');

        (new GetInfo($model))->handle(0);
    }
}
