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
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = Context::get('auth_user');
        if (! $user) {
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
            // 获取用户所有角色的 permission_codes
            $allCodes = [];
            $roles = Db::table('sys_admin_role as ar')
                ->join('sys_role as r', 'ar.role_code', '=', 'r.role_code')
                ->where('ar.admin_code', $user['admin_code'])
                ->where('r.status', 1)
                ->get(['r.permission_codes']);

            foreach ($roles as $role) {
                $codes = $role->permission_codes ?? [];
                $allCodes = array_merge($allCodes, is_array($codes) ? $codes : []);
            }
            $allCodes = array_unique($allCodes);

            $hasPermission = in_array($permCode, $allCodes);

            $this->cache->set($userPermCacheKey, $hasPermission, 300);
        }

        if (! $hasPermission) {
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
