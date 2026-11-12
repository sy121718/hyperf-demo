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
use App\Service\Role\AssignRolesToAdmin;
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\TestCase;

/**
 * 分配角色必须按 role_code 写 pivot 表.
 *
 * roles() 的 belongsToMany 以 sys_admin_role.role_code ↔ sys_role.role_code
 * 连接，sync() 传入的值会被直接写进 role_code 列。接口传的是角色自增 id，
 * 必须先解析成 role_code，否则 pivot 里会出现 role_code='1' 这类无效记录，
 * 管理员实际一个角色都没拿到。
 *
 * 种子数据真实提交，不包事务：AssignRolesToAdmin 内部用 parallel() 并发查库，
 * 协程被分配到另外的连接，看不到未提交的事务数据。
 * @internal
 * @coversNothing
 */
class AssignRolesToAdminTest extends TestCase
{
    private const ADMIN_CODE = 'assign_probe';

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
            'name' => '分配角色探针',
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

    public function testWritesRoleCodesNotIds(): void
    {
        $roleIds = $this->roleIdsOf(['super_admin', 'normal_user']);

        $result = (new AssignRolesToAdmin(new SysAdminModel(), new SysRoleModel()))
            ->handle(RoleRequest::fromArray([
                'admin_id' => $this->adminId,
                'role_ids' => $roleIds,
            ]));

        $this->assertSame($roleIds, $result['role_ids']);

        $codes = $this->currentRoleCodes();
        sort($codes);
        $this->assertSame(['normal_user', 'super_admin'], $codes, 'pivot 里必须是角色标识而不是 id');
    }

    public function testReplacesExistingRoles(): void
    {
        Db::table('sys_admin_role')->insert([
            'admin_code' => self::ADMIN_CODE,
            'role_code' => 'normal_user',
            'create_by' => 1,
            'create_time' => millisecond(),
        ]);

        (new AssignRolesToAdmin(new SysAdminModel(), new SysRoleModel()))
            ->handle(RoleRequest::fromArray([
                'admin_id' => $this->adminId,
                'role_ids' => $this->roleIdsOf(['super_admin']),
            ]));

        $this->assertSame(['super_admin'], $this->currentRoleCodes());
    }

    public function testRejectsUnknownRole(): void
    {
        $this->expectException(BusinessException::class);

        (new AssignRolesToAdmin(new SysAdminModel(), new SysRoleModel()))
            ->handle(RoleRequest::fromArray([
                'admin_id' => $this->adminId,
                'role_ids' => [999999],
            ]));
    }

    public function testRejectsMissingAdmin(): void
    {
        $this->expectException(BusinessException::class);

        (new AssignRolesToAdmin(new SysAdminModel(), new SysRoleModel()))
            ->handle(RoleRequest::fromArray([
                'admin_id' => 999999,
                'role_ids' => $this->roleIdsOf(['normal_user']),
            ]));
    }

    /**
     * @param array<int, string> $roleCodes
     * @return array<int, int>
     */
    private function roleIdsOf(array $roleCodes): array
    {
        return array_map(
            static fn (string $code) => (int) Db::table('sys_role')->where('role_code', $code)->value('id'),
            $roleCodes
        );
    }

    /**
     * @return array<int, string>
     */
    private function currentRoleCodes(): array
    {
        return Db::table('sys_admin_role')
            ->where('admin_code', self::ADMIN_CODE)
            ->pluck('role_code')
            ->toArray();
    }

    private function cleanUp(): void
    {
        Db::table('sys_admin_role')->where('admin_code', self::ADMIN_CODE)->delete();
        Db::table('sys_admin')->where('admin_code', self::ADMIN_CODE)->delete();
    }
}
