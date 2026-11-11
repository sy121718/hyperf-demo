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

namespace HyperfTest\Cases\Unit\Service\Notice;

use App\Dto\Notice\NoticeRequest;
use App\Exception\BusinessException;
use App\Model\Notify\NoticeModel;
use App\Model\Notify\NoticeReadModel;
use App\Model\Notify\NoticeTargetModel;
use App\Service\Notice\BatchPushToUsers;
use App\Service\Notice\GetUnreadCount;
use App\Service\Notice\MarkRead;
use App\Service\Notice\SetTargets;
use App\Service\Notice\Update;
use Hyperf\Context\Context;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class NoticeTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('currentUser');
    }

    // ==================== Request DTO ====================

    public function testRequestFromArrayFillsTypedFields(): void
    {
        $request = NoticeRequest::fromArray(['id' => 1, 'title' => '测试', 'is_top' => 1]);

        $this->assertSame(1, $request->id);
        $this->assertSame('测试', $request->title);
        $this->assertSame(1, $request->is_top);
        $this->assertNull($request->content);
    }

    public function testRequestToDbArrayDropsVirtualFields(): void
    {
        $request = NoticeRequest::fromArray([
            'id' => 1,
            'title' => '标题',
            'notice_type' => 2,
            'notice_id' => 7,
            'user_id' => 3,
        ]);

        $data = $request->toDbArrayFiltered();

        $this->assertSame('标题', $data['title']);
        $this->assertSame(2, $data['notice_type']);
        $this->assertArrayNotHasKey('id', $data);
        $this->assertArrayNotHasKey('notice_id', $data);
        $this->assertArrayNotHasKey('user_id', $data);
    }

    // ==================== Update ====================

    public function testUpdateDraftAllowsAllFields(): void
    {
        $captured = null;

        $notice = Mockery::mock(NoticeModel::class);
        $notice->shouldReceive('getAttribute')->andReturnUsing(fn ($k) => $k === 'status' ? 0 : null);
        $notice->shouldReceive('update')->once()->withArgs(function (array $data) use (&$captured) {
            $captured = $data;
            return true;
        })->andReturn(true);

        $model = Mockery::mock(NoticeModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '通知')->andReturn($notice);

        (new Update($model))->handle(
            NoticeRequest::fromArray(['id' => 1, 'title' => '新标题', 'is_top' => 1])
        );

        $this->assertSame('新标题', $captured['title']);
        $this->assertSame(1, $captured['is_top']);
    }

    public function testUpdatePublishedNoticeOnlyAllowsPresentationFields(): void
    {
        $captured = null;

        $notice = Mockery::mock(NoticeModel::class);
        $notice->shouldReceive('getAttribute')->andReturnUsing(fn ($k) => $k === 'status' ? 1 : null);
        $notice->shouldReceive('update')->once()->withArgs(function (array $data) use (&$captured) {
            $captured = $data;
            return true;
        })->andReturn(true);

        $model = Mockery::mock(NoticeModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '通知')->andReturn($notice);

        (new Update($model))->handle(
            NoticeRequest::fromArray(['id' => 1, 'title' => '想改标题', 'is_popup' => 1])
        );

        // 已发布公告不允许改标题，只保留置顶/弹窗/过期时间
        $this->assertArrayNotHasKey('title', $captured);
        $this->assertSame(1, $captured['is_popup']);
    }

    public function testUpdateNotFound(): void
    {
        $model = Mockery::mock(NoticeModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '通知不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Update($model))->handle(NoticeRequest::fromArray(['id' => 999, 'title' => 'x']));
    }

    // ==================== MarkRead ====================

    public function testMarkReadFirstTime(): void
    {
        $noticeModel = Mockery::mock(NoticeModel::class);
        $noticeModel->shouldReceive('where->increment')->once();

        $readModel = Mockery::mock(NoticeReadModel::class);
        $readModel->shouldReceive('where->where->exists')->andReturn(false);
        $readModel->shouldReceive('create')->once()->andReturn(true);

        $action = new MarkRead($noticeModel, $readModel);
        $this->assertTrue($action->handle(['notice_id' => 1, 'user_id' => 1]));
    }

    public function testMarkReadAlreadyRead(): void
    {
        $noticeModel = Mockery::mock(NoticeModel::class);

        $readModel = Mockery::mock(NoticeReadModel::class);
        $readModel->shouldReceive('where->where->exists')->andReturn(true);

        $action = new MarkRead($noticeModel, $readModel);
        $this->assertTrue($action->handle(['notice_id' => 1, 'user_id' => 1]));
    }

    public function testMarkReadZeroUserId(): void
    {
        $noticeModel = Mockery::mock(NoticeModel::class);
        $readModel = Mockery::mock(NoticeReadModel::class);

        $action = new MarkRead($noticeModel, $readModel);

        $this->expectException(BusinessException::class);
        $action->handle(['notice_id' => 1, 'user_id' => 0]);
    }

    // ==================== GetUnreadCount ====================

    public function testGetUnreadCount(): void
    {
        $noticeModel = Mockery::mock(NoticeModel::class);
        $noticeModel->shouldReceive('where->where->count')->andReturn(10);

        $readModel = Mockery::mock(NoticeReadModel::class);
        $readModel->shouldReceive('where->count')->andReturn(3);

        $action = new GetUnreadCount($noticeModel, $readModel);
        $this->assertEquals(7, $action->handle(1));
    }

    public function testGetUnreadCountZeroUser(): void
    {
        $noticeModel = Mockery::mock(NoticeModel::class);
        $readModel = Mockery::mock(NoticeReadModel::class);

        $action = new GetUnreadCount($noticeModel, $readModel);

        $this->expectException(BusinessException::class);
        $action->handle(0);
    }

    public function testGetUnreadCountAllRead(): void
    {
        $noticeModel = Mockery::mock(NoticeModel::class);
        $noticeModel->shouldReceive('where->where->count')->andReturn(5);

        $readModel = Mockery::mock(NoticeReadModel::class);
        $readModel->shouldReceive('where->count')->andReturn(10);

        $action = new GetUnreadCount($noticeModel, $readModel);
        $this->assertEquals(0, $action->handle(1));
    }

    // ==================== SetTargets ====================

    public function testSetTargetsNoticeNotFound(): void
    {
        $noticeModel = Mockery::mock(NoticeModel::class);
        $noticeModel->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '通知不存在'));

        $targetModel = Mockery::mock(NoticeTargetModel::class);

        $action = new SetTargets($noticeModel, $targetModel);

        $this->expectException(BusinessException::class);
        $action->handle(['id' => 999, 'target_type' => 1]);
    }

    public function testSetTargetsAllUsers(): void
    {
        $notice = Mockery::mock(NoticeModel::class)->makePartial();

        $noticeModel = Mockery::mock(NoticeModel::class);
        $noticeModel->shouldReceive('findOrNotFound')->with(1, '通知')->andReturn($notice);

        $targetModel = Mockery::mock(NoticeTargetModel::class);
        $targetModel->shouldReceive('where->delete')->once();
        $targetModel->shouldReceive('insert')->once()->andReturn(true);

        $action = new SetTargets($noticeModel, $targetModel);
        $this->assertTrue($action->handle(['id' => 1, 'target_type' => 1]));
    }

    public function testSetTargetsSpecificUsers(): void
    {
        $notice = Mockery::mock(NoticeModel::class)->makePartial();

        $noticeModel = Mockery::mock(NoticeModel::class);
        $noticeModel->shouldReceive('findOrNotFound')->with(1, '通知')->andReturn($notice);

        $targetModel = Mockery::mock(NoticeTargetModel::class);
        $targetModel->shouldReceive('where->delete')->once();
        $targetModel->shouldReceive('insert')->once()->with(Mockery::on(function ($arg) {
            return count($arg) === 2;
        }))->andReturn(true);

        $action = new SetTargets($noticeModel, $targetModel);
        $this->assertTrue($action->handle([
            'id' => 1,
            'target_type' => 4,
            'target_ids' => [1, 2],
        ]));
    }

    // ==================== BatchPushToUsers ====================

    public function testBatchPushEmptyNoticeId(): void
    {
        $targetModel = Mockery::mock(NoticeTargetModel::class);

        $action = new BatchPushToUsers($targetModel);

        $this->expectException(BusinessException::class);
        $action->handle(['notice_id' => 0, 'target_users' => [1]]);
    }

    public function testBatchPushEmptyUsers(): void
    {
        $targetModel = Mockery::mock(NoticeTargetModel::class);

        $action = new BatchPushToUsers($targetModel);

        $this->expectException(BusinessException::class);
        $action->handle(['notice_id' => 1, 'target_users' => []]);
    }

    public function testBatchPushSuccess(): void
    {
        $targetModel = Mockery::mock(NoticeTargetModel::class);
        $targetModel->shouldReceive('insert')->once()->andReturn(true);

        $action = new BatchPushToUsers($targetModel);
        $result = $action->handle(['notice_id' => 1, 'target_users' => [1, 2, 3]]);

        $this->assertEquals(3, $result);
    }
}
