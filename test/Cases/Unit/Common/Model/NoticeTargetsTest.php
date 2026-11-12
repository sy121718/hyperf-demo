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

use App\Dto\Notice\NoticeRequest;
use App\Model\Notify\NoticeModel;
use App\Model\Notify\NoticeTargetModel;
use App\Service\Notice\SetTargets;
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\TestCase;

/**
 * SetTargets 的连库验证.
 *
 * mock 版本测不出列约束：notice_target.target_id 是 NOT NULL DEFAULT 0，
 * 全体用户场景原先写 null，在严格模式下必然 1048 失败。
 * 这里走真实数据库，确保写入形状与列定义一致，且先删后插在事务内。
 *
 * @internal
 * @coversNothing
 */
class NoticeTargetsTest extends TestCase
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

    public function testAllUsersTargetWritesZeroNotNull(): void
    {
        $noticeId = $this->createNotice();

        $this->action()->handle(NoticeRequest::fromArray([
            'id' => $noticeId,
            'target_type' => 1,
        ]));

        $rows = Db::table('notice_target')->where('notice_id', $noticeId)->get();

        self::assertCount(1, $rows, '全体用户应只写一行');
        self::assertSame(0, (int) $rows[0]->target_id, 'target_id 用 0 表示不指定对象');
        self::assertSame(1, (int) $rows[0]->target_type);
    }

    public function testSpecificUsersReplacesPreviousTargets(): void
    {
        $noticeId = $this->createNotice();

        Db::table('notice_target')->insert([
            ['notice_id' => $noticeId, 'target_type' => 4, 'target_id' => 99],
        ]);

        $this->action()->handle(NoticeRequest::fromArray([
            'id' => $noticeId,
            'target_type' => 4,
            'target_ids' => [1, 2],
        ]));

        $rows = Db::table('notice_target')->where('notice_id', $noticeId)->orderBy('target_id')->get();

        self::assertCount(2, $rows, '旧目标应被替换而不是累加');
        self::assertSame([1, 2], array_map(static fn ($r) => (int) $r->target_id, $rows->all()));
    }

    public function testOnlyTouchesTargetsOfGivenNotice(): void
    {
        $noticeId = $this->createNotice();
        $otherId = $this->createNotice();

        Db::table('notice_target')->insert([
            ['notice_id' => $otherId, 'target_type' => 4, 'target_id' => 77],
        ]);

        $this->action()->handle(NoticeRequest::fromArray([
            'id' => $noticeId,
            'target_type' => 4,
            'target_ids' => [5],
        ]));

        $otherRows = Db::table('notice_target')->where('notice_id', $otherId)->get();

        self::assertCount(1, $otherRows, '其他通知的目标不应被误删');
        self::assertSame(77, (int) $otherRows[0]->target_id);
    }

    public function testMissingNoticeIsRejectedBeforeAnyWrite(): void
    {
        Db::table('notice_target')->insert([
            ['notice_id' => 88888, 'target_type' => 4, 'target_id' => 66],
        ]);

        try {
            $this->action()->handle(NoticeRequest::fromArray([
                'id' => 88888,
                'target_type' => 4,
                'target_ids' => [1],
            ]));
            self::fail('通知不存在时应抛业务异常');
        } catch (\Throwable) {
            // 预期异常
        }

        $rows = Db::table('notice_target')->where('notice_id', 88888)->get();
        self::assertCount(1, $rows, '校验失败时不能删掉既有目标');
    }

    private function action(): SetTargets
    {
        return new SetTargets(
            $this->container->get(NoticeModel::class),
            $this->container->get(NoticeTargetModel::class),
        );
    }

    private function createNotice(): int
    {
        return (int) Db::table('notice')->insertGetId([
            'title' => '探针通知',
            'notice_type' => 1,
            'status' => 0,
            'create_by' => 1,
        ]);
    }
}
