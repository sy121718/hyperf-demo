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

use App\Dto\Admin\AdminRequest;
use App\Dto\Attachment\AttachmentRequest;
use App\Dto\CronJob\CronJobRequest;
use App\Dto\Menu\MenuRequest;
use App\Dto\Notice\NoticeRequest;
use App\Dto\Permission\PermissionRequest;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * update 场景的主键必须能从路由参数取到.
 *
 * validated() 只返回 rules 里声明过的键，所以 DTO 若不声明 id，
 * 即便 validationData() 并入了路由参数，hydrate() 也取不到值。
 * 这里逐个模块验证：路由带的 id 能落到属性上，控制器不再需要手工赋值。
 *
 * @internal
 * @coversNothing
 */
class UpdateRouteIdTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        ResponseContext::set(new PsrResponse());
    }

    protected function tearDown(): void
    {
        RequestContext::set(new PsrRequest('GET', '/'));
        parent::tearDown();
    }

    /**
     * @param class-string $dtoClass
     */
    #[DataProvider('requestProvider')]
    public function testUpdateHydratesIdFromRouteParam(string $dtoClass): void
    {
        $dto = $this->makeRequest($dtoClass, 'update', [], ['id' => '42']);

        $dto->validateResolved();

        self::assertSame(42, $dto->id, "{$dtoClass} 的 update 场景应从路由取到 id");
    }

    /**
     * @param class-string $dtoClass
     */
    #[DataProvider('requestProvider')]
    public function testUpdateWithoutAnyIdFailsValidation(string $dtoClass): void
    {
        $dto = $this->makeRequest($dtoClass, 'update', [], []);

        $this->expectException(ValidationException::class);
        $dto->validateResolved();
    }

    /**
     * @param class-string $dtoClass
     */
    #[DataProvider('requestProvider')]
    public function testUpdateRejectsNonPositiveId(string $dtoClass): void
    {
        $dto = $this->makeRequest($dtoClass, 'update', [], ['id' => '0']);

        $this->expectException(ValidationException::class);
        $dto->validateResolved();
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function requestProvider(): array
    {
        return [
            'admin' => [AdminRequest::class],
            'attachment' => [AttachmentRequest::class],
            'cron-job' => [CronJobRequest::class],
            'menu' => [MenuRequest::class],
            'notice' => [NoticeRequest::class],
            'permission' => [PermissionRequest::class],
        ];
    }

    /**
     * 构造带路由上下文的 DTO.
     *
     * @param class-string $dtoClass
     * @param array<string, mixed> $body
     * @param array<string, mixed> $routeParams
     */
    private function makeRequest(string $dtoClass, string $action, array $body, array $routeParams): object
    {
        $psrRequest = new PsrRequest('PUT', '/api/' . $action);
        $psrRequest = $psrRequest->withParsedBody($body);
        $psrRequest = $psrRequest->withAttribute(
            Dispatched::class,
            new Dispatched([
                Dispatcher::FOUND,
                new Handler('App\Controller\StubController@' . $action, '/api/' . $action),
                $routeParams,
            ])
        );
        RequestContext::set($psrRequest);

        /** @var object $dto */
        $dto = container()->get($dtoClass);
        // 与 RequestValidationTest 一致：清掉解析缓存与校验器缓存，避免跨用例串数据
        $dto->clearStoredParsedData();
        Context::destroy(spl_object_hash($dto) . ':' . ValidatorInterface::class);

        return $dto;
    }
}
