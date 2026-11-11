<?php

declare(strict_types=1);

// 内置诊断入口不加载业务代码，也不读取目标机器的 PHP 配置。
if (($argv[1] ?? '') === '--runtime-info') {
    echo json_encode([
        'php' => PHP_VERSION,
        'sapi' => PHP_SAPI,
        'swoole' => phpversion('swoole'),
        'redis' => phpversion('redis'),
        'loaded_ini' => php_ini_loaded_file(),
        'scanned_ini' => php_ini_scanned_files(),
        'pdo_drivers' => PDO::getAvailableDrivers(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
    exit(0);
}

// Hyperf 需要可写的缓存和代理目录，因此先将随包代码解包到当前目录。
Phar::mapPhar('hyperf-demo.phar');
$archive = new Phar(__FILE__);
$signature = $archive->getSignature()['hash'];
$basePath = getcwd() . '/.hyperf-demo-' . substr($signature, 0, 12);
if (! is_dir($basePath) && ! mkdir($basePath, 0700)) {
    throw new RuntimeException('无法创建独立运行目录');
}
$lock = fopen($basePath . '/.extract.lock', 'c');
if ($lock === false || ! flock($lock, LOCK_EX)) {
    throw new RuntimeException('无法锁定解包目录');
}
try {
    if (! is_file($basePath . '/.ready')) {
        $archive->extractTo($basePath, null, true);
        if (! is_dir($basePath . '/runtime')) {
            mkdir($basePath . '/runtime', 0700);
        }
        file_put_contents($basePath . '/.ready', $signature);
    }
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}

// 切换到包内副本，确保扫描、日志和容器缓存不访问原仓库。
define('BASE_PATH', $basePath);
chdir(BASE_PATH);
fwrite(STDERR, '独立运行目录：' . BASE_PATH . PHP_EOL);
require BASE_PATH . '/bin/hyperf.php';
__HALT_COMPILER();
