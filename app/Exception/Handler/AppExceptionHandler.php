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

namespace App\Exception\Handler;

use App\Exception\BusinessException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    public function __construct(protected StdoutLoggerInterface $logger)
    {
    }

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->stopPropagation();

        $code = 500;
        $message = '服务器内部错误';
        $data = null;
        // 业务异常的 HTTP 状态仍为 200（前端按 body.code 判定），路由级异常才改真实状态码
        $httpStatus = 200;

        if ($throwable instanceof BusinessException) {
            $code = $throwable->getCode() ?: 400;
            $message = $throwable->getMessage();
        } elseif ($throwable instanceof ValidationException) {
            $code = 422;
            $message = '数据验证失败';
            $data = $throwable->errors();
        } elseif ($throwable instanceof InvalidArgumentException) {
            $code = $throwable->getCode() ?: 400;
            $message = $throwable->getMessage();
        } elseif ($throwable instanceof HttpException) {
            // 路由未命中 / 方法不允许，需返回真实 HTTP 状态，否则调用方无法区分
            $httpStatus = $code = $throwable->getStatusCode();
            $message = match ($code) {
                404 => '请求的资源不存在',
                405 => '请求方法不允许',
                default => $throwable->getMessage() ?: '请求异常',
            };
        } else {
            $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
            $this->logger->error($throwable->getTraceAsString());
        }

        $body = json_encode([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($httpStatus)
            ->withBody(new SwooleStream($body));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
