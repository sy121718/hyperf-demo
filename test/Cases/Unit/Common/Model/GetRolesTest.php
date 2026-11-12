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

use App\Model\Admin\SysAdminModel;
use App\Service\Admin\Permission\GetRoles;
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\TestCase;

/**
 * GetRoles 的连库验证.
 *
 * 该 Action 改为 findOrNotFound 带 ['roles'] 预加载后取 $admin->roles，
 * 这里确认预加载与延迟加载返回的角色集合一致，且不依赖 N+1 查询。
 *
 * @internal
 * @coversNothing
 */
class GetRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 1, 'username' => 'tester', 'is_admin' => 1]);
        Db::beginTransaction();
    }

    protected function tearDown(): void
    {
        Db::rollBack();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testReturnsRolesOfAdmin(): void
    {
        $adminId = $this->seedAdmin('probe_roles_admin');
        Db::table('sys_admin_role')->insert([
            ['admin_code' => 'probe_roles_admin', 'role_code' => 'normal_user', 'create_by' => 1],
        ]);

        $result = new GetRoles($this->container->get(SysAdminModel::class))->handle($adminId);

        self::assertSame($adminId, $result['admin_id']);
        self::assertCount(1, $result['roles'], '应返回该管理员已分配的角色');
        self::assertSame('normal_user', $result['roles'][0]['role_code']);
    }

    public function testMatchesLazyLoadedRoles(): void
    {
        $adminId = $this->seedAdmin('probe_roles_admin');
        Db::table('sys_admin_role')->insert([
            ['admin_code' => 'probe_roles_admin', 'role_code' => 'normal_user', 'create_by' => 1],
            ['admin_code' => 'probe_roles_admin', 'role_code' => 'super_admin', 'create_by' => 1],
        ]);

        $lazy = SysAdminModel::query()->find($adminId);
        // belongsToMany 的 join 两侧都有 role_code，必须带表前缀
        $lazyCodes = $lazy->roles()->pluck('sys_role.role_code')->sort()->values()->all();

        $result = new GetRoles($this->container->get(SysAdminModel::class))->handle($adminId);
        $eagerCodes = array_column($result['roles'], 'role_code');
        sort($eagerCodes);

        self::assertSame($lazyCodes, $eagerCodes, '预加载结果必须与延迟加载一致');
    }

    public function testReturnsEmptyRolesWhenNoneAssigned(): void
    {
        $adminId = $this->seedAdmin('probe_roles_admin');

        $result = new GetRoles($this->container->get(SysAdminModel::class))->handle($adminId);

        self::assertSame([], $result['roles']);
    }

    private function seedAdmin(string $adminCode): int
    {
        return (int) Db::table('sys_admin')->insertGetId([
            'username' => $adminCode,
            'admin_code' => $adminCode,
            'password' => password_hash('x', PASSWORD_BCRYPT),
            'is_admin' => 0,
            'status' => 1,
            'create_by' => 0,
        ]);
    }
}
