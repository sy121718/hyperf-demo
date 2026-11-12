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

namespace HyperfTest\Cases\Unit\Service\Dashboard;

use App\Model\Admin\SysAdminModel;
use App\Model\Admin\SysAdminSessionsModel;
use App\Model\Admin\SysLoginLogModel;
use App\Model\Admin\SysOperationLogModel;
use App\Model\Notify\NoticeModel;
use App\Model\Notify\NoticeReadModel;
use App\Model\Rbac\SysMenuModel;
use App\Model\Rbac\SysPermissionDictModel;
use App\Model\Rbac\SysRoleModel;
use App\Service\Dashboard\GetOverview;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;

/**
 * 概览接口按权限决定返回哪些卡片.
 *
 * 核心约束：没有权限的模块既不返回卡片，也不应该产生任何查询——
 * 否则普通管理员打开首页会触发一堆必然 403 的数据访问。
 * @internal
 * @coversNothing
 */
class GetOverviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // unreadNoticeCount() 需要登录态拿当前用户 ID
        Context::set('auth_user', ['id' => 1, 'username' => 'tester', 'is_admin' => 1]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    /**
     * 超管（权限码传 null）拿到全部卡片.
     */
    public function testSuperAdminGetsAllCards(): void
    {
        $overview = $this->makeOverview();

        $cards = $overview->handle(null);

        $keys = array_column($cards, 'key');
        $this->assertCount(8, $cards);
        $this->assertContains('admin_total', $keys);
        $this->assertContains('login_failed_today', $keys);
    }

    /**
     * 只有 role:list 的用户只拿到角色卡片，其余卡片完全不出现.
     */
    public function testOnlyReturnsCardsUserCanSee(): void
    {
        $overview = $this->makeOverview();

        $cards = $overview->handle(['role:list']);

        $this->assertCount(1, $cards);
        $this->assertSame('role_total', $cards[0]['key']);
        $this->assertSame('角色', $cards[0]['title']);
        $this->assertSame(3, $cards[0]['value']);
        $this->assertSame('/system/role', $cards[0]['path']);
    }

    /**
     * 没有任何权限码时返回空数组，不报错.
     */
    public function testReturnsEmptyWhenNoPermission(): void
    {
        $overview = $this->makeOverview();

        $this->assertSame([], $overview->handle([]));
    }

    /**
     * 无权限的模块不能触发查询：只要调用了就会被 Mockery 记为未预期调用.
     */
    public function testDoesNotQueryModulesWithoutPermission(): void
    {
        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldNotReceive('query');

        $roleQuery = Mockery::mock(Builder::class);
        $roleQuery->shouldReceive('count')->once()->andReturn(2);

        $roleModel = Mockery::mock(SysRoleModel::class);
        $roleModel->shouldReceive('query')->once()->andReturn($roleQuery);

        $overview = new GetOverview(
            $adminModel,
            $roleModel,
            ...$this->makeUnusedDependencies(),
        );

        $cards = $overview->handle(['role:list']);

        $this->assertSame('role_total', $cards[0]['key']);
    }

    /**
     * @return array<int, mixed>
     */
    private function makeUnusedDependencies(): array
    {
        $mocks = [];
        foreach ([
            SysAdminSessionsModel::class,
            NoticeModel::class,
            NoticeReadModel::class,
            SysMenuModel::class,
            SysPermissionDictModel::class,
            SysOperationLogModel::class,
            SysLoginLogModel::class,
        ] as $class) {
            /** @var MockInterface $mock */
            $mock = Mockery::mock($class);
            $mock->shouldNotReceive('query');
            $mocks[] = $mock;
        }

        return $mocks;
    }

    private function makeOverview(): GetOverview
    {
        $adminQuery = Mockery::mock(Builder::class);
        $adminQuery->shouldReceive('count')->andReturn(5);
        $adminModel = Mockery::mock(SysAdminModel::class);
        $adminModel->shouldReceive('query')->andReturn($adminQuery);

        $roleQuery = Mockery::mock(Builder::class);
        $roleQuery->shouldReceive('count')->andReturn(3);
        $roleModel = Mockery::mock(SysRoleModel::class);
        $roleModel->shouldReceive('query')->andReturn($roleQuery);

        $sessionQuery = Mockery::mock(Builder::class);
        $sessionQuery->shouldReceive('where->count')->andReturn(7);
        $sessionModel = Mockery::mock(SysAdminSessionsModel::class);
        $sessionModel->shouldReceive('query')->andReturn($sessionQuery);

        $noticeModel = Mockery::mock(NoticeModel::class);
        $noticeModel->shouldReceive('getTable')->andReturn('notice');
        $noticeModel->shouldReceive('published->whereNotExists->count')->andReturn(4);

        $readModel = Mockery::mock(NoticeReadModel::class);
        $readModel->shouldReceive('getTable')->andReturn('notice_read');

        $menuQuery = Mockery::mock(Builder::class);
        $menuQuery->shouldReceive('where->count')->andReturn(21);
        $menuModel = Mockery::mock(SysMenuModel::class);
        $menuModel->shouldReceive('query')->andReturn($menuQuery);

        $permQuery = Mockery::mock(Builder::class);
        $permQuery->shouldReceive('where->count')->andReturn(111);
        $permModel = Mockery::mock(SysPermissionDictModel::class);
        $permModel->shouldReceive('query')->andReturn($permQuery);

        $opQuery = Mockery::mock(Builder::class);
        $opQuery->shouldReceive('where->where->where->count')->andReturn(9);
        $opModel = Mockery::mock(SysOperationLogModel::class);
        $opModel->shouldReceive('query')->andReturn($opQuery);

        $loginQuery = Mockery::mock(Builder::class);
        $loginQuery->shouldReceive('where->where->where->count')->andReturn(2);
        $loginModel = Mockery::mock(SysLoginLogModel::class);
        $loginModel->shouldReceive('query')->andReturn($loginQuery);

        return new GetOverview(
            $adminModel,
            $roleModel,
            $sessionModel,
            $noticeModel,
            $readModel,
            $menuModel,
            $permModel,
            $opModel,
            $loginModel,
        );
    }
}
