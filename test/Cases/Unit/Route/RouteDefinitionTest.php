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

namespace HyperfTest\Cases\Unit\Route;

use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Testing\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * 路由定义自检：每条注册的路由都必须能落到真实的控制器方法上。
 *
 * 这类问题的表现是请求返回 500 而不是 404 —— 路由匹配成功、调用时才发现方法不存在。
 * 曾经 menu/assign-permissions、menu/remove-permission、role/remove-menu 三条路由
 * 指向从未实现过的方法，静默挂在线上很久。
 *
 * @internal
 * @coversNothing
 */
class RouteDefinitionTest extends TestCase
{
    public function testEveryRoutePointsToAnExistingControllerMethod(): void
    {
        $routes = $this->collectRoutes();

        // 防止路由注册链路失效时用例空跑通过
        $this->assertGreaterThan(80, count($routes), '路由数量异常偏少，路由注册可能没生效');

        $missing = [];
        foreach ($routes as [$method, $path, $callback]) {
            $error = $this->validateCallback($callback);
            if ($error !== null) {
                $missing[] = sprintf('%s %s -> %s (%s)', $method, $path, $callback, $error);
            }
        }

        $this->assertSame([], $missing, "以下路由指向了不存在的控制器或方法：\n" . implode("\n", $missing));
    }

    public function testNoDuplicateRouteRegistration(): void
    {
        // 同一 HTTP 方法 + 路径注册两次时，后注册的会覆盖前者且不报错
        $seen = [];
        $duplicates = [];

        foreach ($this->collectRoutes() as [$method, $path, $callback]) {
            $key = $method . ' ' . $path;
            if (isset($seen[$key])) {
                $duplicates[] = $key . '（重复注册）';
            }
            $seen[$key] = $callback;
        }

        $this->assertSame([], $duplicates, "重复注册的路由：\n" . implode("\n", $duplicates));
    }

    /**
     * 取出所有路由，统一成 [方法, 路径, 回调] 三元组.
     *
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    private function collectRoutes(): array
    {
        $data = (new DispatcherFactory())->getRouter('http')->getData();
        $result = [];

        // FastRoute 数据形如 data[chunkIndex][HTTP_METHOD]：
        // - 静态 chunk：'路径' => Handler 对象
        // - 变量 chunk：数字下标 => ['regex' => ..., 'routeMap' => [[Handler, vars], ...]]
        foreach ($data as $chunks) {
            if (! is_array($chunks)) {
                continue;
            }

            foreach ($chunks as $httpMethod => $chunk) {
                if (! is_array($chunk)) {
                    continue;
                }

                foreach ($chunk as $item) {
                    // 静态路由
                    if ($item instanceof Handler) {
                        $this->appendRoute($result, (string) $httpMethod, $item);
                        continue;
                    }

                    // 变量路由：handler 在 routeMap 每一项的第一位
                    foreach ((array) ($item['routeMap'] ?? []) as $entry) {
                        $handler = $entry[0] ?? null;
                        if ($handler instanceof Handler) {
                            $this->appendRoute($result, (string) $httpMethod, $handler);
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 把一条 handler 记录追加到结果集.
     *
     * @param array<int, array{0: string, 1: string, 2: string}> $result
     */
    private function appendRoute(array &$result, string $httpMethod, Handler $handler): void
    {
        $callback = $handler->callback;
        if (! is_string($callback)) {
            return;
        }

        $result[] = [$httpMethod, $handler->route, $callback];
    }

    /**
     * 校验回调是否可用，可用返回 null，否则返回原因.
     */
    private function validateCallback(string $callback): ?string
    {
        if (! str_contains($callback, '@')) {
            return null; // 闭包等非 class@method 形式，不在此校验范围
        }

        [$class, $method] = explode('@', $callback, 2);

        if (! class_exists($class)) {
            return '控制器不存在';
        }

        if (! method_exists($class, $method)) {
            return '方法不存在';
        }

        $reflection = new ReflectionMethod($class, $method);
        if (! $reflection->isPublic()) {
            return '方法不是 public';
        }

        $controller = new ReflectionClass($class);
        if (! $controller->isInstantiable()) {
            return '控制器不可实例化';
        }

        return null;
    }
}
