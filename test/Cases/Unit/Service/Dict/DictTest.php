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

namespace HyperfTest\Cases\Unit\Service\Dict;

use App\Dto\Dict\DictRequest;
use App\Exception\BusinessException;
use App\Model\System\SysDictModel;
use App\Service\Dict\Create;
use App\Service\Dict\Delete;
use App\Service\Dict\GetInfo;
use App\Service\Dict\Update;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class DictTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 1, 'username' => 'admin', 'is_admin' => 1]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testCreateSuccess(): void
    {
        $dict = Mockery::mock(stdClass::class);
        $dict->shouldReceive('toArray')->andReturn(['id' => 1, 'dict_code' => 'gender']);

        $model = Mockery::mock(SysDictModel::class);
        $model->shouldReceive('where->exists')->andReturn(false);
        $model->shouldReceive('create')->once()->andReturn($dict);

        $result = (new Create($model))->handle(DictRequest::fromArray([
            'dict_name' => '性别', 'dict_code' => 'gender',
        ]));

        $this->assertSame(1, $result['id']);
        $this->assertSame('gender', $result['dict_code']);
    }

    public function testCreateDuplicateCode(): void
    {
        $model = Mockery::mock(SysDictModel::class);
        $model->shouldReceive('where->exists')->andReturn(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('字典编码已存在');

        (new Create($model))->handle(DictRequest::fromArray(['dict_name' => 'x', 'dict_code' => 'dup']));
    }

    public function testCreateFallsBackToDefaults(): void
    {
        // 未提交 dict_type / status 时补齐默认值
        $dict = Mockery::mock(stdClass::class);
        $dict->shouldReceive('toArray')->andReturn(['id' => 1]);

        $model = Mockery::mock(SysDictModel::class);
        $model->shouldReceive('where->exists')->andReturn(false);
        $model->shouldReceive('create')->once()->with([
            'dict_name' => '性别',
            'dict_code' => 'gender',
            'dict_type' => 'default',
            'status' => 1,
        ])->andReturn($dict);

        (new Create($model))->handle(DictRequest::fromArray([
            'dict_name' => '性别', 'dict_code' => 'gender',
        ]));
    }

    public function testUpdateSuccess(): void
    {
        $dict = Mockery::mock(SysDictModel::class);
        $dict->shouldReceive('__get')->with('dict_code')->andReturn('old');
        $dict->shouldReceive('getAttribute')->andReturn(null);
        $dict->shouldReceive('update')->once()->andReturn(true);
        $dict->shouldReceive('fresh->toArray')->andReturn(['id' => 1, 'dict_code' => 'new']);

        $model = Mockery::mock(SysDictModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '字典')->andReturn($dict);
        $model->shouldReceive('where->where->exists')->andReturn(false);

        $result = (new Update($model))->handle(DictRequest::fromArray(['id' => 1, 'dict_code' => 'new']));

        $this->assertSame('new', $result['dict_code']);
    }

    public function testUpdateNotFound(): void
    {
        $model = Mockery::mock(SysDictModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '字典不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Update($model))->handle(DictRequest::fromArray(['id' => 999, 'dict_name' => 'x']));
    }

    public function testDeleteSuccess(): void
    {
        $dict = Mockery::mock(SysDictModel::class);
        $dict->shouldReceive('delete')->once()->andReturn(true);

        $model = Mockery::mock(SysDictModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '字典')->andReturn($dict);

        $result = (new Delete($model))->handle(1);

        $this->assertTrue($result);
    }

    public function testDeleteNotFound(): void
    {
        $model = Mockery::mock(SysDictModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '字典不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Delete($model))->handle(999);
    }

    public function testDeleteZeroId(): void
    {
        $model = Mockery::mock(SysDictModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '字典ID不能为空'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('字典ID不能为空');

        (new Delete($model))->handle(0);
    }

    public function testGetInfoNotFound(): void
    {
        $model = Mockery::mock(SysDictModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '字典不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new GetInfo($model))->handle(999);
    }

    public function testGetInfoZeroId(): void
    {
        $model = Mockery::mock(SysDictModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '字典ID不能为空'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('字典ID不能为空');

        (new GetInfo($model))->handle(0);
    }
}
