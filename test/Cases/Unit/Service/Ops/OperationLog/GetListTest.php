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

namespace HyperfTest\Cases\Unit\Service\Ops\OperationLog;

use App\Dto\Log\LogRequest;
use App\Model\Admin\SysOperationLogModel;
use App\Service\Ops\OperationLog\GetList;
use Hyperf\Testing\TestCase;
use Mockery;

/**
 * 操作日志：result 由 success/failed 映射成 1/0 后，不能再交给通用 filter
 * 按字符串再比较一次，否则「成功」永远筛不到数据。
 * @internal
 * @coversNothing
 */
class GetListTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testFilterNoLongerReceivesRawResult(): void
    {
        $recorder = new class {
            /** @var array<int, array{0: string, 1: mixed}> */
            public array $whereArgs = [];

            public ?array $filterArgs = null;

            public function where(string $field, mixed $value): static
            {
                $this->whereArgs[] = [$field, $value];
                return $this;
            }

            public function when(mixed $condition, callable $callback): static
            {
                if ($condition) {
                    $callback($this);
                }
                return $this;
            }

            public function filter(array $data): static
            {
                $this->filterArgs = $data;
                return $this;
            }

            public function sort(array $data): static
            {
                return $this;
            }

            public function paginate(): array
            {
                return [];
            }
        };

        $model = Mockery::mock(SysOperationLogModel::class);
        $model->shouldReceive('when')->andReturnUsing(
            fn ($condition, $callback) => $condition ? $callback($recorder) : $recorder
        );

        (new GetList($model))->handle(LogRequest::fromArray(['result' => 'success']));

        // 映射后的数值条件只应出现在 where 里
        $this->assertContains(['result', 1], $recorder->whereArgs);
        // filter 入参里不应再带原始字符串 result
        $this->assertArrayNotHasKey('result', $recorder->filterArgs ?? []);
    }
}
