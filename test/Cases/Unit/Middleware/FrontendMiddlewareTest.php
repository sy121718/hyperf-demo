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

use App\Middleware\FrontendMiddleware;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Server\Request;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpMessage\Uri\Uri;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Mockery;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
#[CoversNothing]
class FrontendMiddlewareTest extends TestCase
{
    private string $publicPath;

    protected function setUp(): void
    {
        $this->publicPath = sys_get_temp_dir() . '/hyperf-frontend-' . bin2hex(random_bytes(8));
        mkdir($this->publicPath . '/admin', 0700, true);
        file_put_contents($this->publicPath . '/index.html', '<html>前台</html>');
        file_put_contents($this->publicPath . '/admin/index.html', '<html>后台</html>');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        unlink($this->publicPath . '/index.html');
        unlink($this->publicPath . '/admin/index.html');
        rmdir($this->publicPath . '/admin');
        rmdir($this->publicPath);
        parent::tearDown();
    }

    public function testServesBothSitesAndHistoryRoutes(): void
    {
        foreach (['/' => '前台', '/login' => '前台', '/notice/12' => '前台', '/admin' => '后台', '/admin/login' => '后台', '/admin/system/admin' => '后台'] as $path => $site) {
            $html = '<html>' . $site . '</html>';
            $response = Mockery::mock(ResponseInterface::class);
            $response->shouldReceive('html')->once()->with($html)->andReturn(new Response());
            $handler = Mockery::mock(RequestHandlerInterface::class);
            $handler->shouldNotReceive('handle');

            $result = $this->middleware($response)->process(new Request('GET', new Uri($path)), $handler);

            self::assertSame(200, $result->getStatusCode());
            self::assertSame('no-cache', $result->getHeaderLine('Cache-Control'));
        }
    }

    public function testDoesNotTurnApiOrPrivateFilesIntoHtml(): void
    {
        foreach (['/api', '/api/auth/profile', '/api/unknown', '/dist/tools/build.php', '/dist/output/hyperf-demo', '/.env', '/admin/.env', '/assets/missing.js', '/admin/assets/missing.js', '/unknown.js'] as $path) {
            $response = Mockery::mock(ResponseInterface::class);
            $response->shouldNotReceive('html');
            $expected = new Response();
            $handler = Mockery::mock(RequestHandlerInterface::class);
            $handler->shouldReceive('handle')->once()->andReturn($expected);

            self::assertSame($expected, $this->middleware($response)->process(new Request('GET', new Uri($path)), $handler));
        }
    }

    public function testOnlyGetAndHeadCanServePages(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('html')->once()->with('')->andReturn(new Response());
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $expected = new Response();
        $handler->shouldReceive('handle')->once()->andReturn($expected);
        $middleware = $this->middleware($response);

        self::assertSame($expected, $middleware->process(new Request('POST', new Uri('/login')), $handler));
        self::assertSame(200, $middleware->process(new Request('HEAD', new Uri('/login')), $handler)->getStatusCode());
    }

    public function testMissingBuildFallsThroughToBackend(): void
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldNotReceive('html');
        $expected = new Response();
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')->once()->andReturn($expected);
        $config = Mockery::mock(ConfigInterface::class);
        $config->shouldReceive('get')->with('server.settings.document_root')->andReturn($this->publicPath . '/missing');

        self::assertSame($expected, (new FrontendMiddleware($response, $config))->process(new Request('GET', new Uri('/')), $handler));
    }

    private function middleware(ResponseInterface $response): FrontendMiddleware
    {
        $config = Mockery::mock(ConfigInterface::class);
        $config->shouldReceive('get')->with('server.settings.document_root')->andReturn($this->publicPath);

        return new FrontendMiddleware($response, $config);
    }
}
