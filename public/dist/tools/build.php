<?php

declare(strict_types=1);

// 从脚本位置定位项目和产物目录，避免依赖临时路径或当前工作目录。
$source = dirname(__DIR__, 3);
$work = dirname(__DIR__);
$files = [];

// 复用前端工程规定的构建顺序，任一站点构建失败就停止打包。
$process = proc_open(['pnpm', '--dir', $source . '/app/View', 'build'], [STDIN, STDOUT, STDERR], $pipes, $source);
if (! is_resource($process)) {
    throw new RuntimeException('无法启动前端构建');
}
if (proc_close($process) !== 0) {
    throw new RuntimeException('前端构建失败，已停止打包');
}

// 仅打包应用、配置、依赖及前端构建产物，不包含密钥、缓存、上传和开发环境。
foreach (['app', 'bin', 'config', 'vendor', 'public'] as $directory) {
    printf("正在收集目录：%s\n", $directory);
    $iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($source . '/' . $directory, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $file) use ($source): bool {
            return ! $file->isLink()
                && ! in_array($file->getFilename(), ['node_modules', '.git', '.env', 'uploads'], true)
                && $file->getPathname() !== $source . '/app/View'
                && $file->getPathname() !== $source . '/public/dist';
        },
    ));
    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }
        $relative = substr($file->getPathname(), strlen($source) + 1);
        $files[$relative] = $file->getPathname();
    }
}
foreach (['composer.json', 'composer.lock', 'LICENSE'] as $file) {
    $files[$file] = $source . '/' . $file;
}

// 仅调整包内测试副本：本机监听、独立端口、两个工作进程。
$configuration = str_replace(
    ["'host' => '0.0.0.0'", "'port' => 9501", 'Constant::OPTION_WORKER_NUM => swoole_cpu_num()'],
    ["'host' => '127.0.0.1'", "'port' => 19501", 'Constant::OPTION_WORKER_NUM => 2'],
    file_get_contents($files['config/autoload/server.php']),
    $replacements,
);
if ($replacements !== 3) {
    throw new RuntimeException('独立端口配置替换数量不符合预期');
}
file_put_contents($work . '/tools/server.php', $configuration);
$files['config/autoload/server.php'] = $work . '/tools/server.php';

// 统一写入归档，避免逐文件 addFile 造成反复复制整个 PHAR。
$phar = new Phar($work . '/output/hyperf-demo.phar', 0, 'hyperf-demo.phar');
$phar->startBuffering();
$phar->buildFromIterator(new ArrayIterator($files));
$phar->setStub(file_get_contents($work . '/tools/stub.php'));
$phar->setSignatureAlgorithm(Phar::SHA256);
$phar->compressFiles(Phar::GZ);
$phar->stopBuffering();
unset($phar);

// phpmicro 将自身后面的脚本或 PHAR 作为入口，不需要调用外部 PHP。
$output = fopen($work . '/output/hyperf-demo', 'wb');
foreach ([$work . '/download-micro/micro.sfx', $work . '/output/hyperf-demo.phar'] as $inputPath) {
    $input = fopen($inputPath, 'rb');
    stream_copy_to_stream($input, $output);
    fclose($input);
}
fclose($output);
chmod($work . '/output/hyperf-demo', 0755);
printf("已打包 %d 个文件，产物 %s，大小 %.2f MiB\n", count($files), $work . '/output/hyperf-demo', filesize($work . '/output/hyperf-demo') / 1048576);
