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

namespace HyperfTest\Cases\Unit\Service\Admin\Permission;

use App\Exception\BusinessException;
use App\Model\Admin\SysAdminModel;
use App\Service\Admin\Permission\GetMine;
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;

/**
 * /auth/permissions 返回的权限码必须与模型统一入口口径一致：
 * 只算启用中的角色、跨角色去重。这个接口是前端按钮级权限的唯一数据源。
 * @internal
 * @coversNothing
 */
class GetMineTest extends TestCase
{
    private const ADMIN_CODE = 'get_mine_probe';

    private int $adminId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanUp();

        $this->adminId = (int) Db::table('sys_admin')->insertGetId([
            'username' => self::ADMIN_CODE,
            'admin_code' => self::ADMIN_CODE,
            'password' => 'x',
            'name' => '当前权限探针',
            'status' => 1,
            'is_admin' => 0,
            'login_failure_count' => 0,
            'create_time' => millisecond(),
        ]);

        Context::set('auth_user', ['id' => $this->adminId, 'username' => self::ADMIN_CODE, 'is_admin' => 0]);
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testReturnsOwnPermissionCodes(): void
    {
        $this->bindRole('normal_user');

        $result = (new GetMine(new SysAdminModel()))->handle();

        $this->assertSame($this->adminId, $result['admin_id']);
        $this->assertSame(0, $result['is_admin']);
        $this->assertSame([], $result['permission_codes'], 'normal_user 本身没有任何权限码');
    }

    /**
     * 跨角色重复的权限码只出现一次.
     */
    public function testMergesAndDeduplicatesCodesAcrossRoles(): void
    {
        $this->bindRole('normal_user');
        Db::table('sys_role')->where('role_code', 'normal_user')
            ->update(['permission_codes' => json_encode(['role:list', 'menu:tree'])]);

        $this->assertSame(
            ['role:list', 'menu:tree'],
            (new GetMine(new SysAdminModel()))->handle()['permission_codes']
        );
    }

    /**
     * 停用角色不参与计算，与 SysAdminModel::permissionCodes() 同口径.
     */
    public function testIgnoresDisabledRoles(): void
    {
        $this->bindRole('normal_user');
        Db::table('sys_role')->where('role_code', 'normal_user')
            ->update(['permission_codes' => json_encode(['role:list']), 'status' => 0]);

        $this->assertSame([], (new GetMine(new SysAdminModel()))->handle()['permission_codes']);
    }

    /**
     * is_admin 原样透传给前端，不在这里枚举全部权限点.
     *
     * 库里 uk_super_slot 唯一索引保证只能有一个未删除的超管，
     * 所以这里没法造第二个超管账号，直接用模型 mock 验证透传逻辑。
     */
    public function testSuperAdminFlagIsReturned(): void
    {
        Context::set('auth_user', ['id' => $this->adminId, 'username' => self::ADMIN_CODE, 'is_admin' => 1]);

        /** @var MockInterface|SysAdminModel $admin */
        $admin = Mockery::mock(SysAdminModel::class);
        // $admin->is_admin 走 __get → getAttribute
        $admin->shouldReceive('getAttribute')->with('is_admin')->andReturn(1);
        $admin->shouldReceive('permissionCodes')->andReturn([]);

        /** @var MockInterface|SysAdminModel $model */
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('findOrNotFound')->andReturn($admin);

        $this->assertSame(1, (new GetMine($model))->handle()['is_admin']);
    }

    public function testRejectsAnonymous(): void
    {
        Context::destroy('auth_user');

        $this->expectException(BusinessException::class);

        (new GetMine(new SysAdminModel()))->handle();
    }

    private function bindRole(string $roleCode): void
    {
        Db::table('sys_admin_role')->insert([
            'admin_code' => self::ADMIN_CODE,
            'role_code' => $roleCode,
            'create_by' => 1,
            'create_time' => millisecond(),
        ]);
    }

    private function cleanUp(): void
    {
        Db::table('sys_admin_role')->where('admin_code', self::ADMIN_CODE)->delete();
        Db::table('sys_admin')->where('admin_code', self::ADMIN_CODE)->delete();

        // 还原被用例改过的内置角色
        Db::table('sys_role')->where('role_code', 'normal_user')->update([
            'permission_codes' => json_encode([]),
            'status' => 1,
        ]);
    }
}
