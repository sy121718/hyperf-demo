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

namespace HyperfTest\Cases\Unit\Service\Attachment;

use App\Exception\BusinessException;
use App\Model\Attachment\SysAttachmentModel;
use App\Service\Attachment\Delete;
use App\Service\Attachment\Dto\UploadAttachmentDto;
use App\Service\Attachment\GetList;
use App\Service\Attachment\Upload;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class AttachmentTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testUploadSuccess(): void
    {
        $dto = new UploadAttachmentDto(
            fileName: 'test.jpg',
            filePath: '/uploads/test.jpg',
            fileSize: 1024,
            fileType: 'image',
            mimeType: 'image/jpeg',
        );

        $created = Mockery::mock(SysAttachmentModel::class)->makePartial();
        $created->id = 1;

        $model = Mockery::mock(SysAttachmentModel::class);
        $model->shouldReceive('create')->once()->andReturn($created);

        $action = new Upload($model);
        $result = $action->handle($dto);

        $this->assertEquals(['id' => 1], $result);
    }

    public function testUploadEmptyFileName(): void
    {
        $dto = new UploadAttachmentDto(
            fileName: '',
            filePath: '/uploads/test.jpg',
            fileSize: 1024,
            fileType: 'image',
        );

        $model = Mockery::mock(SysAttachmentModel::class);
        $action = new Upload($model);

        $this->expectException(BusinessException::class);
        $action->handle($dto);
    }

    public function testDeleteSuccess(): void
    {
        $attachment = Mockery::mock(SysAttachmentModel::class)->makePartial();
        $attachment->shouldReceive('delete')->once()->andReturn(true);

        $model = Mockery::mock(SysAttachmentModel::class);
        $model->shouldReceive('find')->with(1)->andReturn($attachment);

        $action = new Delete($model);
        $result = $action->handle(1);

        $this->assertTrue($result);
    }

    public function testDeleteNotFound(): void
    {
        $model = Mockery::mock(SysAttachmentModel::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);

        $action = new Delete($model);

        $this->expectException(BusinessException::class);
        $action->handle(999);
    }

    public function testGetListClassExists(): void
    {
        $this->assertTrue(class_exists(GetList::class));
    }
}
