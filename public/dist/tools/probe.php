<?php

declare(strict_types=1);

// 直接在独立运行时中检查版本与扩展，避免误用本机 PHP 配置。
$result = [
    'php' => PHP_VERSION,
    'sapi' => PHP_SAPI,
    'swoole' => phpversion('swoole'),
    'redis' => phpversion('redis'),
    'loaded_ini' => php_ini_loaded_file(),
    'scanned_ini' => php_ini_scanned_files(),
    'extensions' => get_loaded_extensions(),
    'pdo_drivers' => PDO::getAvailableDrivers(),
];
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
