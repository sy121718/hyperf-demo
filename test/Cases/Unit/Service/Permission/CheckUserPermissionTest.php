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

namespace HyperfTest\Cases\Unit\Service\Permission;

use App\Model\Admin\SysAdminRoleModel;
use App\Model\Menu\SysMenuPermissionModel;
use App\Model\Permission\SysPermissionDictModel;
use App\Model\Role\SysRoleMenuModel;
use App\Service\Permission\CheckUserPermission;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Hyperf\Testing\TestCase;
use Mockery;

/**
 * @internal
 * @coversNothing
 */
class CheckUserPermissionTest extends TestCase
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

    // ==================== 公共接口（不在字典中） ====================

    public function testPublicApiAllowed(): void
    {
        $permModel = $this->mockPermModelNotFound();

        $result = $this->makeAction($permModel)->handle(1, '/api/public/health', 'GET');

        $this->assertTrue($result);
    }

    // ==================== 禁用的权限 ====================

    public function testDisabledPermissionDenied(): void
    {
        $perm = (object) ['id' => 1, 'status' => 0, 'is_public' => 0, 'api_path' => '/api/admin/list'];

        $permModel = Mockery::mock(SysPermissionDictModel::class);
        $b1 = Mockery::mock(Builder::class);
        $b1->shouldReceive('where')->with('http_method', 'GET')->andReturnSelf();
        $b1->shouldReceive('first')->andReturn($perm);
        $permModel->shouldReceive('where')->with('api_path', '/api/admin/list')->andReturn($b1);

        $result = $this->makeAction($permModel)->handle(1, '/api/admin/list', 'GET');

        $this->assertFalse($result);
    }

    // ==================== 公开权限 ====================

    public function testPublicPermissionAllowed(): void
    {
        $perm = (object) ['id' => 1, 'status' => 1, 'is_public' => 1, 'api_path' => '/api/common/info'];

        $permModel = Mockery::mock(SysPermissionDictModel::class);
        $b1 = Mockery::mock(Builder::class);
        $b1->shouldReceive('where')->with('http_method', 'GET')->andReturnSelf();
        $b1->shouldReceive('first')->andReturn($perm);
        $permModel->shouldReceive('where')->with('api_path', '/api/common/info')->andReturn($b1);

        $result = $this->makeAction($permModel)->handle(1, '/api/common/info', 'GET');

        $this->assertTrue($result);
    }

    // ==================== 没有角色 ====================

    public function testNoRolesDenied(): void
    {
        $perm = (object) ['id' => 5, 'status' => 1, 'is_public' => 0, 'api_path' => '/api/admin/list'];

        $permModel = Mockery::mock(SysPermissionDictModel::class);
        $b1 = Mockery::mock(Builder::class);
        $b1->shouldReceive('where')->with('http_method', 'GET')->andReturnSelf();
        $b1->shouldReceive('first')->andReturn($perm);
        $permModel->shouldReceive('where')->with('api_path', '/api/admin/list')->andReturn($b1);

        $arModel = Mockery::mock(SysAdminRoleModel::class);
        $arBuilder = Mockery::mock(Builder::class);
        $arBuilder->shouldReceive('pluck')->with('role_id')->andReturnSelf();
        $arBuilder->shouldReceive('toArray')->andReturn([]);
        $arModel->shouldReceive('where')->with('admin_id', 99)->andReturn($arBuilder);

        $rmModel = Mockery::mock(SysRoleMenuModel::class);
        $rmBuilder = Mockery::mock(Builder::class);
        $rmBuilder->shouldReceive('pluck')->with('menu_id')->andReturnSelf();
        $rmBuilder->shouldReceive('toArray')->andReturn([]);
        $rmModel->shouldReceive('whereIn')->with('role_id', Mockery::on(static fn ($arg) => is_callable($arg)))->andReturn($rmBuilder);

        $result = $this->makeAction($permModel, $arModel, $rmModel)->handle(99, '/api/admin/list', 'GET');

        $this->assertFalse($result);
    }

    // ==================== 有角色但没菜单 ====================

    public function testHasRolesButNoMenusDenied(): void
    {
        $perm = (object) ['id' => 5, 'status' => 1, 'is_public' => 0, 'api_path' => '/api/admin/list'];

        $permModel = Mockery::mock(SysPermissionDictModel::class);
        $b1 = Mockery::mock(Builder::class);
        $b1->shouldReceive('where')->with('http_method', 'GET')->andReturnSelf();
        $b1->shouldReceive('first')->andReturn($perm);
        $permModel->shouldReceive('where')->with('api_path', '/api/admin/list')->andReturn($b1);

        $arModel = Mockery::mock(SysAdminRoleModel::class);
        $arBuilder = Mockery::mock(Builder::class);
        $arBuilder->shouldReceive('pluck')->with('role_id')->andReturnSelf();
        $arBuilder->shouldReceive('toArray')->andReturn([1, 2]);
        $arModel->shouldReceive('where')->with('admin_id', 1)->andReturn($arBuilder);

        $rmModel = Mockery::mock(SysRoleMenuModel::class);
        $rmBuilder = Mockery::mock(Builder::class);
        $rmBuilder->shouldReceive('pluck')->with('menu_id')->andReturnSelf();
        $rmBuilder->shouldReceive('toArray')->andReturn([]);
        $rmModel->shouldReceive('whereIn')->with('role_id', Mockery::on(static fn ($arg) => is_callable($arg)))->andReturn($rmBuilder);

        $result = $this->makeAction($permModel, $arModel, $rmModel)->handle(1, '/api/admin/list', 'GET');

        $this->assertFalse($result);
    }

    // ==================== 完整权限链通过 ====================

    public function testFullChainPermissionGranted(): void
    {
        $perm = (object) ['id' => 5, 'status' => 1, 'is_public' => 0, 'api_path' => '/api/admin/list'];

        $permModel = Mockery::mock(SysPermissionDictModel::class);
        $b1 = Mockery::mock(Builder::class);
        $b1->shouldReceive('where')->with('http_method', 'GET')->andReturnSelf();
        $b1->shouldReceive('first')->andReturn($perm);
        $permModel->shouldReceive('where')->with('api_path', '/api/admin/list')->andReturn($b1);

        $arModel = Mockery::mock(SysAdminRoleModel::class);
        $arBuilder = Mockery::mock(Builder::class);
        $arBuilder->shouldReceive('pluck')->with('role_id')->andReturnSelf();
        $arBuilder->shouldReceive('toArray')->andReturn([1]);
        $arModel->shouldReceive('where')->with('admin_id', 1)->andReturn($arBuilder);

        $rmModel = Mockery::mock(SysRoleMenuModel::class);
        $rmBuilder = Mockery::mock(Builder::class);
        $rmBuilder->shouldReceive('pluck')->with('menu_id')->andReturnSelf();
        $rmBuilder->shouldReceive('toArray')->andReturn([10, 20]);
        $rmModel->shouldReceive('whereIn')->with('role_id', Mockery::on(static fn ($arg) => is_callable($arg)))->andReturn($rmBuilder);

        $mpModel = Mockery::mock(SysMenuPermissionModel::class);
        $mpBuilder = Mockery::mock(Builder::class);
        $mpBuilder->shouldReceive('where')->with('permission_id', 5)->andReturnSelf();
        $mpBuilder->shouldReceive('exists')->andReturn(true);
        $mpModel->shouldReceive('whereIn')->with('menu_id', [10, 20])->andReturn($mpBuilder);

        $result = $this->makeAction($permModel, $arModel, $rmModel, $mpModel)->handle(1, '/api/admin/list', 'GET');

        $this->assertTrue($result);
    }

    // ==================== 完整权限链拒绝 ====================

    public function testFullChainPermissionDenied(): void
    {
        $perm = (object) ['id' => 5, 'status' => 1, 'is_public' => 0, 'api_path' => '/api/admin/list'];

        $permModel = Mockery::mock(SysPermissionDictModel::class);
        $b1 = Mockery::mock(Builder::class);
        $b1->shouldReceive('where')->with('http_method', 'GET')->andReturnSelf();
        $b1->shouldReceive('first')->andReturn($perm);
        $permModel->shouldReceive('where')->with('api_path', '/api/admin/list')->andReturn($b1);

        $arModel = Mockery::mock(SysAdminRoleModel::class);
        $arBuilder = Mockery::mock(Builder::class);
        $arBuilder->shouldReceive('pluck')->with('role_id')->andReturnSelf();
        $arBuilder->shouldReceive('toArray')->andReturn([1]);
        $arModel->shouldReceive('where')->with('admin_id', 1)->andReturn($arBuilder);

        $rmModel = Mockery::mock(SysRoleMenuModel::class);
        $rmBuilder = Mockery::mock(Builder::class);
        $rmBuilder->shouldReceive('pluck')->with('menu_id')->andReturnSelf();
        $rmBuilder->shouldReceive('toArray')->andReturn([10]);
        $rmModel->shouldReceive('whereIn')->with('role_id', Mockery::on(static fn ($arg) => is_callable($arg)))->andReturn($rmBuilder);

        $mpModel = Mockery::mock(SysMenuPermissionModel::class);
        $mpBuilder = Mockery::mock(Builder::class);
        $mpBuilder->shouldReceive('where')->with('permission_id', 5)->andReturnSelf();
        $mpBuilder->shouldReceive('exists')->andReturn(false);
        $mpModel->shouldReceive('whereIn')->with('menu_id', [10])->andReturn($mpBuilder);

        $result = $this->makeAction($permModel, $arModel, $rmModel, $mpModel)->handle(1, '/api/admin/list', 'GET');

        $this->assertFalse($result);
    }

    // ==================== 动态路由匹配 ====================

    public function testDynamicRouteMatching(): void
    {
        $permModel = Mockery::mock(SysPermissionDictModel::class);

        $b1 = Mockery::mock(Builder::class);
        $b1->shouldReceive('where')->with('http_method', 'GET')->andReturnSelf();
        $b1->shouldReceive('first')->andReturn(null);
        $permModel->shouldReceive('where')->with('api_path', '/api/admin/info/5')->andReturn($b1);

        $dynamicPerm = (object) [
            'id' => 10, 'status' => 1, 'is_public' => 1,
            'api_path' => '/api/admin/info/{id}',
        ];

        $b2 = Mockery::mock(Builder::class);
        $b2->shouldReceive('where')->with('http_method', 'GET')->andReturnSelf();
        $b2->shouldReceive('get')->andReturn(new Collection([$dynamicPerm]));
        $permModel->shouldReceive('where')->with('api_path', 'like', '%{%')->andReturn($b2);

        $result = $this->makeAction($permModel)->handle(1, '/api/admin/info/5', 'GET');

        $this->assertTrue($result);
    }

    // ==================== HTTP 方法大小写不敏感 ====================

    public function testHttpMethodCaseInsensitive(): void
    {
        $permModel = $this->mockPermModelNotFound();

        $result = $this->makeAction($permModel)->handle(1, '/api/test', 'post');

        $this->assertTrue($result);
    }

    // ==================== 动态路由不匹配 ====================

    public function testDynamicRouteNotMatched(): void
    {
        $permModel = $this->mockPermModelNotFound();

        $result = $this->makeAction($permModel)->handle(1, '/api/some/unknown', 'DELETE');

        $this->assertTrue($result);
    }

    // ==================== 多个动态路由正确匹配 ====================

    public function testMultipleDynamicRoutesCorrectMatch(): void
    {
        $permModel = Mockery::mock(SysPermissionDictModel::class);

        $b1 = Mockery::mock(Builder::class);
        $b1->shouldReceive('where')->with('http_method', 'PUT')->andReturnSelf();
        $b1->shouldReceive('first')->andReturn(null);
        $permModel->shouldReceive('where')->with('api_path', '/api/role/update/3')->andReturn($b1);

        $perm1 = (object) [
            'id' => 20, 'status' => 1, 'is_public' => 0,
            'api_path' => '/api/admin/update/{id}',
        ];
        $perm2 = (object) [
            'id' => 21, 'status' => 1, 'is_public' => 1,
            'api_path' => '/api/role/update/{id}',
        ];

        $b2 = Mockery::mock(Builder::class);
        $b2->shouldReceive('where')->with('http_method', 'PUT')->andReturnSelf();
        $b2->shouldReceive('get')->andReturn(new Collection([$perm1, $perm2]));
        $permModel->shouldReceive('where')->with('api_path', 'like', '%{%')->andReturn($b2);

        $result = $this->makeAction($permModel)->handle(1, '/api/role/update/3', 'PUT');

        $this->assertTrue($result);
    }

    private function makeAction(
        ?SysPermissionDictModel $permModel = null,
        ?SysAdminRoleModel $arModel = null,
        ?SysRoleMenuModel $rmModel = null,
        ?SysMenuPermissionModel $mpModel = null,
    ): CheckUserPermission {
        return new CheckUserPermission(
            $permModel ?? Mockery::mock(SysPermissionDictModel::class),
            $arModel ?? Mockery::mock(SysAdminRoleModel::class),
            $rmModel ?? Mockery::mock(SysRoleMenuModel::class),
            $mpModel ?? Mockery::mock(SysMenuPermissionModel::class),
        );
    }

    private function mockPermModelNotFound(): SysPermissionDictModel
    {
        $model = Mockery::mock(SysPermissionDictModel::class);

        $b1 = Mockery::mock(Builder::class);
        $b1->shouldReceive('where')->andReturnSelf();
        $b1->shouldReceive('first')->andReturn(null);

        $b2 = Mockery::mock(Builder::class);
        $b2->shouldReceive('where')->andReturnSelf();
        $b2->shouldReceive('get')->andReturn(new Collection([]));

        $model->shouldReceive('where')->with('api_path', Mockery::any())->andReturn($b1);
        $model->shouldReceive('where')->with('api_path', 'like', '%{%')->andReturn($b2);

        return $model;
    }
}
