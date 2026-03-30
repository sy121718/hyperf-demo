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

namespace App\Service\Log;

use App\Model\Log\SysOperationLogsModel;
use Hyperf\Context\Context;
use Psr\Http\Message\ServerRequestInterface;

class RecordOperation
{
    public function __construct(
        protected SysOperationLogsModel $logModel,
    ) {
    }

    public function handle(array $data): int
    {
        $request = Context::get(ServerRequestInterface::class);

        $logData = [
            'admin_id' => $data['admin_id'] ?? currentUser('id') ?? 0,
            'menu_id' => $data['menu_id'] ?? null,
            'perm_dict_id' => $data['perm_dict_id'] ?? null,
            'service_method' => $data['service_method'] ?? null,
            'operation' => $data['operation'] ?? '未知操作',
            'api_path' => $data['api_path'] ?? ($request ? $request->getUri()->getPath() : null),
            'http_method' => $data['http_method'] ?? ($request ? $request->getMethod() : null),
            'request_data' => is_array($data['request_data'] ?? null) ? json_encode($data['request_data'], JSON_UNESCAPED_UNICODE) : ($data['request_data'] ?? null),
            'response_data' => is_array($data['response_data'] ?? null) ? json_encode($data['response_data'], JSON_UNESCAPED_UNICODE) : ($data['response_data'] ?? null),
            'ip' => $data['ip'] ?? $this->getClientIp($request),
            'location' => $data['location'] ?? null,
            'user_agent' => $data['user_agent'] ?? ($request ? $request->getHeaderLine('User-Agent') : null),
            'result' => $data['result'] ?? 1,
            'content' => $data['content'] ?? null,
            'execution_time' => $data['execution_time'] ?? null,
            'create_by' => $data['create_by'] ?? currentUser('id') ?? null,
        ];

        $log = $this->logModel->create($logData);
        return (int) $log->id;
    }

    public static function success(string $operation, array $requestData = [], array $responseData = []): int
    {
        return container()->get(self::class)->handle([
            'operation' => $operation,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'result' => 1,
        ]);
    }

    public static function failure(string $operation, ?string $content = null, array $requestData = []): int
    {
        return container()->get(self::class)->handle([
            'operation' => $operation,
            'content' => $content,
            'request_data' => $requestData,
            'result' => 0,
        ]);
    }

    private function getClientIp(?ServerRequestInterface $request): string
    {
        if (! $request) {
            return '0.0.0.0';
        }
        $xff = $request->getHeaderLine('X-Forwarded-For');
        if ($xff) {
            return trim(explode(',', $xff)[0]);
        }
        $serverParams = $request->getServerParams();
        return $serverParams['remote_addr'] ?? '0.0.0.0';
    }
}
