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

use App\Dto\Attachment\AttachmentRequest;
use App\Exception\BusinessException;
use App\Model\Attachment\SysAttachmentModel;
use App\Service\Attachment\Delete;
use App\Service\Attachment\GetList;
use App\Service\Attachment\Upload;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

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
        $request = AttachmentRequest::fromArray([
            'file_name' => 'test.jpg',
            'file_path' => '/uploads/test.jpg',
            'file_size' => 1024,
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $created = Mockery::mock(stdClass::class);
        $created->id = 1;
        $created->shouldReceive('toArray')->andReturn(['id' => 1, 'file_name' => 'test.jpg']);

        $model = Mockery::mock(SysAttachmentModel::class);
        $model->shouldReceive('create')->once()->andReturn($created);

        $result = (new Upload($model))->handle($request);

        $this->assertSame(1, $result['id']);
    }

    public function testUploadDefaultsStorageTypeAndStatus(): void
    {
        $captured = null;
        $created = Mockery::mock(stdClass::class);
        $created->id = 1;
        $created->shouldReceive('toArray')->andReturn(['id' => 1]);

        $model = Mockery::mock(SysAttachmentModel::class);
        $model->shouldReceive('create')->once()->withArgs(function (array $data) use (&$captured) {
            $captured = $data;
            return true;
        })->andReturn($created);

        (new Upload($model))->handle(AttachmentRequest::fromArray([
            'file_name' => 'a.txt',
            'file_path' => '/tmp/a.txt',
            'file_size' => 3,
            'file_type' => 'txt',
        ]));

        $this->assertSame('local', $captured['storage_type']);
        $this->assertSame(1, $captured['status']);
    }

    public function testDeleteSuccess(): void
    {
        $attachment = Mockery::mock(SysAttachmentModel::class)->makePartial();
        $attachment->shouldReceive('delete')->once()->andReturn(true);

        $model = Mockery::mock(SysAttachmentModel::class);
        $model->shouldReceive('find')->with(1)->andReturn($attachment);

        $this->assertTrue((new Delete($model))->handle(1));
    }

    public function testDeleteNotFound(): void
    {
        $model = Mockery::mock(SysAttachmentModel::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);

        $this->expectException(BusinessException::class);
        (new Delete($model))->handle(999);
    }

    public function testGetListClassExists(): void
    {
        $this->assertTrue(class_exists(GetList::class));
    }
}
