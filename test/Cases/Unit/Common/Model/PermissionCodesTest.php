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
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\TestCase;

/**
 * 权限码聚合的唯一入口验证.
 *
 * 菜单过滤与权限中间件都依赖 SysAdminModel::permissionCodes()，
 * 这里连库验证它和裸 SQL 读到的 permission_codes 口径一致，
 * 避免两边各写一套后出现「菜单可见但接口 403」。
 *
 * 所有用例都在事务里执行并回滚，不污染真实数据。
 *
 * @internal
 * @coversNothing
 */
class PermissionCodesTest extends TestCase
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

    public function testReturnsEmptyWhenAdminHasNoRoles(): void
    {
        $model = SysAdminModel::query()->where('admin_code', 'tester')->first();

        self::assertNotNull($model, '测试库缺少 tester 数据');
        self::assertSame([], $model->permissionCodes());
        self::assertSame([], SysAdminModel::permissionCodesByAdminCode('tester'));
    }

    public function testReturnsEmptyForUnknownAdminCode(): void
    {
        self::assertSame([], SysAdminModel::permissionCodesByAdminCode('__not_exists__'));
    }

    public function testAggregatesEnabledRolesOnly(): void
    {
        Db::table('sys_role')->insert([
            'role_code' => 'probe_disabled',
            'role_name' => '探针-停用角色',
            'status' => 0,
            'permission_codes' => json_encode(['probe:shouldNotAppear']),
        ]);

        Db::table('sys_admin_role')->insert([
            ['admin_code' => 'tester', 'role_code' => 'super_admin', 'create_by' => 1],
            ['admin_code' => 'tester', 'role_code' => 'probe_disabled', 'create_by' => 1],
        ]);

        $codes = SysAdminModel::permissionCodesByAdminCode('tester');

        self::assertContains('admin:all', $codes, '启用角色的权限码应被收集');
        self::assertNotContains('probe:shouldNotAppear', $codes, '停用角色不应参与');
    }

    public function testMatchesRawJsonWithoutDuplicates(): void
    {
        // 让同一个管理员挂两个都含 admin:all 的角色，验证去重
        Db::table('sys_role')->insert([
            'role_code' => 'probe_dup',
            'role_name' => '探针-重复权限',
            'status' => 1,
            'permission_codes' => json_encode(['admin:all', 'probe:only']),
        ]);

        Db::table('sys_admin_role')->insert([
            ['admin_code' => 'tester', 'role_code' => 'super_admin', 'create_by' => 1],
            ['admin_code' => 'tester', 'role_code' => 'probe_dup', 'create_by' => 1],
        ]);

        $codes = SysAdminModel::permissionCodesByAdminCode('tester');

        self::assertSame(count($codes), count(array_unique($codes)), '权限码必须去重');
        self::assertContains('admin:all', $codes);
        self::assertContains('probe:only', $codes);

        $expected = [];
        $raw = Db::table('sys_admin_role as ar')
            ->join('sys_role as r', 'ar.role_code', '=', 'r.role_code')
            ->where('ar.admin_code', 'tester')
            ->where('r.status', 1)
            ->pluck('r.permission_codes');

        foreach ($raw as $json) {
            $expected = array_merge($expected, json_decode((string) $json, true) ?: []);
        }

        $expected = array_values(array_unique($expected));
        sort($expected);
        sort($codes);

        self::assertSame($expected, $codes, '与裸 SQL 读到的结果必须一致');
    }
}
