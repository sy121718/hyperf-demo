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

namespace HyperfTest\Cases\Unit\Service\Batch;

use App\Exception\BusinessException;
use App\Model\Admin\SysAdminRoleModel;
use App\Model\Admin\SysAdminSessionsModel;
use App\Model\Menu\SysMenuModel;
use App\Model\Menu\SysMenuPermissionModel;
use App\Model\Permission\SysPermissionDictModel;
use App\Model\Role\SysRoleMenuModel;
use App\Model\Role\SysRoleModel;
use App\Service\Menu\BatchDelete as MenuBatchDelete;
use App\Service\Ops\Session\BatchKickout;
use App\Service\Permission\BatchDelete as PermBatchDelete;
use App\Service\Role\BatchDelete as RoleBatchDelete;
use Hyperf\Collection\Collection;
use Hyperf\Context\Context;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 * @coversNothing
 */
class BatchOperationsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('currentUser');
    }

    public function testMenuBatchDeleteEmptyIds(): void
    {
        $menuModel = Mockery::mock(SysMenuModel::class);
        $menuPermModel = Mockery::mock(SysMenuPermissionModel::class);

        $action = new MenuBatchDelete($menuModel, $menuPermModel);

        $this->expectException(BusinessException::class);
        $action->handle(['ids' => []]);
    }

    public function testRoleBatchDeleteNotSuperAdmin(): void
    {
        $this->mockCurrentUser(['id' => 2, 'is_admin' => 0]);

        $roleModel = Mockery::mock(SysRoleModel::class);
        $adminRoleModel = Mockery::mock(SysAdminRoleModel::class);
        $roleMenuModel = Mockery::mock(SysRoleMenuModel::class);

        $action = new RoleBatchDelete($roleModel, $adminRoleModel, $roleMenuModel);

        $this->expectException(BusinessException::class);
        $action->handle(['ids' => [1]]);
    }

    public function testRoleBatchDeleteEmptyIds(): void
    {
        $this->mockCurrentUser(['id' => 1, 'is_admin' => 1]);

        $roleModel = Mockery::mock(SysRoleModel::class);
        $adminRoleModel = Mockery::mock(SysAdminRoleModel::class);
        $roleMenuModel = Mockery::mock(SysRoleMenuModel::class);

        $action = new RoleBatchDelete($roleModel, $adminRoleModel, $roleMenuModel);

        $this->expectException(BusinessException::class);
        $action->handle(['ids' => []]);
    }

    public function testPermBatchDeleteNotSuperAdmin(): void
    {
        $this->mockCurrentUser(['id' => 2, 'is_admin' => 0]);

        $permModel = Mockery::mock(SysPermissionDictModel::class);

        $action = new PermBatchDelete($permModel);

        $this->expectException(BusinessException::class);
        $action->handle(['ids' => [1]]);
    }

    public function testPermBatchDeleteEmptyIds(): void
    {
        $this->mockCurrentUser(['id' => 1, 'is_admin' => 1]);

        $permModel = Mockery::mock(SysPermissionDictModel::class);

        $action = new PermBatchDelete($permModel);

        $this->expectException(BusinessException::class);
        $action->handle(['ids' => []]);
    }

    public function testSessionBatchKickoutEmptyIds(): void
    {
        $sessionModel = Mockery::mock(SysAdminSessionsModel::class);
        $cache = Mockery::mock(CacheInterface::class);

        $action = new BatchKickout($sessionModel, $cache);

        $this->expectException(BusinessException::class);
        $action->handle(['ids' => []]);
    }

    public function testSessionBatchKickoutSuccess(): void
    {
        $session1 = Mockery::mock(SysAdminSessionsModel::class)->makePartial();
        $session1->status = 1;
        $session1->jwt_token = 'token1';
        $session1->shouldReceive('save')->once();

        $session2 = Mockery::mock(SysAdminSessionsModel::class)->makePartial();
        $session2->status = 0;

        $sessionModel = Mockery::mock(SysAdminSessionsModel::class);
        $sessionModel->shouldReceive('whereIn->get')->andReturn(new Collection([$session1, $session2]));

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('delete')->once()->andReturn(true);

        $action = new BatchKickout($sessionModel, $cache);
        $result = $action->handle(['ids' => [1, 2]]);

        $this->assertEquals(1, $result);
    }

    public function testSessionBatchKickoutAllInactive(): void
    {
        $session1 = Mockery::mock(SysAdminSessionsModel::class)->makePartial();
        $session1->status = 0;

        $sessionModel = Mockery::mock(SysAdminSessionsModel::class);
        $sessionModel->shouldReceive('whereIn->get')->andReturn(new Collection([$session1]));

        $cache = Mockery::mock(CacheInterface::class);

        $action = new BatchKickout($sessionModel, $cache);
        $result = $action->handle(['ids' => [1]]);

        $this->assertEquals(0, $result);
    }

    private function mockCurrentUser(array $user): void
    {
        $request = Mockery::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('current_user')->andReturn($user);
        Context::set(ServerRequestInterface::class, $request);
    }
}
