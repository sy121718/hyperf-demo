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
use App\Model\Admin\SysAdminRoleModel;
use App\Model\Rbac\SysRoleModel;
use App\Service\Role\AssignRoles;
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\TestCase;
use RuntimeException;

/**
 * 管理员角色分配的连库验证.
 *
 * 重点在「先删后插」的事务性：写入失败时旧的角色关联必须原样保留，
 * 否则会留下「角色被清空」的中间态。
 *
 * @internal
 * @coversNothing
 */
class AssignRolesTransactionTest extends TestCase
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

    public function testReplacesRolesInOneShot(): void
    {
        $adminId = $this->seedAdminWithRole('probe_admin', 'super_admin');
        $normalRoleId = (int) SysRoleModel::query()->where('role_code', 'normal_user')->value('id');

        $this->action()->handle($adminId, [$normalRoleId]);

        $codes = Db::table('sys_admin_role')
            ->where('admin_code', 'probe_admin')
            ->pluck('role_code')
            ->all();

        self::assertSame(['normal_user'], $codes, '旧角色应被整体替换');
    }

    public function testOldRolesSurviveWhenInsertFails(): void
    {
        $adminId = $this->seedAdminWithRole('probe_admin', 'super_admin');
        $normalRoleId = (int) SysRoleModel::query()->where('role_code', 'normal_user')->value('id');

        // 用会抛异常的模型替换写入步骤，验证 delete 被回滚
        $failingModel = new class extends SysAdminRoleModel {
            public function insert(array $values)
            {
                throw new RuntimeException('模拟写入失败: ' . count($values) . ' 行');
            }
        };

        $action = new AssignRoles(
            $failingModel,
            $this->container->get(SysAdminModel::class),
            $this->container->get(SysRoleModel::class),
        );

        try {
            $action->handle($adminId, [$normalRoleId]);
            self::fail('插入失败时应抛出异常');
        } catch (RuntimeException) {
            // 预期异常
        }

        $codes = Db::table('sys_admin_role')
            ->where('admin_code', 'probe_admin')
            ->pluck('role_code')
            ->all();

        self::assertSame(['super_admin'], $codes, '失败时旧角色必须保留，不能被清空');
    }

    private function action(): AssignRoles
    {
        return new AssignRoles(
            $this->container->get(SysAdminRoleModel::class),
            $this->container->get(SysAdminModel::class),
            $this->container->get(SysRoleModel::class),
        );
    }

    private function seedAdminWithRole(string $adminCode, string $roleCode): int
    {
        $adminId = (int) Db::table('sys_admin')->insertGetId([
            'username' => $adminCode,
            'admin_code' => $adminCode,
            'password' => password_hash('x', PASSWORD_BCRYPT),
            'is_admin' => 0,
            'status' => 1,
            'create_by' => 0,
        ]);

        Db::table('sys_admin_role')->insert([
            'admin_code' => $adminCode,
            'role_code' => $roleCode,
            'create_by' => 1,
        ]);

        return $adminId;
    }
}
