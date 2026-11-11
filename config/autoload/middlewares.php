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
use App\Middleware\CorsMiddleware;
use Hyperf\Validation\Middleware\ValidationMiddleware;

return [
    'http' => [
        // 必须启用：FormRequest/DTO 的 validateResolved() 依赖它触发，
        // 否则 required 等“字段缺失”规则永远不会生效
        ValidationMiddleware::class,
        CorsMiddleware::class,
    ],
];
