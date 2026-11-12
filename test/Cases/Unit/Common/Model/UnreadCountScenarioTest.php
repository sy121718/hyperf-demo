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

use App\Model\Notify\NoticeModel;
use App\Model\Notify\NoticeReadModel;
use App\Service\Notice\GetUnreadCount;
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\TestCase;

/**
 * 未读数必须在真实数据上算对，尤其是「过期通知的已读记录」这种边界.
 * @internal
 * @coversNothing
 */
class UnreadCountScenarioTest extends TestCase
{
    private int $userId;

    /** @var array<int, int> */
    private array $noticeIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        Db::beginTransaction();

        $this->userId = 900001;
        Context::set('auth_user', ['id' => $this->userId, 'username' => 'unread_probe', 'is_admin' => 0]);
    }

    protected function tearDown(): void
    {
        Db::rollBack();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    /**
     * 过期通知不计入已发布，它的已读记录也不能被减掉.
     *
     * 旧算法（已发布总数 - 已读记录数）在这个场景下会算成 2 - 2 = 0，
     * 但实际有 2 条有效通知都没读，答案应该是 2。
     */
    public function testExpiredNoticeReadRecordDoesNotReduceCount(): void
    {
        // 两条有效通知，都没读
        $liveA = $this->createNotice(status: 1, expireTime: null);
        $liveB = $this->createNotice(status: 1, expireTime: date('Y-m-d H:i:s', time() + 3600));

        // 一条已过期通知，并且用户读过它
        $expired = $this->createNotice(status: 1, expireTime: date('Y-m-d H:i:s', time() - 3600));
        $this->markRead($expired);

        $count = (new GetUnreadCount(new NoticeModel(), new NoticeReadModel()))->handle($this->userId);

        $this->assertSame(2, $count, '过期通知的已读记录不应压低未读数');
    }

    /**
     * 读过一条有效通知，未读数应减一.
     */
    public function testReadingLiveNoticeDecrementsCount(): void
    {
        $a = $this->createNotice(status: 1, expireTime: null);
        $this->createNotice(status: 1, expireTime: null);

        $this->markRead($a);

        $count = (new GetUnreadCount(new NoticeModel(), new NoticeReadModel()))->handle($this->userId);

        $this->assertSame(1, $count);
    }

    private function createNotice(int $status, ?string $expireTime): int
    {
        return (int) Db::table('notice')->insertGetId([
            'title' => 'unread-probe-' . bin2hex(random_bytes(4)),
            'content' => 'x',
            'notice_type' => 1,
            'is_top' => 0,
            'is_popup' => 0,
            'status' => $status,
            'publish_time' => millisecond(),
            'expire_time' => $expireTime,
            'read_count' => 0,
            'create_by' => $this->userId,
            'create_time' => millisecond(),
        ]);
    }

    private function markRead(int $noticeId): void
    {
        Db::table('notice_read')->insert([
            'notice_id' => $noticeId,
            'user_id' => $this->userId,
            'read_time' => millisecond(),
        ]);
    }
}
