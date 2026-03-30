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

use App\Exception\BusinessException;
use App\Model\Notice\NoticeModel;
use App\Model\Notice\NoticeReadModel;
use App\Model\Notice\NoticeTargetModel;
use App\Service\Notice\BatchPushToUsers;
use App\Service\Notice\Dto\UpdateNoticeDto;
use App\Service\Notice\GetUnreadCount;
use App\Service\Notice\MarkRead;
use App\Service\Notice\SetTargets;
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

    public function testUpdateNoticeDtoFromArray(): void
    {
        $dto = UpdateNoticeDto::fromArray(['id' => 1, 'title' => '测试', 'is_top' => 1]);

        $this->assertEquals(1, $dto->id);
        $this->assertEquals('测试', $dto->title);
        $this->assertEquals(1, $dto->isTop);
        $this->assertNull($dto->content);
    }

    public function testUpdateNoticeDtoToUpdateArray(): void
    {
        $dto = UpdateNoticeDto::fromArray(['id' => 1, 'title' => '标题', 'notice_type' => 2]);
        $result = $dto->toUpdateArray();

        $this->assertEquals('标题', $result['title']);
        $this->assertEquals(2, $result['notice_type']);
        $this->assertArrayNotHasKey('content', $result);
    }

    public function testUpdateNoticeDtoPublishedFields(): void
    {
        $dto = UpdateNoticeDto::fromArray([
            'id' => 1,
            'title' => '标题',
            'is_top' => 1,
            'is_popup' => 1,
            'expire_time' => '2026-12-31 23:59:59',
        ]);

        $result = $dto->getPublishedUpdateFields();

        $this->assertArrayHasKey('is_top', $result);
        $this->assertArrayHasKey('is_popup', $result);
        $this->assertArrayHasKey('expire_time', $result);
        $this->assertArrayNotHasKey('title', $result);
    }

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

    public function testSetTargetsNoticeNotFound(): void
    {
        $noticeModel = Mockery::mock(NoticeModel::class);
        $noticeModel->shouldReceive('find')->with(999)->andReturn(null);

        $targetModel = Mockery::mock(NoticeTargetModel::class);

        $action = new SetTargets($noticeModel, $targetModel);

        $this->expectException(BusinessException::class);
        $action->handle(['id' => 999, 'target_type' => 1]);
    }

    public function testSetTargetsAllUsers(): void
    {
        $notice = Mockery::mock(NoticeModel::class)->makePartial();

        $noticeModel = Mockery::mock(NoticeModel::class);
        $noticeModel->shouldReceive('find')->with(1)->andReturn($notice);

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
        $noticeModel->shouldReceive('find')->with(1)->andReturn($notice);

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
