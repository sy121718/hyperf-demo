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
use RuntimeException;
use Throwable;

/**
 * 超管唯一约束的数据库层验证.
 *
 * sys_admin 上有生成列 super_slot + 唯一索引 uk_super_slot：
 *   super_slot = IF(is_admin = 1 AND deleted_time IS NULL, 1, NULL)
 * 唯一索引允许多个 NULL，所以普通管理员不受限制，而未删除的超管最多一个。
 *
 * 这里直接连库验证索引本身，因为 Service 层的判断可以被新的写入路径绕过。
 * 所有用例都在事务里执行并回滚，不污染真实数据。
 *
 * @internal
 * @coversNothing
 */
class SuperAdminUniquenessTest extends TestCase
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

    public function testHasOtherSuperAdminExcludesGivenId(): void
    {
        $model = new SysAdminModel();

        // 种子数据里 id=1 是超管
        $this->assertTrue(
            $model->hasOtherSuperAdmin(),
            '存在超管时应返回 true'
        );
        $this->assertFalse(
            $model->hasOtherSuperAdmin($this->superAdminId()),
            '排除自己之后不应再算作“其他超管”'
        );
    }

    public function testCannotInsertSecondSuperAdmin(): void
    {
        $this->expectException(Throwable::class);

        SysAdminModel::query()->insert([
            'username' => 'probe_super_2',
            'admin_code' => 'probe_code_2',
            'password' => password_hash('x', PASSWORD_BCRYPT),
            'is_admin' => 1,
            'status' => 1,
            'create_by' => 0,
        ]);
    }

    public function testMultipleNormalAdminsAllowed(): void
    {
        // 普通管理员不受唯一约束影响
        for ($i = 1; $i <= 3; ++$i) {
            SysAdminModel::query()->insert([
                'username' => "probe_normal_{$i}",
                'admin_code' => "probe_code_n{$i}",
                'password' => password_hash('x', PASSWORD_BCRYPT),
                'is_admin' => 0,
                'status' => 1,
                'create_by' => 0,
            ]);
        }

        $count = SysAdminModel::query()->where('username', 'like', 'probe_normal_%')->count();
        $this->assertSame(3, $count);
    }

    public function testSlotIsReleasedAfterSoftDelete(): void
    {
        // 软删现有超管后，应该可以再设一个新的
        $currentId = $this->superAdminId();
        $this->assertGreaterThan(0, $currentId, '缺少用于本用例的超管数据');

        SysAdminModel::query()->where('id', $currentId)->update(['deleted_time' => date('Y-m-d H:i:s.v')]);

        SysAdminModel::query()->insert([
            'username' => 'probe_super_new',
            'admin_code' => 'probe_code_new',
            'password' => password_hash('x', PASSWORD_BCRYPT),
            'is_admin' => 1,
            'status' => 1,
            'create_by' => 0,
        ]);

        $slotHolder = SysAdminModel::query()
            ->where('is_admin', 1)
            ->whereNull('deleted_time')
            ->count();

        $this->assertSame(1, $slotHolder, '软删旧超管后应只剩一个在位超管');
    }

    /**
     * 取一个在位的超管 ID，供用例使用.
     */
    private function superAdminId(): int
    {
        $id = SysAdminModel::query()
            ->where('is_admin', 1)
            ->whereNull('deleted_time')
            ->value('id');

        if ($id === null) {
            throw new RuntimeException('测试库中没有可用的超管数据');
        }

        return (int) $id;
    }
}
