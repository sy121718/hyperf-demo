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

use App\Dto\Menu\MenuRequest;
use App\Exception\BusinessException;
use App\Model\Rbac\SysMenuModel;
use App\Service\Menu\Create;
use App\Service\Menu\Delete;
use App\Service\Menu\GetInfo;
use App\Service\Menu\GetTree;
use App\Service\Menu\Update;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;
use Psr\SimpleCache\CacheInterface;
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

    public function testCreateSuccess(): void
    {
        // 非目录类型必须带权限标识；落库时拼成逗号分隔字符串
        $menu = Mockery::mock(stdClass::class);
        $menu->shouldReceive('toArray')->andReturn(['id' => 1, 'menu_code' => 'sys:user']);

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);
        $model->shouldReceive('create')->once()->with(Mockery::on(
            static fn (array $data) => $data['permission_code'] === 'sys:user:list,sys:user:add'
                && $data['parent_id'] === 0
                && $data['create_by'] === 1
                && ! array_key_exists('permission_codes', $data)
        ))->andReturn($menu);

        $result = (new Create($model))->handle(MenuRequest::fromArray([
            'menu_code' => 'sys:user',
            'menu_name' => '用户管理',
            'type' => 2,
            'permission_codes' => ['sys:user:list', 'sys:user:add'],
        ]));

        $this->assertSame(1, $result['id']);
    }

    public function testCreateDuplicateCode(): void
    {
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('菜单标识码 sys:user 已存在');

        (new Create($model))->handle(MenuRequest::fromArray([
            'menu_code' => 'sys:user',
            'type' => 2,
            'permission_codes' => ['sys:user:list'],
        ]));
    }

    public function testCreateRejectsMissingParent(): void
    {
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);
        $model->shouldReceive('where->exists')->andReturn(false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('父级菜单ID 99 不存在');

        (new Create($model))->handle(MenuRequest::fromArray([
            'menu_code' => 'sys:user',
            'type' => 2,
            'parent_id' => 99,
            'permission_codes' => ['sys:user:list'],
        ]));
    }

    public function testCreateDirectoryRejectsPermissionCodes(): void
    {
        // type=0 是目录，目录本身不该挂权限标识
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('目录类型不能设置权限标识');

        (new Create($model))->handle(MenuRequest::fromArray([
            'menu_code' => 'sys',
            'type' => 0,
            'permission_codes' => ['sys:user:list'],
        ]));
    }

    public function testCreateNonDirectoryRequiresPermissionCodes(): void
    {
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('非目录类型必须设置权限标识');

        (new Create($model))->handle(MenuRequest::fromArray([
            'menu_code' => 'sys:user',
            'type' => 2,
        ]));
    }

    // ==================== Update ====================

    public function testUpdateSuccess(): void
    {
        $menu = Mockery::mock(SysMenuModel::class)->makePartial();
        $menu->menu_code = 'sys:user';
        $menu->type = 2;
        $menu->shouldReceive('update')->once()->with(Mockery::on(
            static fn (array $data) => $data['menu_name'] === '用户管理'
                && $data['update_by'] === 1
        ))->andReturn(true);
        $menu->shouldReceive('fresh')->andReturn($menu);
        $menu->shouldReceive('toArray')->andReturn(['id' => 1, 'menu_name' => '用户管理']);

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '菜单')->andReturn($menu);

        $result = (new Update($model))->handle(MenuRequest::fromArray([
            'id' => 1,
            'menu_name' => '用户管理',
        ]));

        $this->assertSame('用户管理', $result['menu_name']);
    }

    public function testUpdateRejectsSelfAsParent(): void
    {
        $menu = Mockery::mock(SysMenuModel::class)->makePartial();
        $menu->menu_code = 'sys:user';
        $menu->type = 2;

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('findOrNotFound')->andReturn($menu);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('不能将自己设为父菜单');

        (new Update($model))->handle(MenuRequest::fromArray(['id' => 5, 'parent_id' => 5]));
    }

    public function testUpdateRejectsMissingParent(): void
    {
        $menu = Mockery::mock(SysMenuModel::class)->makePartial();
        $menu->menu_code = 'sys:user';
        $menu->type = 2;

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('findOrNotFound')->andReturn($menu);
        $model->shouldReceive('where->exists')->andReturn(false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('父级菜单ID 99 不存在');

        (new Update($model))->handle(MenuRequest::fromArray(['id' => 1, 'parent_id' => 99]));
    }

    public function testUpdateNotFound(): void
    {
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '菜单不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Update($model))->handle(MenuRequest::fromArray(['id' => 999]));
    }

    // ==================== Delete ====================

    public function testDeleteSuccess(): void
    {
        // 没有子菜单时只删自己
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '菜单')->andReturn(
            Mockery::mock(SysMenuModel::class)->makePartial()
        );
        $model->shouldReceive('where')->with('parent_id', 1)->andReturnSelf();
        $model->shouldReceive('pluck')->with('id')->andReturn(
            Mockery::mock()->shouldReceive('toArray')->andReturn([])->getMock()
        );
        $model->shouldReceive('whereIn')->with('id', [1])->once()->andReturnSelf();
        $model->shouldReceive('delete')->once()->andReturn(1);

        $this->assertTrue((new Delete($model))->handle(1));
    }

    public function testDeleteIncludesChildren(): void
    {
        // 删除父菜单时子菜单要一起删；递归按 parent_id 逐层展开
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('findOrNotFound')->andReturn(
            Mockery::mock(SysMenuModel::class)->makePartial()
        );

        $children = [
            1 => [2, 3],
            2 => [],
            3 => [],
        ];
        $model->shouldReceive('where')->with('parent_id', Mockery::any())->andReturnSelf();
        $model->shouldReceive('pluck')->with('id')->andReturnUsing(
            static function () use (&$children) {
                $ids = array_shift($children);
                return Mockery::mock()
                    ->shouldReceive('toArray')->andReturn($ids)
                    ->getMock();
            }
        );
        $model->shouldReceive('whereIn')->with('id', [1, 2, 3])->once()->andReturnSelf();
        $model->shouldReceive('delete')->once()->andReturn(3);

        $this->assertTrue((new Delete($model))->handle(1));
    }

    public function testDeleteZeroId(): void
    {
        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '菜单ID不能为空'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('菜单ID不能为空');

        (new Delete($model))->handle(0);
    }

    // ==================== GetInfo ====================

    public function testGetInfoSuccess(): void
    {
        $menu = Mockery::mock(SysMenuModel::class)->makePartial();
        $menu->shouldReceive('toArray')->andReturn(['id' => 1, 'menu_code' => 'sys:user']);

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '菜单')->andReturn($menu);

        $this->assertSame('sys:user', (new GetInfo($model))->handle(1)['menu_code']);
    }

    // ==================== GetTree ====================

    public function testGetTreeUsesCacheWhenUnfiltered(): void
    {
        $cached = [['id' => 1, 'menu_name' => '系统管理']];

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->once()->andReturn($cached);

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('filter')->never();

        $this->assertSame($cached, (new GetTree($model, $cache))->handle(MenuRequest::fromArray([])));
    }

    public function testGetTreeBypassesCacheWhenFiltered(): void
    {
        // 带筛选条件时必须查库，且不能把筛选结果写进全量树缓存
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->never();
        $cache->shouldReceive('set')->never();

        $rows = [['id' => 1, 'parent_id' => 0, 'menu_name' => '系统管理']];

        $collection = Mockery::mock();
        $collection->shouldReceive('isEmpty')->andReturn(false);
        $collection->shouldReceive('toArray')->andReturn($rows);

        $builder = Mockery::mock();
        $builder->shouldReceive('when')->andReturnSelf();
        $builder->shouldReceive('sort')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn($collection);

        $model = Mockery::mock(SysMenuModel::class);
        $model->shouldReceive('filter')->andReturn($builder);
        $model->shouldReceive('buildTree')->with($rows)->andReturn($rows);

        $result = (new GetTree($model, $cache))->handle(
            MenuRequest::fromArray(['search' => '系统'])
        );

        $this->assertSame($rows, $result);
    }
}
