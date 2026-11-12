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

namespace HyperfTest\Cases\Unit\Service\Role;

use App\Dto\Role\RoleRequest;
use App\Exception\BusinessException;
use App\Model\Admin\SysAdminModel;
use App\Model\Rbac\SysRoleModel;
use App\Service\Role\RemoveRoleFromAdmin;
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\TestCase;

/**
 * 移除角色必须按 role_code 操作 pivot 表.
 *
 * roles() 的 belongsToMany 以 sys_admin_role.role_code ↔ sys_role.role_code 连接，
 * detach() 按 role_code 列匹配。接口传的是角色自增 id，直接用会匹配不到任何记录，
 * 接口返回成功但角色实际还在管理员身上。
 * @internal
 * @coversNothing
 */
class RemoveRoleFromAdminTest extends TestCase
{
    private const ADMIN_CODE = 'remove_role_probe';

    private int $adminId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 1, 'username' => 'tester', 'is_admin' => 1]);
        $this->cleanUp();

        $this->adminId = (int) Db::table('sys_admin')->insertGetId([
            'username' => self::ADMIN_CODE,
            'admin_code' => self::ADMIN_CODE,
            'password' => 'x',
            'name' => '移除角色探针',
            'status' => 1,
            'is_admin' => 0,
            'login_failure_count' => 0,
            'create_time' => millisecond(),
        ]);
    }

    protected function tearDown(): void
    {
        $this->cleanUp();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testRemovesOnlyTheGivenRole(): void
    {
        $this->bindRoles(['super_admin', 'normal_user']);
        $roleId = $this->roleIdOf('super_admin');

        $result = (new RemoveRoleFromAdmin(new SysAdminModel(), new SysRoleModel()))
            ->handle(RoleRequest::fromArray([
                'admin_id' => $this->adminId,
                'role_id' => $roleId,
            ]));

        $this->assertSame($roleId, $result['role_id']);
        $this->assertSame(['normal_user'], $this->currentRoleCodes(), 'super_admin 必须真的被移除');
    }

    /**
     * 这条锁定 bug 本身：接口返回成功后，角色不能还留在 pivot 里.
     */
    public function testRoleIsActuallyGoneFromPivot(): void
    {
        $this->bindRoles(['super_admin']);
        $roleId = $this->roleIdOf('super_admin');

        (new RemoveRoleFromAdmin(new SysAdminModel(), new SysRoleModel()))
            ->handle(RoleRequest::fromArray([
                'admin_id' => $this->adminId,
                'role_id' => $roleId,
            ]));

        $this->assertSame([], $this->currentRoleCodes(), '移除唯一的角色后 pivot 应为空');
    }

    public function testRejectsMissingAdmin(): void
    {
        $this->expectException(BusinessException::class);

        (new RemoveRoleFromAdmin(new SysAdminModel(), new SysRoleModel()))
            ->handle(RoleRequest::fromArray([
                'admin_id' => 999999,
                'role_id' => $this->roleIdOf('normal_user'),
            ]));
    }

    public function testRejectsMissingRole(): void
    {
        $this->expectException(BusinessException::class);

        (new RemoveRoleFromAdmin(new SysAdminModel(), new SysRoleModel()))
            ->handle(RoleRequest::fromArray([
                'admin_id' => $this->adminId,
                'role_id' => 999999,
            ]));
    }

    /**
     * @param array<int, string> $roleCodes
     */
    private function bindRoles(array $roleCodes): void
    {
        foreach ($roleCodes as $code) {
            Db::table('sys_admin_role')->insert([
                'admin_code' => self::ADMIN_CODE,
                'role_code' => $code,
                'create_by' => 1,
                'create_time' => millisecond(),
            ]);
        }
    }

    private function roleIdOf(string $roleCode): int
    {
        return (int) Db::table('sys_role')->where('role_code', $roleCode)->value('id');
    }

    /**
     * @return array<int, string>
     */
    private function currentRoleCodes(): array
    {
        $codes = Db::table('sys_admin_role')
            ->where('admin_code', self::ADMIN_CODE)
            ->pluck('role_code')
            ->toArray();

        sort($codes);

        return $codes;
    }

    private function cleanUp(): void
    {
        Db::table('sys_admin_role')->where('admin_code', self::ADMIN_CODE)->delete();
        Db::table('sys_admin')->where('admin_code', self::ADMIN_CODE)->delete();
    }
}
