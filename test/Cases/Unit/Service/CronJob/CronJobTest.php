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

use App\Exception\BusinessException;
use App\Model\CronJob\SysCronJobModel;
use App\Service\CronJob\Create;
use App\Service\CronJob\Delete;
use App\Service\CronJob\Dto\CreateCronJobDto;
use App\Service\CronJob\Dto\UpdateCronJobDto;
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
        $dto = new CreateCronJobDto(
            jobName: '测试任务',
            cronExpression: '* * * * *',
            command: 'php artisan test',
        );

        $created = Mockery::mock(SysCronJobModel::class)->makePartial();
        $created->shouldReceive('fresh->toArray')->andReturn([
            'id' => 1,
            'job_name' => '测试任务',
            'cron_expression' => '* * * * *',
            'command' => 'php artisan test',
        ]);

        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('create')->once()->andReturn($created);

        $action = new Create($model);
        $result = $action->handle($dto);

        $this->assertEquals('测试任务', $result['job_name']);
    }

    public function testCreateEmptyName(): void
    {
        $dto = new CreateCronJobDto(
            jobName: '',
            cronExpression: '* * * * *',
            command: 'php artisan test',
        );

        $model = Mockery::mock(SysCronJobModel::class);
        $action = new Create($model);

        $this->expectException(BusinessException::class);
        $action->handle($dto);
    }

    public function testGetInfoSuccess(): void
    {
        $job = Mockery::mock(SysCronJobModel::class)->makePartial();
        $job->shouldReceive('toArray')->andReturn([
            'id' => 1,
            'job_name' => '测试任务',
        ]);

        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('find')->with(1)->andReturn($job);

        $action = new GetInfo($model);
        $result = $action->handle(1);

        $this->assertEquals('测试任务', $result['job_name']);
    }

    public function testGetInfoNotFound(): void
    {
        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);

        $action = new GetInfo($model);

        $this->expectException(BusinessException::class);
        $action->handle(999);
    }

    public function testUpdateSuccess(): void
    {
        $dto = new UpdateCronJobDto(id: 1, jobName: '更新任务');

        $job = Mockery::mock(SysCronJobModel::class)->makePartial();
        $job->shouldReceive('update')->once()->andReturn(true);
        $job->shouldReceive('fresh->toArray')->andReturn([
            'id' => 1,
            'job_name' => '更新任务',
        ]);

        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('find')->with(1)->andReturn($job);

        $action = new Update($model);
        $result = $action->handle($dto);

        $this->assertEquals('更新任务', $result['job_name']);
    }

    public function testDeleteSuccess(): void
    {
        $job = Mockery::mock(SysCronJobModel::class)->makePartial();
        $job->shouldReceive('delete')->once()->andReturn(true);

        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('find')->with(1)->andReturn($job);

        $action = new Delete($model);
        $result = $action->handle(1);

        $this->assertTrue($result);
    }

    public function testDeleteNotFound(): void
    {
        $model = Mockery::mock(SysCronJobModel::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);

        $action = new Delete($model);

        $this->expectException(BusinessException::class);
        $action->handle(999);
    }
}
