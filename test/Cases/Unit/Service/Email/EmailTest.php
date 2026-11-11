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

namespace HyperfTest\Cases\Unit\Service\Email;

use App\Dto\Email\EmailRequest;
use App\Exception\BusinessException;
use App\Model\Notify\EmailSendRecipientModel;
use App\Model\Notify\EmailSendRecordModel;
use App\Model\Notify\EmailTemplateModel;
use App\Service\Email\Send\Send;
use App\Service\Email\Send\Test as TestSend;
use App\Service\Email\Template\Create;
use App\Service\Email\Template\Delete;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class EmailTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testTemplateCreateDuplicateCode(): void
    {
        $model = Mockery::mock(EmailTemplateModel::class);
        $model->shouldReceive('where->exists')->andReturn(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('模板编码已存在');

        (new Create($model))->handle(EmailRequest::fromArray([
            'template_name' => '测试模板',
            'template_code' => 'existing_code',
        ]));
    }

    public function testTemplateCreateSuccess(): void
    {
        $template = Mockery::mock(stdClass::class);
        $template->shouldReceive('toArray')->andReturn(['id' => 1, 'template_code' => 'tpl']);

        $model = Mockery::mock(EmailTemplateModel::class);
        $model->shouldReceive('where->exists')->andReturn(false);
        // 未提交的 template_type / status 补默认值
        $model->shouldReceive('create')->once()->with([
            'template_name' => '测试模板',
            'template_code' => 'tpl',
            'subject' => '标题',
            'content' => '<p>正文</p>',
            'template_type' => 'html',
            'status' => 1,
        ])->andReturn($template);

        $result = (new Create($model))->handle(EmailRequest::fromArray([
            'template_name' => '测试模板',
            'template_code' => 'tpl',
            'subject' => '标题',
            'content' => '<p>正文</p>',
        ]));

        $this->assertSame(1, $result['id']);
    }

    public function testTemplateDeleteSuccess(): void
    {
        $template = Mockery::mock(EmailTemplateModel::class)->makePartial();
        $template->shouldReceive('delete')->once()->andReturn(true);

        $model = Mockery::mock(EmailTemplateModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '邮件模板')->andReturn($template);

        $action = new Delete($model);
        $this->assertTrue($action->handle(1));
    }

    public function testTemplateDeleteNotFound(): void
    {
        $model = Mockery::mock(EmailTemplateModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '邮件模板不存在'));

        $action = new Delete($model);

        $this->expectException(BusinessException::class);
        $action->handle(999);
    }

    public function testSendNoRecipient(): void
    {
        $recordModel = Mockery::mock(EmailSendRecordModel::class);
        $recipientModel = Mockery::mock(EmailSendRecipientModel::class);

        $action = new Send($recordModel, $recipientModel);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('收件人不能为空');

        $action->handle(EmailRequest::fromArray(['subject' => 'test', 'content' => 'test']));
    }

    public function testRecipientsPrefersToList(): void
    {
        // to_list 存在时忽略 to
        $request = EmailRequest::fromArray([
            'to' => 'single@example.com',
            'to_list' => ['a@example.com', 'b@example.com'],
        ]);

        $this->assertSame(['a@example.com', 'b@example.com'], $request->recipients());
    }

    public function testRecipientsWrapsSingleTo(): void
    {
        $request = EmailRequest::fromArray(['to' => 'single@example.com']);

        $this->assertSame(['single@example.com'], $request->recipients());
    }

    public function testSendWritesIntegerStatus(): void
    {
        // status 是 tinyint，写 'pending' 会直接 500
        $record = new stdClass();
        $record->id = 7;

        $recordModel = Mockery::mock(EmailSendRecordModel::class);
        $recordModel->shouldReceive('create')->once()->withArgs(
            static fn (array $data) => $data['status'] === EmailSendRecordModel::STATUS_PENDING
                && $data['total_recipients'] === 1
        )->andReturn($record);

        $recipientModel = Mockery::mock(EmailSendRecipientModel::class);
        $recipientModel->shouldReceive('insert')->once()->withArgs(
            static fn (array $rows) => $rows[0]['status'] === EmailSendRecipientModel::STATUS_PENDING
                && $rows[0]['record_id'] === 7
        )->andReturn(true);

        $result = (new Send($recordModel, $recipientModel))->handle(EmailRequest::fromArray([
            'subject' => '测试',
            'content' => '内容',
            'to' => 'a@example.com',
        ]));

        $this->assertSame(7, $result['record_id']);
        $this->assertSame(1, $result['recipient_count']);
    }

    public function testTestSendSuccess(): void
    {
        $this->assertTrue((new TestSend())->handle(EmailRequest::fromArray(['to' => 'test@example.com'])));
    }
}
