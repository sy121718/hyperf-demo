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
}
