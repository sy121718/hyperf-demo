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
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

use function Hyperf\Support\env;

return [
    // 默认通道
    'default' => env('LOG_CHANNEL', 'default'),

    'channels' => [
        // 应用主日志
        'default' => [
            'handler' => [
                'class' => StreamHandler::class,
                'constructor' => [
                    'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
                    'level' => Level::Debug,
                ],
            ],
            'formatter' => [
                'class' => LineFormatter::class,
                'constructor' => [
                    'format' => null,
                    'dateFormat' => 'Y-m-d H:i:s',
                    'allowInlineLineBreaks' => true,
                ],
            ],
        ],

        // SQL 日志独立成文件，便于单独开关与切割
        'sql' => [
            'handler' => [
                'class' => StreamHandler::class,
                'constructor' => [
                    'stream' => BASE_PATH . '/runtime/logs/sql.log',
                    'level' => Level::Debug,
                ],
            ],
            'formatter' => [
                'class' => LineFormatter::class,
                'constructor' => [
                    'format' => null,
                    'dateFormat' => 'Y-m-d H:i:s',
                    'allowInlineLineBreaks' => true,
                ],
            ],
        ],
    ],
];
