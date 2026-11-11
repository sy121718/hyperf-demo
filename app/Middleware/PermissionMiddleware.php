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
use App\Model\Rbac\SysPermissionDictModel;
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

        // 获取接口所需的权限码
        $permCode = $this->getPermCode($httpMethod, $apiPath);
        if (! $permCode) {
            return $handler->handle($request);
        }

        // 公开权限直接放行
        if ($this->isPublicPerm($permCode)) {
            return $handler->handle($request);
        }

        // 检查用户权限码列表
        if (! $this->hasPermission($user['admin_code'], $permCode)) {
            return $this->forbidden('权限不足，无法访问该资源');
        }

        return $handler->handle($request);
    }

    private function getPermCode(string $httpMethod, string $apiPath): ?string
    {
        $cacheKey = CacheKeys::permApi($httpMethod, $apiPath);
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return $cached ?: null;
        }

        $perm = $this->permissionModel
            ->where('api_path', $apiPath)
            ->where('http_method', $httpMethod)
            ->where('status', 1)
            ->first(['perm_code', 'is_public']);

        $permCode = $perm?->perm_code;
        $this->cache->set($cacheKey, $permCode ?: '', CacheKeys::TTL_MEDIUM);

        return $permCode ?: null;
    }

    private function isPublicPerm(string $permCode): bool
    {
        $cacheKey = CacheKeys::permApi('PUBLIC', $permCode);
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return (bool) $cached;
        }

        $isPublic = (bool) $this->permissionModel
            ->where('perm_code', $permCode)
            ->where('is_public', 1)
            ->exists();

        $this->cache->set($cacheKey, $isPublic, CacheKeys::TTL_MEDIUM);

        return $isPublic;
    }

    private function hasPermission(string $adminCode, string $permCode): bool
    {
        $cacheKey = CacheKeys::permCodes($adminCode);
        $codes = $this->cache->get($cacheKey);

        if ($codes === null) {
            $codes = $this->loadUserPermCodes($adminCode);
            $this->cache->set($cacheKey, $codes, CacheKeys::TTL_MEDIUM);
        }

        return in_array($permCode, $codes, true);
    }

    private function loadUserPermCodes(string $adminCode): array
    {
        $allCodes = [];

        $roles = Db::table('sys_admin_role as ar')
            ->join('sys_role as r', 'ar.role_code', '=', 'r.role_code')
            ->where('ar.admin_code', $adminCode)
            ->where('r.status', 1)
            ->get(['r.permission_codes']);

        foreach ($roles as $role) {
            // Db::table 不走模型 casts，JSON 字段返回字符串，需手动解码
            $codes = $role->permission_codes ?? [];
            if (is_string($codes)) {
                $codes = json_decode($codes, true) ?: [];
            }
            if (is_array($codes)) {
                $allCodes = array_merge($allCodes, $codes);
            }
        }

        return array_unique($allCodes);
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
