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

namespace HyperfTest\Cases\Unit\Service\CronJob;

use App\Dto\CronJob\CronJobRequest;
use App\Exception\BusinessException;
use App\Model\CronJob\SysCronJobModel;
use App\Service\CronJob\Create;
use App\Service\CronJob\Delete;
use App\Service\CronJob\GetInfo;
use App\Service\CronJob\Update;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class CronJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCreateSuccess(): void
    {
        $request = CronJobRequest::fromArray([
            'job_name' => '测试任务',
            'cron_expression' => '* * * * *',
            'command' => 'php artisan test',
        ]);

        $created = Mockery::mock(SysCronJobModel::class)->makePartial();
        $created->shouldReceive('fresh->toArray')->andReturn([
            'id' => 1,
            'job_name' => '测试任务',
            'cron_expression' => '* * * * *',
            'command' => 'php artisan test',
        ]);

        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('create')->once()->andReturn($created);

        $result = (new Create($model))->handle($request);

        $this->assertEquals('测试任务', $result['job_name']);
    }

    public function testCreateFillsDefaults(): void
    {
        $request = CronJobRequest::fromArray([
            'job_name' => '测试任务',
            'cron_expression' => '* * * * *',
            'command' => 'echo 1',
        ]);

        $captured = null;
        $created = Mockery::mock(SysCronJobModel::class)->makePartial();
        $created->shouldReceive('fresh->toArray')->andReturn(['id' => 1]);

        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('create')->once()->withArgs(function (array $data) use (&$captured) {
            $captured = $data;
            return true;
        })->andReturn($created);

        (new Create($model))->handle($request);

        $this->assertSame('default', $captured['job_category']);
        $this->assertSame(0, $captured['sort_order']);
    }

    public function testGetInfoSuccess(): void
    {
        $job = Mockery::mock(SysCronJobModel::class)->makePartial();
        $job->shouldReceive('toArray')->andReturn([
            'id' => 1,
            'job_name' => '测试任务',
        ]);

        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '任务')->andReturn($job);

        $result = (new GetInfo($model))->handle(1);

        $this->assertEquals('测试任务', $result['job_name']);
    }

    public function testGetInfoNotFound(): void
    {
        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '任务不存在'));

        $this->expectException(BusinessException::class);
        (new GetInfo($model))->handle(999);
    }

    public function testUpdateSuccess(): void
    {
        $request = CronJobRequest::fromArray(['id' => 1, 'job_name' => '更新任务']);

        $job = Mockery::mock(SysCronJobModel::class)->makePartial();
        $job->shouldReceive('update')->once()->andReturn(true);
        $job->shouldReceive('fresh->toArray')->andReturn([
            'id' => 1,
            'job_name' => '更新任务',
        ]);

        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '任务')->andReturn($job);

        $result = (new Update($model))->handle($request);

        $this->assertEquals('更新任务', $result['job_name']);
    }

    public function testUpdateNotFound(): void
    {
        $request = CronJobRequest::fromArray(['id' => 999, 'job_name' => 'x']);

        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '任务不存在'));

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(404);
        (new Update($model))->handle($request);
    }

    public function testDeleteSuccess(): void
    {
        $job = Mockery::mock(SysCronJobModel::class)->makePartial();
        $job->shouldReceive('delete')->once()->andReturn(true);

        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('findOrNotFound')->with(1, '任务')->andReturn($job);

        $this->assertTrue((new Delete($model))->handle(1));
    }

    public function testDeleteNotFound(): void
    {
        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('findOrNotFound')
            ->andThrow(new BusinessException(404, '任务不存在'));

        $this->expectException(BusinessException::class);
        (new Delete($model))->handle(999);
    }
}
