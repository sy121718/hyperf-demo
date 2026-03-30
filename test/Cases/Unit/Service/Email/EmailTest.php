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

use App\Exception\BusinessException;
use App\Model\Email\EmailSendRecipientModel;
use App\Model\Email\EmailSendRecordModel;
use App\Model\Email\EmailTemplateModel;
use App\Service\Email\Send\Send;
use App\Service\Email\Send\Test as TestSend;
use App\Service\Email\Template\Create;
use App\Service\Email\Template\Delete;
use Mockery;
use PHPUnit\Framework\TestCase;

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

        $action = new Create($model);

        $this->expectException(BusinessException::class);
        $action->handle([
            'template_name' => '测试模板',
            'template_code' => 'existing_code',
        ]);
    }

    public function testTemplateCreateEmptyName(): void
    {
        $model = Mockery::mock(EmailTemplateModel::class);

        $action = new Create($model);

        $this->expectException(BusinessException::class);
        $action->handle([
            'template_name' => '',
            'template_code' => 'test_code',
        ]);
    }

    public function testTemplateCreateEmptyCode(): void
    {
        $model = Mockery::mock(EmailTemplateModel::class);

        $action = new Create($model);

        $this->expectException(BusinessException::class);
        $action->handle([
            'template_name' => 'test',
            'template_code' => '',
        ]);
    }

    public function testTemplateDeleteSuccess(): void
    {
        $template = Mockery::mock(EmailTemplateModel::class)->makePartial();
        $template->shouldReceive('delete')->once()->andReturn(true);

        $model = Mockery::mock(EmailTemplateModel::class);
        $model->shouldReceive('find')->with(1)->andReturn($template);

        $action = new Delete($model);
        $this->assertTrue($action->handle(1));
    }

    public function testTemplateDeleteNotFound(): void
    {
        $model = Mockery::mock(EmailTemplateModel::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);

        $action = new Delete($model);

        $this->expectException(BusinessException::class);
        $action->handle(999);
    }

    public function testSendEmptySubject(): void
    {
        $recordModel = Mockery::mock(EmailSendRecordModel::class);
        $recipientModel = Mockery::mock(EmailSendRecipientModel::class);

        $action = new Send($recordModel, $recipientModel);

        $this->expectException(BusinessException::class);
        $action->handle(['subject' => '', 'content' => 'test', 'to' => 'test@example.com']);
    }

    public function testSendEmptyContent(): void
    {
        $recordModel = Mockery::mock(EmailSendRecordModel::class);
        $recipientModel = Mockery::mock(EmailSendRecipientModel::class);

        $action = new Send($recordModel, $recipientModel);

        $this->expectException(BusinessException::class);
        $action->handle(['subject' => 'test', 'content' => '', 'to' => 'test@example.com']);
    }

    public function testSendNoRecipient(): void
    {
        $recordModel = Mockery::mock(EmailSendRecordModel::class);
        $recipientModel = Mockery::mock(EmailSendRecipientModel::class);

        $action = new Send($recordModel, $recipientModel);

        $this->expectException(BusinessException::class);
        $action->handle(['subject' => 'test', 'content' => 'test']);
    }

    public function testTestSendSuccess(): void
    {
        $action = new TestSend();
        $this->assertTrue($action->handle(['to' => 'test@example.com']));
    }

    public function testTestSendEmptyTo(): void
    {
        $action = new TestSend();

        $this->expectException(BusinessException::class);
        $action->handle(['to' => '']);
    }
}
