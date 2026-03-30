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
use App\Model\Admin\SysAdminModel;
use App\Model\Admin\SysAdminSessionsModel;
use DateTimeImmutable;
use DateTimeZone;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Hyperf\Context\Context;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;
use UnexpectedValueException;

use function Hyperf\Support\env;

class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected HttpResponse $response,
        protected SysAdminModel $adminModel,
        protected SysAdminSessionsModel $sessionModel,
        protected CacheInterface $cache,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $token = $this->getTokenFromRequest($request);
            if (! $token) {
                return $this->unauthorized('Token未提供', 'TOKEN_MISSING');
            }

            $secret = (string) env('JWT_SECRET', '');
            $algo = (string) env('JWT_ALGO', 'HS256');
            $payload = JWT::decode($token, new Key($secret, $algo));
            $adminId = (int) $payload->sub;

            $cacheKey = CacheKeys::authSession($token);
            $cached = $this->cache->get($cacheKey);

            if ($cached) {
                $admin = (object) $cached['admin'];
                $sessionStatus = $cached['session_status'];
                $sessionId = $cached['session_id'];
            } else {
                $admin = $this->adminModel->find($adminId);
                if (! $admin) {
                    return $this->unauthorized('用户不存在', 'USER_NOT_FOUND');
                }

                $session = $this->sessionModel
                    ->where('jwt_token', $token)
                    ->where('admin_id', $admin->id)
                    ->first();

                if (! $session) {
                    return $this->unauthorized('会话不存在，请重新登录', 'SESSION_NOT_FOUND');
                }

                $sessionStatus = $session->status;
                $sessionId = $session->id;

                $this->cache->set($cacheKey, [
                    'admin' => [
                        'id' => $admin->id,
                        'username' => $admin->username,
                        'name' => $admin->name ?? $admin->username,
                        'email' => $admin->email,
                        'is_admin' => $admin->is_admin,
                        'status' => $admin->status,
                    ],
                    'session_status' => $sessionStatus,
                    'session_id' => $sessionId,
                ], 300);
            }

            if ($sessionStatus != 1) {
                $this->cache->delete($cacheKey);
                return $this->unauthorized('会话已失效，请重新登录', 'SESSION_INACTIVE');
            }

            $adminArr = $cached ? $cached['admin'] : [
                'id' => $admin->id,
                'username' => $admin->username,
                'name' => $admin->name ?? $admin->username,
                'email' => $admin->email,
                'is_admin' => $admin->is_admin,
                'status' => $admin->status,
            ];

            if (($adminArr['status'] ?? 0) != 1) {
                $this->cache->delete($cacheKey);
                return $this->unauthorized('账号已被禁用', 'USER_STATUS_INVALID');
            }

            $this->sessionModel->where('id', $sessionId)
                ->update(['last_active_time' => millisecond()]);

            Context::set('auth_user', $adminArr);
            Context::set('auth_token', $token);
        } catch (ExpiredException) {
            return $this->unauthorized('Token已过期', 'TOKEN_EXPIRED');
        } catch (UnexpectedValueException) {
            return $this->unauthorized('Token无效', 'TOKEN_INVALID');
        } catch (Throwable) {
            return $this->unauthorized('认证失败', 'AUTH_ERROR');
        }

        $response = $handler->handle($request);

        return $this->maybeRefreshToken($response, $payload, $token, $adminArr, $sessionId, $secret, $algo);
    }

    /**
     * 滑动过期：token 剩余时间 < REFRESH_WINDOW 时自动签发新 token.
     */
    private function maybeRefreshToken(
        ResponseInterface $response,
        object $payload,
        string $oldToken,
        array $adminArr,
        int $sessionId,
        string $secret,
        string $algo,
    ): ResponseInterface {
        $refreshWindow = (int) env('JWT_REFRESH_WINDOW', 1800);
        $remaining = ($payload->exp ?? 0) - time();

        if ($remaining >= $refreshWindow || $remaining <= 0) {
            return $response;
        }

        $ttl = (int) env('JWT_TTL', 7200);
        $now = time();
        $newPayload = [
            'sub' => $payload->sub,
            'iat' => $now,
            'exp' => $now + $ttl,
            'username' => $payload->username ?? '',
        ];
        $newToken = JWT::encode($newPayload, $secret, $algo);

        $expiresTime = (new DateTimeImmutable("+{$ttl} seconds", new DateTimeZone('UTC')))
            ->format('Y-m-d H:i:s.v');

        $this->sessionModel->where('id', $sessionId)->update([
            'jwt_token' => $newToken,
            'expires_time' => $expiresTime,
        ]);

        $oldCacheKey = CacheKeys::authSession($oldToken);
        $cachedData = $this->cache->get($oldCacheKey);
        $this->cache->delete($oldCacheKey);

        if ($cachedData) {
            $newCacheKey = CacheKeys::authSession($newToken);
            $this->cache->set($newCacheKey, $cachedData, 300);
        }

        Context::set('auth_token', $newToken);

        return $response
            ->withHeader('Access-Control-Expose-Headers', 'New-Token, New-Token-Expires-In')
            ->withHeader('New-Token', $newToken)
            ->withHeader('New-Token-Expires-In', (string) $ttl);
    }

    private function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        $queryParams = $request->getQueryParams();
        return $queryParams['token'] ?? null;
    }

    private function unauthorized(string $message, string $errorCode = 'UNAUTHORIZED'): ResponseInterface
    {
        return $this->response->json([
            'success' => false,
            'code' => 401,
            'message' => $message,
            'error_code' => $errorCode,
            'data' => null,
        ]);
    }
}
