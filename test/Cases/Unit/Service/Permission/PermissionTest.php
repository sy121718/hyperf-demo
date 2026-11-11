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

namespace HyperfTest\Cases\Unit\Service\Permission;

use App\Dto\Permission\PermissionRequest;
use App\Exception\BusinessException;
use App\Model\Rbac\SysPermissionDictModel;
use App\Service\Permission\BatchCreate;
use App\Service\Permission\BatchUpdate;
use App\Service\Permission\Create;
use App\Service\Permission\Delete;
use App\Service\Permission\GetInfo;
use App\Service\Permission\Update;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class PermissionTest extends TestCase
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

    // ==================== Create ====================

    public function testCreatePermissionSuccess(): void
    {
        $created = Mockery::mock(stdClass::class);
        $created->shouldReceive('toArray')->andReturn([
            'id' => 1, 'perm_code' => 'user:add', 'perm_name' => '新增用户',
        ]);

        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);
        $model->shouldReceive('create')->once()->andReturn($created);

        $result = (new Create($model))->handle(
            PermissionRequest::fromArray([
                'perm_code' => 'user:add',
                'perm_name' => '新增用户',
                'api_path' => '/api/user/add',
                'http_method' => 'POST',
            ])
        );

        $this->assertSame('user:add', $result['perm_code']);
    }

    public function testCreatePermissionDuplicateCode(): void
    {
        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('权限编码 user:add 已存在');

        (new Create($model))->handle(
            PermissionRequest::fromArray(['perm_code' => 'user:add', 'perm_name' => '新增'])
        );
    }

    public function testCreatePermissionDefaultsParentId(): void
    {
        $created = Mockery::mock(stdClass::class);
        $created->shouldReceive('toArray')->andReturn(['id' => 1]);

        $captured = null;
        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('isFieldUnique')->andReturn(true);
        $model->shouldReceive('create')->withArgs(function (array $data) use (&$captured) {
            $captured = $data;
            return true;
        })->andReturn($created);

        (new Create($model))->handle(
            PermissionRequest::fromArray(['perm_code' => 'x:y', 'perm_name' => '名'])
        );

        // 未提交 parent_id 时补 0，避免写入 NULL
        $this->assertSame(0, $captured['parent_id']);
    }

    // ==================== Update ====================

    public function testUpdatePermissionSuccess(): void
    {
        $perm = Mockery::mock(SysPermissionDictModel::class);
        $perm->shouldReceive('getAttribute')->andReturnUsing(fn ($k) => match ($k) {
            'perm_code' => 'old_code', default => null,
        });
        $perm->shouldReceive('update')->andReturn(true);
        $perm->shouldReceive('fresh->toArray')->andReturn([
            'id' => 1, 'perm_code' => 'new_code', 'perm_name' => '更新后',
        ]);

        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '权限')->andReturn($perm);
        $model->shouldReceive('isFieldUnique')->andReturn(true);

        $result = (new Update($model))->handle(
            PermissionRequest::fromArray(['id' => 1, 'perm_code' => 'new_code', 'perm_name' => '更新后'])
        );

        $this->assertSame('new_code', $result['perm_code']);
    }

    public function testUpdatePermissionNotFound(): void
    {
        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '权限不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Update($model))->handle(PermissionRequest::fromArray(['id' => 999, 'perm_name' => 'x']));
    }

    public function testUpdatePermissionDuplicateCode(): void
    {
        $perm = Mockery::mock(SysPermissionDictModel::class);
        $perm->shouldReceive('getAttribute')->andReturnUsing(fn ($k) => match ($k) {
            'perm_code' => 'old', default => null,
        });

        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '权限')->andReturn($perm);
        $model->shouldReceive('isFieldUnique')->with(['perm_code' => 'taken'], 1)->andReturn(false);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('权限编码 taken 已存在');

        (new Update($model))->handle(PermissionRequest::fromArray(['id' => 1, 'perm_code' => 'taken']));
    }

    // ==================== Delete ====================

    public function testDeletePermissionSuccess(): void
    {
        $perm = Mockery::mock(SysPermissionDictModel::class);

        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('findOrNotFound')->with(5, '权限')->andReturn($perm);
        $model->shouldReceive('where->pluck->toArray')->andReturn([]);
        $model->shouldReceive('whereIn->delete')->andReturn(1);

        $this->assertTrue((new Delete($model))->handle(['id' => 5]));
    }

    public function testDeletePermissionNotFound(): void
    {
        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '权限不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Delete($model))->handle(['id' => 999]);
    }

    public function testDeletePermissionZeroId(): void
    {
        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '权限ID不能为空'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('权限ID不能为空');

        (new Delete($model))->handle(['id' => 0]);
    }

    // ==================== GetInfo ====================

    public function testGetInfoSuccess(): void
    {
        $perm = Mockery::mock(SysPermissionDictModel::class);
        $perm->shouldReceive('toArray')->andReturn(['id' => 1, 'perm_code' => 'test']);

        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '权限')->andReturn($perm);

        $result = (new GetInfo($model))->handle(1);

        $this->assertSame(1, $result['id']);
    }

    public function testGetInfoNotFound(): void
    {
        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '权限不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new GetInfo($model))->handle(999);
    }

    public function testGetInfoZeroId(): void
    {
        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(400, '权限ID不能为空'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('权限ID不能为空');

        (new GetInfo($model))->handle(0);
    }

    // ==================== 批量创建 / 批量更新 ====================

    public function testBatchCreateWritesEachItemIndependently(): void
    {
        // 批量接口会把 items 逐条转成 DTO，各条必须带自己的数据。
        // 曾经因为 DTO 数据存在共享的协程上下文里，3 条全部写成了最后一条。
        $captured = [];
        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('where->exists')->andReturn(false);
        $model->shouldReceive('create')->andReturnUsing(
            static function (array $data) use (&$captured) {
                $captured[] = $data;
                return Mockery::mock(stdClass::class);
            }
        );

        $items = [
            PermissionRequest::fromArray(['perm_code' => 'probe:a', 'perm_name' => '探针A']),
            PermissionRequest::fromArray(['perm_code' => 'probe:b', 'perm_name' => '探针B']),
            PermissionRequest::fromArray(['perm_code' => 'probe:c', 'perm_name' => '探针C']),
        ];

        $result = (new BatchCreate($model))->handle($items);

        $this->assertSame(3, $result['created_count']);
        $this->assertSame(['probe:a', 'probe:b', 'probe:c'], array_column($captured, 'perm_code'));
        $this->assertSame(['探针A', '探针B', '探针C'], array_column($captured, 'perm_name'));
    }

    public function testBatchCreateSkipsExistingCodes(): void
    {
        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('where->exists')->andReturn(true);
        $model->shouldReceive('create')->never();

        $result = (new BatchCreate($model))->handle([
            PermissionRequest::fromArray(['perm_code' => 'dup:a', 'perm_name' => '重复A']),
        ]);

        $this->assertSame(0, $result['created_count']);
        $this->assertSame(['dup:a'], $result['skipped_codes']);
    }

    public function testBatchCreateRejectsEmptyItems(): void
    {
        $model = Mockery::mock(SysPermissionDictModel::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('权限数据不能为空');

        (new BatchCreate($model))->handle([]);
    }

    public function testBatchUpdateAppliesEachItemToItsOwnRecord(): void
    {
        // 每条 item 必须更新到自己的 id 上，不能全部落到同一条记录
        $updated = [];

        $makeRecord = static function (int $id, string $code) use (&$updated): Mockery\MockInterface {
            $record = Mockery::mock(SysPermissionDictModel::class)->makePartial();
            $record->id = $id;
            $record->perm_code = $code;
            $record->shouldReceive('update')->once()->andReturnUsing(
                static function (array $data) use (&$updated, $id): bool {
                    $updated[$id] = $data;
                    return true;
                }
            );
            return $record;
        };

        $records = [
            217 => $makeRecord(217, 'probe:a'),
            218 => $makeRecord(218, 'probe:b'),
        ];

        $model = Mockery::mock(SysPermissionDictModel::class);
        $model->shouldReceive('find')->andReturnUsing(
            static fn (int $id) => $records[$id] ?? null
        );

        $result = (new BatchUpdate($model))->handle([
            PermissionRequest::fromArray(['id' => 217, 'perm_name' => '探针A改']),
            PermissionRequest::fromArray(['id' => 218, 'perm_name' => '探针B改']),
        ]);

        $this->assertSame(2, $result['updated_count']);
        $this->assertSame(['perm_name' => '探针A改'], $updated[217]);
        $this->assertSame(['perm_name' => '探针B改'], $updated[218]);
    }
}
