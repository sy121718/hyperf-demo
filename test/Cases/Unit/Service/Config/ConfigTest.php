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

namespace HyperfTest\Cases\Unit\Service\Config;

use App\Dto\Config\ConfigRequest;
use App\Exception\BusinessException;
use App\Model\System\SysConfigModel;
use App\Service\Config\Create;
use App\Service\Config\Delete;
use App\Service\Config\Get;
use App\Service\Config\GetInfo;
use Hyperf\Context\Context;
use Hyperf\Database\Model\Builder;
use Hyperf\Testing\TestCase;
use Mockery;
use Psr\SimpleCache\CacheInterface;
use stdClass;

/**
 * @internal
 * @coversNothing
 */
class ConfigTest extends TestCase
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
        $config = Mockery::mock(stdClass::class);
        $config->shouldReceive('toArray')->andReturn(['id' => 1, 'group_key' => 'site']);

        $model = Mockery::mock(SysConfigModel::class);
        $model->shouldReceive('where->exists')->andReturn(false);
        $model->shouldReceive('create')->once()->andReturn($config);

        $result = (new Create($model))->handle(ConfigRequest::fromArray([
            'group_key' => 'site', 'group_name' => '站点配置',
        ]));

        $this->assertArrayHasKey('id', $result);
    }

    public function testCreateDuplicateKey(): void
    {
        $model = Mockery::mock(SysConfigModel::class);
        $model->shouldReceive('where->exists')->andReturn(true);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('配置分组键已存在');

        (new Create($model))->handle(ConfigRequest::fromArray(['group_key' => 'dup', 'group_name' => 'test']));
    }

    public function testCreateFallsBackToDefaults(): void
    {
        // 未提交 config_data / status 时补齐默认值，避免写库时显式写入 NULL
        $config = Mockery::mock(stdClass::class);
        $config->shouldReceive('toArray')->andReturn(['id' => 1]);

        $model = Mockery::mock(SysConfigModel::class);
        $model->shouldReceive('where->exists')->andReturn(false);
        $model->shouldReceive('create')->once()->with([
            'group_key' => 'site',
            'group_name' => '站点配置',
            'config_data' => [],
            'status' => 1,
        ])->andReturn($config);

        (new Create($model))->handle(ConfigRequest::fromArray([
            'group_key' => 'site', 'group_name' => '站点配置',
        ]));
    }

    public function testGetInfoSuccess(): void
    {
        $config = Mockery::mock(SysConfigModel::class);
        $config->shouldReceive('toArray')->andReturn(['id' => 1, 'group_key' => 'test']);

        $model = Mockery::mock(SysConfigModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '配置')->andReturn($config);

        $result = (new GetInfo($model))->handle(1);

        $this->assertSame(1, $result['id']);
    }

    public function testGetInfoNotFound(): void
    {
        $model = Mockery::mock(SysConfigModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '配置不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new GetInfo($model))->handle(999);
    }

    public function testDeleteSuccess(): void
    {
        $config = Mockery::mock(SysConfigModel::class);
        $config->shouldReceive('getAttribute')->with('group_key')->andReturn('test');
        $config->shouldReceive('delete')->once()->andReturn(true);

        $model = Mockery::mock(SysConfigModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '配置')->andReturn($config);

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('delete')->with('config:test')->once();

        $result = (new Delete($model, $cache))->handle(1);

        $this->assertTrue($result);
    }

    public function testDeleteNotFound(): void
    {
        $model = Mockery::mock(SysConfigModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '配置不存在'));

        $cache = Mockery::mock(CacheInterface::class);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);

        (new Delete($model, $cache))->handle(999);
    }

    public function testGetWithCache(): void
    {
        $model = Mockery::mock(SysConfigModel::class);

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->with('config:site')->andReturn([
            'config_data' => ['title' => 'My Site'],
        ]);

        $result = (new Get($model, $cache))->handle('site', 'title');

        $this->assertSame('My Site', $result);
    }

    public function testGetCacheMissHitsDb(): void
    {
        $record = Mockery::mock(stdClass::class);
        $record->shouldReceive('toArray')->andReturn([
            'group_key' => 'site',
            'config_data' => ['name' => 'Test'],
        ]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('status', 1)->andReturnSelf();
        $builder->shouldReceive('first')->andReturn($record);

        $model = Mockery::mock(SysConfigModel::class);
        $model->shouldReceive('where')->with('group_key', 'site')->andReturn($builder);

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->with('config:site')->andReturn(null);
        $cache->shouldReceive('set')->once();

        $result = (new Get($model, $cache))->handle('site');

        $this->assertSame(['name' => 'Test'], $result);
    }

    public function testGetReturnsDefaultOnMiss(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('status', 1)->andReturnSelf();
        $builder->shouldReceive('first')->andReturn(null);

        $model = Mockery::mock(SysConfigModel::class);
        $model->shouldReceive('where')->with('group_key', 'missing')->andReturn($builder);

        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->andReturn(null);

        $result = (new Get($model, $cache))->handle('missing', null, 'fallback');

        $this->assertSame('fallback', $result);
    }
}
