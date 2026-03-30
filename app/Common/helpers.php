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
use App\Exception\BusinessException;
use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Psr\Container\ContainerInterface;

/**
 * 全局业务异常抛出函数.
 *
 * @param int $code HTTP 状态码：400 参数错误 / 401 未认证 / 403 权限不足 / 404 不存在
 * @throws BusinessException
 */
function paramError(string $message, int $code = 400): never
{
    $allowedCodes = [400, 401, 403, 404, 409, 422, 500, 502, 503];
    if (! in_array($code, $allowedCodes, true)) {
        $code = 400;
    }
    throw new BusinessException($code, $message);
}

/**
 * 获取当前 JWT 认证用户信息.
 *
 * @param null|string $field 指定字段名，不传返回全部
 */
function currentUser(?string $field = null): mixed
{
    $userInfo = Context::get('auth_user');

    if (! $userInfo) {
        paramError('用户未登录或会话已过期', 401);
    }

    if ($field !== null) {
        if (! array_key_exists($field, $userInfo)) {
            paramError("用户字段 '{$field}' 不存在", 400);
        }
        return $userInfo[$field];
    }

    return $userInfo;
}

/**
 * 获取 DI 容器（简写）.
 */
function container(): ContainerInterface
{
    return ApplicationContext::getContainer();
}

/**
 * 获取当前时间（UTC）.
 */
function millisecond(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.v');
}
