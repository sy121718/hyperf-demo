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
use FastRoute\Dispatcher;
use Hyperf\Context\Context;
use Hyperf\Context\RequestContext;
use Hyperf\Context\ResponseContext;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\HttpMessage\Server\Request as PsrRequest;
use Hyperf\HttpMessage\Server\Response as PsrResponse;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * 覆盖 Request 层的路由相关行为：
 * 按 action 选规则、路由参数参与校验、校验通过后回填属性。
 *
 * @internal
 * @coversNothing
 */
class RequestValidationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // FormRequest::failedValidation() 会读 ResponseContext 生成 422 响应
        ResponseContext::set(new PsrResponse());
    }

    protected function tearDown(): void
    {
        RequestContext::set(new PsrRequest('GET', '/'));
        parent::tearDown();
    }

    public function testRulesAreChosenByRouteAction(): void
    {
        $dto = $this->makeRequest('assignToAdmin', []);

        $rules = $dto->rules();

        $this->assertArrayHasKey('admin_id', $rules);
        $this->assertArrayHasKey('role_ids', $rules);
        // 不同 action 的字段不应串场景
        $this->assertArrayNotHasKey('role_code', $rules);
    }

    public function testRequiredFieldMissingThrowsValidationException(): void
    {
        $dto = $this->makeRequest('assignToAdmin', ['admin_id' => 1]);

        $this->expectException(ValidationException::class);
        $dto->validateResolved();
    }

    public function testRouteParamSatisfiesRequiredRule(): void
    {
        // update 的主键在路由里，必须并进校验数据，否则 required 永远失败
        $dto = $this->makeRequest('update', ['role_name' => '运维'], ['id' => '9']);

        $dto->validateResolved();

        $this->assertSame(9, $dto->id);
        $this->assertSame('运维', $dto->role_name);
    }

    public function testRouteParamOverridesRequestParam(): void
    {
        // 主键由 URL 决定：请求体里伪造的 id 不能改变实际更新的目标行
        $dto = $this->makeRequest('update', ['id' => '7'], ['id' => '9']);

        $dto->validateResolved();

        $this->assertSame(9, $dto->id);
    }

    public function testValidationHydratesNestedArrayField(): void
    {
        $dto = $this->makeRequest('assignMenus', ['role_code' => 'ops', 'menu_ids' => [1, 2]]);

        $dto->validateResolved();

        $this->assertSame('ops', $dto->role_code);
        $this->assertSame([1, 2], $dto->menu_ids);
    }

    public function testInvalidNestedItemFailsValidation(): void
    {
        $dto = $this->makeRequest('assignMenus', ['role_code' => 'ops', 'menu_ids' => [0]]);

        $this->expectException(ValidationException::class);
        $dto->validateResolved();
    }

    public function testListActionUsesPaginationRules(): void
    {
        $dto = $this->makeRequest('list', ['page' => '2', 'limit' => '20']);

        $dto->validateResolved();

        $this->assertSame(2, $dto->page);
        $this->assertSame(20, $dto->limit);
        $this->assertTrue($dto->isListAction());
    }

    public function testListActionRejectsLimitAboveMax(): void
    {
        $dto = $this->makeRequest('list', ['limit' => '500']);

        $this->expectException(ValidationException::class);
        $dto->validateResolved();
    }

    /**
     * 构造带路由上下文的 DTO.
     *
     * @param array<string, mixed> $body
     * @param array<string, mixed> $routeParams
     */
    private function makeRequest(string $action, array $body, array $routeParams = []): RoleRequest
    {
        $psrRequest = new PsrRequest('POST', '/api/role/' . $action);
        $psrRequest = $psrRequest->withParsedBody($body);
        $psrRequest = $psrRequest->withAttribute(
            Dispatched::class,
            new Dispatched([
                Dispatcher::FOUND,
                new Handler('App\Controller\System\RoleController@' . $action, '/api/role/' . $action),
                $routeParams,
            ])
        );
        RequestContext::set($psrRequest);

        /** @var RoleRequest $dto */
        $dto = container()->get(RoleRequest::class);
        // Request 会把解析后的入参缓存进 Context，同一协程内多次构造必须清掉，否则复用上一个用例的数据
        $dto->clearStoredParsedData();
        // FormRequest 用 spl_object_hash 作缓存键，对象销毁后哈希会被复用，
        // 同一协程内连续构造会命中上一个用例的校验器，这里显式清掉
        Context::destroy(spl_object_hash($dto) . ':' . ValidatorInterface::class);

        return $dto;
    }
}
