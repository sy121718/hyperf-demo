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

namespace HyperfTest\Cases\Unit\Service\Admin\Manage;

use App\Model\Admin\SysAdminModel;
use App\Service\Admin\Manage\GetList;
use Hyperf\Context\Context;
use Hyperf\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;

/**
 * @internal
 * @coversNothing
 */
class GetListTest extends TestCase
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

    public function testGetListDefaultPagination(): void
    {
        $model = $this->mockModel();
        $model->shouldReceive('paginate')
            ->with(10, ['*'], 'page', 1)
            ->once()
            ->andReturn((object) ['total' => 0]);

        $action = new GetList($model);
        $result = $action->handle([]);

        $this->assertNotNull($result);
    }

    public function testGetListCustomPagination(): void
    {
        $model = $this->mockModel();
        $model->shouldReceive('paginate')
            ->with(20, ['*'], 'page', 3)
            ->once()
            ->andReturn((object) ['total' => 100]);

        $action = new GetList($model);
        $action->handle(['page' => 3, 'limit' => 20]);

        $this->addToAssertionCount(1);
    }

    public function testGetListCallsFilterWithData(): void
    {
        $model = $this->mockModel();
        $model->shouldReceive('filter')
            ->with(Mockery::on(fn ($d) => ($d['status'] ?? null) === '1'))
            ->once()
            ->andReturnSelf();

        $action = new GetList($model);
        $action->handle(['status' => '1']);

        $this->addToAssertionCount(1);
    }

    public function testGetListCallsSortWithData(): void
    {
        $model = $this->mockModel();
        $model->shouldReceive('sort')
            ->once()
            ->andReturnSelf();

        $action = new GetList($model);
        $action->handle(['sort' => ['field' => 'id', 'direction' => 'desc']]);

        $this->addToAssertionCount(1);
    }

    public function testGetListWithSearchKeyword(): void
    {
        $model = $this->mockModel();
        $model->shouldReceive('when')
            ->with('test', Mockery::type('Closure'))
            ->once()
            ->andReturnSelf();

        $action = new GetList($model);
        $action->handle(['search' => 'test']);

        $this->addToAssertionCount(1);
    }

    public function testGetListWithoutSearchDoesNotFilter(): void
    {
        $model = $this->mockModel();
        $model->shouldReceive('when')
            ->with(null, Mockery::type('Closure'))
            ->once()
            ->andReturnSelf();

        $action = new GetList($model);
        $action->handle([]);

        $this->addToAssertionCount(1);
    }

    private function mockModel(): MockInterface
    {
        $model = Mockery::mock(SysAdminModel::class);
        $model->shouldReceive('filter')->byDefault()->andReturnSelf();
        $model->shouldReceive('when')->byDefault()->andReturnSelf();
        $model->shouldReceive('sort')->byDefault()->andReturnSelf();
        $model->shouldReceive('getSearchableFields')->byDefault()->andReturn(['username', 'name', 'email']);
        $model->shouldReceive('paginate')->byDefault()->andReturn(
            (object) ['items' => [], 'total' => 0, 'per_page' => 10, 'current_page' => 1]
        );
        return $model;
    }
}
