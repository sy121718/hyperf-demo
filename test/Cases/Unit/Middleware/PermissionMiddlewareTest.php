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

namespace HyperfTest\Cases\Unit\Middleware;

use App\Middleware\PermissionMiddleware;
use App\Model\Rbac\SysPermissionDictModel;
use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Hyperf\HttpMessage\Server\Request;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpMessage\Uri\Uri;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;

/**
 * 权限中间件的路径解析与准入判定.
 * @internal
 */
#[CoversNothing]
class PermissionMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Context::destroy('auth_user');
        parent::tearDown();
    }

    /**
     * 带参路由必须解析成字典里的占位符形式，否则等值匹配落空即被放行.
     */
    #[DataProvider('apiPathProvider')]
    public function testResolvesRoutePatternInsteadOfRawUri(string $method, string $uri, string $expectedPath): void
    {
        $this->assertSame($expectedPath, $this->resolvePath($method, $uri));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function apiPathProvider(): array
    {
        return [
            '带 id 的详情接口' => ['GET', '/api/admin/info/1', '/api/admin/info/{id}'],
            '带 id 的更新接口' => ['PUT', '/api/admin/update/42', '/api/admin/update/{id}'],
            '带 dict_id 的字典项接口' => ['GET', '/api/dict/items/7', '/api/dict/items/{dict_id}'],
        ];
    }

    public function testNoParamRouteStaysUnchanged(): void
    {
        $this->assertSame('/api/admin/list', $this->resolvePath('GET', '/api/admin/list'));
    }

    /**
     * 无权限的普通管理员访问带参写接口必须 403，不能因为路径对不上而放行.
     *
     * 这条锁定的就是漏洞本身：修复前 resolveApiPath() 拿原始 URI 去等值匹配，
     * 带参接口查不到权限码，中间件直接放行，测试会拿到 200。
     */
    public function testDeniesUserWithoutPermissionOnParamRoute(): void
    {
        $response = $this->runMiddleware('DELETE', '/api/admin/delete/9', ['admin:list']);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAllowsUserHoldingThePermission(): void
    {
        $response = $this->runMiddleware('DELETE', '/api/admin/delete/9', ['admin:delete']);

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * 超管不看权限码，直接放行.
     */
    public function testSuperAdminBypassesPermissionCheck(): void
    {
        $response = $this->runMiddleware('DELETE', '/api/admin/delete/9', [], isAdmin: 1);

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * 取中间件真正参与匹配的路径.
     */
    private function resolvePath(string $method, string $uri): string
    {
        $ref = new ReflectionMethod(PermissionMiddleware::class, 'resolveApiPath');

        return $ref->invoke($this->makeMiddleware([]), $this->makeRequest($method, $uri));
    }

    /**
     * @param array<int, string> $permCodes 该用户持有的权限码
     */
    private function runMiddleware(string $method, string $uri, array $permCodes, int $isAdmin = 0): PsrResponseInterface
    {
        Context::set('auth_user', [
            'id' => 2,
            'admin_code' => 'plain_admin',
            'status' => 1,
            'is_admin' => $isAdmin,
        ]);

        /** @var MockInterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')->andReturn(new Response());

        return $this->makeMiddleware($permCodes)->process($this->makeRequest($method, $uri), $handler);
    }

    /**
     * @param array<int, string> $permCodes
     */
    private function makeMiddleware(array $permCodes): PermissionMiddleware
    {
        /** @var MockInterface|ResponseInterface $httpResponse */
        $httpResponse = Mockery::mock(ResponseInterface::class);
        $httpResponse->shouldReceive('json')->andReturnUsing(
            static fn (array $body) => (new Response())
                ->withStatus(403)
                ->withBody(new SwooleStream((string) json_encode($body, JSON_UNESCAPED_UNICODE)))
        );

        // 缓存全部命中，避免真实查库。
        //
        // perm:api:{METHOD}:{md5(路径)} 里的 md5 不可逆，所以这里按「字典里真实存在的
        // api_path」预先算好映射：只有占位符形式（/api/admin/delete/{id}）能查到
        // admin:delete，原始 URI（/api/admin/delete/9）查不到。这正好复现漏洞链路：
        // 路径对不上 → getPermCode() 返回 null → 中间件放行。
        $dictPath = '/api/admin/delete/{id}';
        $apiCodeMap = [
            'DELETE:' . md5($dictPath) => 'admin:delete',
        ];

        /** @var CacheInterface|MockInterface $cache */
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('get')->andReturnUsing(
            static function (string $key) use ($permCodes, $apiCodeMap) {
                if (str_starts_with($key, 'perm:codes:')) {
                    return $permCodes;
                }
                if (str_starts_with($key, 'perm:api:PUBLIC:')) {
                    return false;
                }
                if (str_starts_with($key, 'perm:api:')) {
                    return $apiCodeMap[substr($key, strlen('perm:api:'))] ?? '';
                }

                return null;
            }
        );
        $cache->shouldReceive('set')->andReturn(true);

        /** @var MockInterface|SysPermissionDictModel $permissionModel */
        $permissionModel = Mockery::mock(SysPermissionDictModel::class);

        return new PermissionMiddleware($httpResponse, $permissionModel, $cache);
    }

    private function makeRequest(string $method, string $uri): Request
    {
        $factory = ApplicationContext::getContainer()->get(DispatcherFactory::class);
        $dispatched = new Dispatched($factory->getDispatcher('http')->dispatch($method, $uri), 'http');

        return (new Request($method, new Uri($uri)))
            ->withAttribute(Dispatched::class, $dispatched);
    }
}
