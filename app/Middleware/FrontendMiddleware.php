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

namespace App\Middleware;

use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FrontendMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly HttpResponse $response,
        private readonly ConfigInterface $config,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = rawurldecode($request->getUri()->getPath());

        // API、构建工具、隐藏文件及缺失资源不能被 SPA 首页掩盖。
        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true)
            || preg_match('~^/(?:api|assets|dist)(?:/|$)|^/admin/assets(?:/|$)|(?:^|/)\.~', $path)
            || (pathinfo($path, PATHINFO_EXTENSION) !== '' && ! in_array($path, ['/index.html', '/admin/index.html'], true))) {
            return $handler->handle($request);
        }

        $isAdmin = $path === '/admin' || str_starts_with($path, '/admin/');
        $file = $this->config->get('server.settings.document_root') . ($isAdmin ? '/admin/index.html' : '/index.html');
        if (! is_readable($file)) {
            return $handler->handle($request);
        }

        // 仅返回两个固定入口，避免将请求路径直接映射为任意磁盘文件。
        $html = $request->getMethod() === 'HEAD' ? '' : file_get_contents($file);

        return $this->response->html($html)->withHeader('Cache-Control', 'no-cache');
    }
}
