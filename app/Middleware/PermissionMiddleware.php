<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Common\CacheKeys;
use App\Model\Permission\SysPermissionDictModel;
use Hyperf\Context\Context;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;

class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected HttpResponse $response,
        protected SysPermissionDictModel $permissionModel,
        protected CacheInterface $cache,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = Context::get('auth_user');
        if (!$user) {
            return $this->forbidden('用户未登录');
        }

        if ($user['status'] != 1) {
            return $this->forbidden('账户已被禁用');
        }

        if ($user['is_admin']) {
            return $handler->handle($request);
        }

        $apiPath = '/' . ltrim($request->getUri()->getPath(), '/');
        $httpMethod = $request->getMethod();

        $permCacheKey = CacheKeys::permApi($httpMethod, $apiPath);
        $permission = $this->cache->get($permCacheKey);

        if ($permission === null) {
            $permRecord = $this->permissionModel
                ->where('api_path', $apiPath)
                ->where('http_method', $httpMethod)
                ->where('status', 1)
                ->first();

            $permission = $permRecord ? [
                'id' => $permRecord->id,
                'perm_code' => $permRecord->perm_code,
                'is_public' => $permRecord->is_public,
            ] : false;

            $this->cache->set($permCacheKey, $permission, 600);
        }

        if ($permission === false) {
            return $handler->handle($request);
        }

        if ($permission['is_public']) {
            return $handler->handle($request);
        }

        $userId = (int) $user['id'];
        $permCode = $permission['perm_code'] ?? '';

        $userPermCacheKey = CacheKeys::permUser($userId, $permCode);
        $hasPermission = $this->cache->get($userPermCacheKey);

        if ($hasPermission === null) {
            $hasPermission = Db::table('sys_admin as a')
                ->join('sys_admin_role as ar', 'a.admin_code', '=', 'ar.admin_code')
                ->join('sys_role as r', 'ar.role_code', '=', 'r.role_code')
                ->where('a.id', $userId)
                ->where('r.status', 1)
                ->whereJsonContains('r.permission_codes', $permCode)
                ->exists();

            $this->cache->set($userPermCacheKey, $hasPermission, 300);
        }

        if (!$hasPermission) {
            return $this->forbidden('权限不足，无法访问该资源');
        }

        return $handler->handle($request);
    }

    private function forbidden(string $message): ResponseInterface
    {
        return $this->response->json([
            'success' => false,
            'code' => 403,
            'message' => $message,
            'data' => null,
        ]);
    }
}
