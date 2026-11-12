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

namespace HyperfTest\Cases\Unit\Service\Ops\LoginLog;

use App\Dto\Log\LogRequest;
use App\Model\Admin\SysLoginLogModel;
use App\Service\Ops\LoginLog\GetMyList;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;

/**
 * 我的登录日志：status 是 0/1 两态，0 必须能作为筛选值生效。
 * @internal
 * @coversNothing
 */
class GetMyListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Context::set('auth_user', ['id' => 7, 'username' => 'u7', 'is_admin' => 0]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    public function testStatusZeroFiltersFailedLogins(): void
    {
        $applied = [];
        // 模拟 Builder 的 when：条件为真时执行回调，否则跳过
        $builder = Mockery::mock();
        $builder->shouldReceive('where')->with('user_id', 7)->andReturnSelf();
        $builder->shouldReceive('where')->andReturnUsing(function ($field, $value) use (&$applied, $builder) {
            $applied[] = [$field, $value];
            return $builder;
        });
        $builder->shouldReceive('when')->andReturnUsing(
            fn ($condition, $callback) => $condition ? $callback($builder) : $builder
        );
        $builder->shouldReceive('orderBy')->andReturnSelf();
        $builder->shouldReceive('paginate')->andReturn([]);

        $model = Mockery::mock(SysLoginLogModel::class);
        $model->shouldReceive('where')->andReturn($builder);

        (new GetMyList($model))->handle(LogRequest::fromArray(['status' => 0]));

        $this->assertContains(['status', 0], $applied);
    }

    public function testNullStatusDoesNotAddCondition(): void
    {
        $applied = [];
        $builder = Mockery::mock();
        $builder->shouldReceive('where')->with('user_id', 7)->andReturnSelf();
        $builder->shouldReceive('where')->andReturnUsing(function ($field, $value) use (&$applied, $builder) {
            $applied[] = [$field, $value];
            return $builder;
        });
        $builder->shouldReceive('when')->andReturnUsing(
            fn ($condition, $callback) => $condition ? $callback($builder) : $builder
        );
        $builder->shouldReceive('orderBy')->andReturnSelf();
        $builder->shouldReceive('paginate')->andReturn([]);

        $model = Mockery::mock(SysLoginLogModel::class);
        $model->shouldReceive('where')->andReturn($builder);

        (new GetMyList($model))->handle(LogRequest::fromArray([]));

        $this->assertNotContains('status', array_column($applied, 0));
    }
}
