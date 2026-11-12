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

use App\Dto\Admin\AdminRequest;
use App\Exception\BusinessException;
use App\Model\Admin\SysAdminModel;
use App\Model\Rbac\SysRoleModel;
use App\Service\Admin\Permission\RevokeRole;
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\TestCase;

/**
 * 回收角色必须按 role_code 操作 pivot 表，不能用自增 id.
 *
 * 背景：roles() 的 belongsToMany 以 sys_admin_role.role_code ↔ sys_role.role_code
 * 连接，sync() 传入的值会被直接写进 role_code 列。曾用 pluck('id')，
 * 结果原有角色全被删掉、换成一条 role_code='2' 的无效记录，管理员权限被清空。
 * @internal
 * @coversNothing
 */
class RevokeRoleTest extends TestCase
{
    private const ADMIN_CODE = 'revoke_probe';

    protected function setUp(): void
    {
        parent::setUp();
        Db::beginTransaction();
        Context::set('auth_user', ['id' => 1, 'username' => 'tester', 'is_admin' => 1]);

        Db::table('sys_admin')->insert([
            'username' => self::ADMIN_CODE,
            'admin_code' => self::ADMIN_CODE,
            'password' => 'x',
            'name' => '回收角色探针',
            'status' => 1,
            'is_admin' => 0,
            'login_failure_count' => 0,
            'create_time' => millisecond(),
        ]);
    }

    protected function tearDown(): void
    {
        Db::rollBack();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testRevokesOnlyTheGivenRoleAndKeepsOthers(): void
    {
        $this->bindRoles(['super_admin', 'normal_user']);
        $revokeId = $this->roleIdOf('super_admin');

        $result = $this->revoke([$revokeId]);

        $this->assertSame([$revokeId], $result['removed_role_ids']);
        $this->assertSame(
            ['normal_user'],
            $this->currentRoleCodes(),
            '只应移除 super_admin，normal_user 必须保留'
        );
    }

    public function testPivotNeverContainsNumericId(): void
    {
        $this->bindRoles(['super_admin', 'normal_user']);

        $this->revoke([$this->roleIdOf('super_admin')]);

        // pivot 里的 role_code 必须都是真实角色标识，不能是 "1" / "2" 这类 id
        foreach ($this->currentRoleCodes() as $code) {
            $this->assertNotSame('', trim($code));
            $this->assertTrue(
                SysRoleModel::query()->where('role_code', $code)->exists(),
                "pivot 里出现了不存在的 role_code: {$code}"
            );
        }
    }

    public function testRejectsUnknownRole(): void
    {
        $this->bindRoles(['normal_user']);

        $this->expectException(BusinessException::class);

        $this->revoke([999999]);
    }

    public function testRejectsEmptyRoleIds(): void
    {
        $this->bindRoles(['normal_user']);

        $this->expectException(BusinessException::class);

        $this->revoke([]);
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

    /**
     * @param array<int, int> $roleIds
     * @return array{admin_id: int, removed_role_ids: array<int, int>}
     */
    private function revoke(array $roleIds): array
    {
        $adminId = (int) Db::table('sys_admin')->where('admin_code', self::ADMIN_CODE)->value('id');

        $request = AdminRequest::fromArray([
            'admin_id' => $adminId,
            'role_ids' => $roleIds,
        ]);

        return (new RevokeRole(new SysAdminModel(), new SysRoleModel()))->handle($request);
    }
}
