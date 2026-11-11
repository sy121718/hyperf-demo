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

namespace HyperfTest\Cases\Unit\Dto;

use App\Dto\Role\RoleRequest;
use Hyperf\Context\Context;
use Hyperf\Context\RequestContext;
use Hyperf\HttpMessage\Server\Request as PsrRequest;
use PHPUnit\Framework\TestCase;

/**
 * 覆盖 BaseRequestDto 的数据承载部分：
 * 类型转换、虚拟字段剔除、通用查询参数与分页计算。
 *
 * 路由上下文相关的行为（场景规则、路由参数合并）见 RequestValidationTest。
 *
 * @internal
 * @coversNothing
 */
class BaseRequestDtoTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // FormRequest 的 getAttribute() 会读 RequestContext，脱离 HTTP 上下文时需给个空请求
        RequestContext::set(new PsrRequest('GET', '/'));
    }

    public function testFromArrayCastsStringValuesToDeclaredTypes(): void
    {
        // 查询串/表单提交都是字符串，DTO 必须按属性声明类型转换
        $dto = RoleRequest::fromArray([
            'role_code' => 'ops',
            'sort_order' => '5',
            'status' => '0',
        ]);

        $this->assertSame('ops', $dto->role_code);
        $this->assertSame(5, $dto->sort_order);
        $this->assertSame(0, $dto->status);
    }

    public function testFromArrayKeepsUnsubmittedFieldsAsNull(): void
    {
        // 未提交的字段保持 null，写库时才能交给数据库默认值
        $dto = RoleRequest::fromArray(['role_code' => 'ops']);

        $this->assertNull($dto->role_type);
        $this->assertNull($dto->remark);
    }

    public function testToDbArrayExcludesVirtualFields(): void
    {
        $dto = RoleRequest::fromArray([
            'role_code' => 'ops',
            'role_ids' => [1, 2],
            'menu_ids' => [3],
            'admin_id' => 9,
            'ids' => [1],
        ]);
        $db = $dto->toDbArray();

        // 关联 ID 集合与主键不写进 sys_role
        $this->assertArrayNotHasKey('role_ids', $db);
        $this->assertArrayNotHasKey('menu_ids', $db);
        $this->assertArrayNotHasKey('admin_id', $db);
        $this->assertArrayNotHasKey('ids', $db);
        $this->assertArrayNotHasKey('id', $db);
        $this->assertSame('ops', $db['role_code']);
    }

    public function testToArrayExposesVirtualFieldsForActions(): void
    {
        // Action 需要拿到 role_ids 等关联字段，toArray 不做剔除
        $dto = RoleRequest::fromArray(['role_code' => 'ops', 'role_ids' => [1, 2]]);

        $this->assertSame([1, 2], $dto->toArray()['role_ids']);
    }

    public function testToDbArrayFilteredDropsNullValues(): void
    {
        // update 场景：未提交的字段不能写成 NULL 覆盖数据库默认值
        $dto = RoleRequest::fromArray(['role_name' => '运维']);
        $db = $dto->toDbArrayFiltered();

        $this->assertSame(['role_name' => '运维'], $db);
    }

    public function testToQueryArrayMergesPaginationFields(): void
    {
        $dto = RoleRequest::fromArray(['role_code' => 'ops']);
        $dto->page = 2;
        $dto->limit = 20;
        $query = $dto->toQueryArray();

        $this->assertSame(2, $query['page']);
        $this->assertSame(20, $query['limit']);
        $this->assertArrayNotHasKey('search', $query);
    }

    public function testPageNumberFallsBackToOne(): void
    {
        $dto = RoleRequest::fromArray([]);

        $this->assertSame(1, $dto->pageNumber());
    }

    public function testPageSizeDefaultsToTen(): void
    {
        $dto = RoleRequest::fromArray([]);

        $this->assertSame(10, $dto->pageSize());
    }

    public function testPageSizeIsCappedAtHundred(): void
    {
        // 防止调用方传 limit=10000 拖垮数据库
        $dto = RoleRequest::fromArray([]);
        $dto->limit = 10000;

        $this->assertSame(100, $dto->pageSize());
    }

    public function testPageSizeFallsBackWhenNonPositive(): void
    {
        $dto = RoleRequest::fromArray([]);
        $dto->limit = 0;

        $this->assertSame(10, $dto->pageSize());
    }

    public function testRulesWithoutRouteContextAreEmpty(): void
    {
        // fromArray 不依赖路由，拿不到 action 时返回空规则
        $dto = RoleRequest::fromArray(['role_code' => 'ops']);

        $this->assertSame([], $dto->rules());
    }

    public function testAuthorizeIsAlwaysTrue(): void
    {
        // 认证与鉴权由中间件负责，DTO 必须放行，否则会返回空的 403
        $dto = RoleRequest::fromArray([]);

        $this->assertTrue($dto->authorize());
    }

    public function testSingletonStoresDataInCoroutineContext(): void
    {
        // 容器按单例复用 DTO。若数据写在实例属性上，跨请求会残留、
        // 并发协程之间还会互相覆盖，所以单例的字段必须落到协程上下文。
        $dto = container()->get(RoleRequest::class);
        $dto->role_code = 'ops';

        $this->assertSame('ops', $dto->role_code);
        $this->assertSame(
            'ops',
            Context::get(RoleRequest::class . '.dto.role_code'),
            '单例的字段必须存进协程上下文，而不是留在对象属性上'
        );

        // 清掉上下文里的值，同一个对象就读不到了 —— 证明值没粘在实例上
        Context::destroy(RoleRequest::class . '.dto.role_code');
        $this->assertNull($dto->role_code);
    }

    public function testBatchInstancesKeepTheirOwnData(): void
    {
        // 批量场景一次构造多个同类型 DTO，它们必须各持自己的数据
        $items = array_map(
            static fn (array $item) => RoleRequest::fromArray($item),
            [
                ['role_code' => 'a', 'role_name' => '甲'],
                ['role_code' => 'b', 'role_name' => '乙'],
            ]
        );

        $this->assertSame('a', $items[0]->role_code);
        $this->assertSame('甲', $items[0]->role_name);
        $this->assertSame('b', $items[1]->role_code);
        $this->assertSame('乙', $items[1]->role_name);
    }
}
