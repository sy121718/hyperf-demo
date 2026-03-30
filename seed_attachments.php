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
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=base;charset=utf8mb4', 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$categories = $pdo->query('SELECT id, category_code FROM sys_file_category WHERE status = 1')->fetchAll(PDO::FETCH_ASSOC);
$catIds = array_column($categories, 'id');
$catMap = array_column($categories, 'category_code', 'id');

$fileTemplates = [
    'doc_contracts' => ['ext' => 'pdf', 'mime' => 'application/pdf', 'type' => 'document', 'names' => ['合同_%s_v%d', '协议书_%s_%d', '合同附件_%s_%d'], 'size' => [50000, 5000000]],
    'doc_contracts_sales' => ['ext' => 'pdf', 'mime' => 'application/pdf', 'type' => 'document', 'names' => ['销售合同_%s_%d', '订单合同_%s_%d'], 'size' => [100000, 3000000]],
    'doc_contracts_purchase' => ['ext' => 'pdf', 'mime' => 'application/pdf', 'type' => 'document', 'names' => ['采购合同_%s_%d', '供应商协议_%s_%d'], 'size' => [80000, 2500000]],
    'doc_contracts_labor' => ['ext' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'type' => 'document', 'names' => ['劳动合同_%s_%d', '入职协议_%s_%d'], 'size' => [60000, 1500000]],
    'doc_contracts_service' => ['ext' => 'pdf', 'mime' => 'application/pdf', 'type' => 'document', 'names' => ['服务合同_%s_%d', 'SLA协议_%s_%d'], 'size' => [70000, 2000000]],
    'doc_reports' => ['ext' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'type' => 'document', 'names' => ['报告_%s_%d', '分析报告_%s_%d'], 'size' => [100000, 8000000]],
    'doc_reports_monthly' => ['ext' => 'xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'type' => 'document', 'names' => ['月报_%s_%d月', '月度汇总_%s_%d'], 'size' => [200000, 5000000]],
    'doc_reports_quarterly' => ['ext' => 'pptx', 'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'type' => 'document', 'names' => ['季报_%s_Q%d', '季度复盘_%s_%d'], 'size' => [500000, 15000000]],
    'doc_reports_annual' => ['ext' => 'pdf', 'mime' => 'application/pdf', 'type' => 'document', 'names' => ['年报_%s_%d年', '年终总结_%s_%d'], 'size' => [1000000, 30000000]],
    'doc_technical' => ['ext' => 'md', 'mime' => 'text/markdown', 'type' => 'document', 'names' => ['技术方案_%s_%d', 'API文档_%s_v%d', '架构设计_%s_%d'], 'size' => [5000, 500000]],
    'doc_training' => ['ext' => 'pptx', 'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'type' => 'document', 'names' => ['培训课件_%s_%d', '新人手册_%s_v%d'], 'size' => [500000, 20000000]],
    'doc_policies' => ['ext' => 'pdf', 'mime' => 'application/pdf', 'type' => 'document', 'names' => ['制度_%s_%d版', '规范_%s_v%d'], 'size' => [30000, 1000000]],
    'img_products' => ['ext' => 'jpg', 'mime' => 'image/jpeg', 'type' => 'image', 'names' => ['产品_%s_%d', 'SPU_%s_%d'], 'size' => [50000, 5000000]],
    'img_products_main' => ['ext' => 'jpg', 'mime' => 'image/jpeg', 'type' => 'image', 'names' => ['主图_%s_%d', 'cover_%s_%d'], 'size' => [100000, 3000000]],
    'img_products_detail' => ['ext' => 'jpg', 'mime' => 'image/jpeg', 'type' => 'image', 'names' => ['详情_%s_%d', 'detail_%s_%d'], 'size' => [200000, 8000000]],
    'img_products_sku' => ['ext' => 'png', 'mime' => 'image/png', 'type' => 'image', 'names' => ['SKU_%s_%d', 'sku_color_%s_%d'], 'size' => [50000, 2000000]],
    'img_banners' => ['ext' => 'jpg', 'mime' => 'image/jpeg', 'type' => 'image', 'names' => ['banner_%s_%d', 'slider_%s_%d'], 'size' => [100000, 5000000]],
    'img_banners_home' => ['ext' => 'jpg', 'mime' => 'image/jpeg', 'type' => 'image', 'names' => ['首页banner_%s_%d', 'home_slide_%s_%d'], 'size' => [200000, 5000000]],
    'img_banners_event' => ['ext' => 'jpg', 'mime' => 'image/jpeg', 'type' => 'image', 'names' => ['活动_%s_%d', 'event_%s_%d'], 'size' => [150000, 4000000]],
    'img_icons' => ['ext' => 'svg', 'mime' => 'image/svg+xml', 'type' => 'image', 'names' => ['icon_%s_%d', 'ic_%s_%d'], 'size' => [500, 50000]],
    'img_avatars' => ['ext' => 'jpg', 'mime' => 'image/jpeg', 'type' => 'image', 'names' => ['avatar_%s_%d', 'user_%s_%d'], 'size' => [10000, 500000]],
    'img_screenshots' => ['ext' => 'png', 'mime' => 'image/png', 'type' => 'image', 'names' => ['截图_%s_%d', 'screen_%s_%d'], 'size' => [100000, 10000000]],
    'img_backgrounds' => ['ext' => 'jpg', 'mime' => 'image/jpeg', 'type' => 'image', 'names' => ['背景_%s_%d', 'bg_%s_%d'], 'size' => [200000, 15000000]],
    'vid_demos' => ['ext' => 'mp4', 'mime' => 'video/mp4', 'type' => 'video', 'names' => ['演示_%s_%d', 'demo_%s_%d'], 'size' => [5000000, 500000000]],
    'vid_training' => ['ext' => 'mp4', 'mime' => 'video/mp4', 'type' => 'video', 'names' => ['培训_%s_%d', 'lesson_%s_%d'], 'size' => [10000000, 800000000]],
    'vid_promo' => ['ext' => 'mp4', 'mime' => 'video/mp4', 'type' => 'video', 'names' => ['宣传片_%s_%d', 'promo_%s_%d'], 'size' => [20000000, 1000000000]],
    'vid_recordings' => ['ext' => 'mp4', 'mime' => 'video/mp4', 'type' => 'video', 'names' => ['录屏_%s_%d', 'rec_%s_%d'], 'size' => [5000000, 300000000]],
    'aud_meetings' => ['ext' => 'mp3', 'mime' => 'audio/mpeg', 'type' => 'audio', 'names' => ['会议_%s_%d', 'meeting_%s_%d'], 'size' => [1000000, 50000000]],
    'aud_bgm' => ['ext' => 'mp3', 'mime' => 'audio/mpeg', 'type' => 'audio', 'names' => ['音乐_%s_%d', 'bgm_%s_%d'], 'size' => [2000000, 15000000]],
    'aud_voice' => ['ext' => 'ogg', 'mime' => 'audio/ogg', 'type' => 'audio', 'names' => ['语音_%s_%d', 'voice_%s_%d'], 'size' => [10000, 2000000]],
    'arc_backups' => ['ext' => 'tar.gz', 'mime' => 'application/gzip', 'type' => 'archive', 'names' => ['备份_%s_%d', 'backup_%s_%d'], 'size' => [10000000, 2000000000]],
    'arc_resources' => ['ext' => 'zip', 'mime' => 'application/zip', 'type' => 'archive', 'names' => ['资源包_%s_%d', 'assets_%s_%d'], 'size' => [5000000, 500000000]],
    'arc_releases' => ['ext' => 'zip', 'mime' => 'application/zip', 'type' => 'archive', 'names' => ['发布_%s_v%d', 'release_%s_%d'], 'size' => [1000000, 200000000]],
    'des_ui' => ['ext' => 'fig', 'mime' => 'application/octet-stream', 'type' => 'design', 'names' => ['UI_%s_%d', 'design_%s_%d'], 'size' => [1000000, 100000000]],
    'des_ui_web' => ['ext' => 'fig', 'mime' => 'application/octet-stream', 'type' => 'design', 'names' => ['Web_UI_%s_%d', 'web_design_%s_%d'], 'size' => [2000000, 80000000]],
    'des_ui_mobile' => ['ext' => 'sketch', 'mime' => 'application/octet-stream', 'type' => 'design', 'names' => ['App_UI_%s_%d', 'mobile_%s_%d'], 'size' => [3000000, 120000000]],
    'des_ui_miniapp' => ['ext' => 'fig', 'mime' => 'application/octet-stream', 'type' => 'design', 'names' => ['小程序_%s_%d', 'miniapp_%s_%d'], 'size' => [1000000, 50000000]],
    'des_graphic' => ['ext' => 'psd', 'mime' => 'image/vnd.adobe.photoshop', 'type' => 'design', 'names' => ['海报_%s_%d', 'poster_%s_%d'], 'size' => [5000000, 200000000]],
    'des_prototype' => ['ext' => 'rp', 'mime' => 'application/octet-stream', 'type' => 'design', 'names' => ['原型_%s_%d', 'proto_%s_%d'], 'size' => [500000, 30000000]],
    'des_3d' => ['ext' => 'obj', 'mime' => 'model/obj', 'type' => 'design', 'names' => ['模型_%s_%d', '3d_%s_%d'], 'size' => [1000000, 500000000]],
    'dat_excel' => ['ext' => 'xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'type' => 'data', 'names' => ['数据_%s_%d', 'data_%s_%d'], 'size' => [50000, 20000000]],
    'dat_excel_sales' => ['ext' => 'xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'type' => 'data', 'names' => ['销售数据_%s_%d月', 'sales_%s_%d'], 'size' => [100000, 15000000]],
    'dat_excel_inventory' => ['ext' => 'xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'type' => 'data', 'names' => ['库存_%s_%d', 'inventory_%s_%d'], 'size' => [80000, 10000000]],
    'dat_excel_finance' => ['ext' => 'xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'type' => 'data', 'names' => ['财务_%s_%d', 'finance_%s_%d'], 'size' => [200000, 25000000]],
    'dat_csv' => ['ext' => 'csv', 'mime' => 'text/csv', 'type' => 'data', 'names' => ['导出_%s_%d', 'export_%s_%d'], 'size' => [10000, 50000000]],
    'dat_sql' => ['ext' => 'sql', 'mime' => 'application/sql', 'type' => 'data', 'names' => ['DB备份_%s_%d', 'dump_%s_%d'], 'size' => [100000, 500000000]],
    'dat_json' => ['ext' => 'json', 'mime' => 'application/json', 'type' => 'data', 'names' => ['配置_%s_%d', 'config_%s_%d'], 'size' => [500, 5000000]],
    'oth_temp' => ['ext' => 'tmp', 'mime' => 'application/octet-stream', 'type' => 'other', 'names' => ['临时_%s_%d', 'tmp_%s_%d'], 'size' => [1000, 10000000]],
    'oth_unsorted' => ['ext' => 'bin', 'mime' => 'application/octet-stream', 'type' => 'other', 'names' => ['未分类_%s_%d', 'file_%s_%d'], 'size' => [1000, 50000000]],
];

$defaultTpl = ['ext' => 'dat', 'mime' => 'application/octet-stream', 'type' => 'other', 'names' => ['file_%s_%d'], 'size' => [1000, 10000000]];

$storageTypes = ['local', 'local', 'local', 'local', 'oss', 'cos'];
$depts = ['技术部', '产品部', '设计部', '市场部', '运营部', '财务部', '人事部', '销售部'];
$projects = ['电商平台', '管理后台', '小程序', '数据中台', 'CRM系统', 'ERP系统', '官网', '内部工具'];

$total = 1730000;
$batchSize = 10000;
$batches = (int) ceil($total / $batchSize);

$weights = [];
foreach ($catIds as $cid) {
    $code = $catMap[$cid] ?? '';
    if (str_contains($code, 'img_products') || str_contains($code, 'img_banners')) {
        $weights[$cid] = 8;
    } elseif (str_starts_with($code, 'img_')) {
        $weights[$cid] = 5;
    } elseif (str_starts_with($code, 'doc_')) {
        $weights[$cid] = 4;
    } elseif (str_starts_with($code, 'dat_')) {
        $weights[$cid] = 3;
    } elseif (str_starts_with($code, 'des_')) {
        $weights[$cid] = 2;
    } elseif (str_starts_with($code, 'vid_') || str_starts_with($code, 'aud_')) {
        $weights[$cid] = 1;
    } else {
        $weights[$cid] = 2;
    }
}
$weightedIds = [];
foreach ($weights as $cid => $w) {
    for ($k = 0; $k < $w; ++$k) {
        $weightedIds[] = $cid;
    }
}

$startTime = strtotime('2024-06-01');
$endTime = strtotime('2026-03-06');

echo "开始生成 {$total} 条附件数据...\n";
$t0 = microtime(true);
$inserted = 0;

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('ALTER TABLE sys_attachment DISABLE KEYS');

for ($b = 0; $b < $batches; ++$b) {
    $count = min($batchSize, $total - $inserted);
    $values = [];
    $params = [];

    for ($i = 0; $i < $count; ++$i) {
        $catId = $weightedIds[array_rand($weightedIds)];
        $code = $catMap[$catId] ?? '';
        $tpl = $fileTemplates[$code] ?? $defaultTpl;

        $nameTemplate = $tpl['names'][array_rand($tpl['names'])];
        $dept = $depts[array_rand($depts)];
        $proj = $projects[array_rand($projects)];
        $seq = mt_rand(1000, 99999);
        $fileName = sprintf($nameTemplate, $proj, $seq) . '.' . $tpl['ext'];

        $fileSize = mt_rand($tpl['size'][0], $tpl['size'][1]);
        $storageType = $storageTypes[array_rand($storageTypes)];

        $ts = mt_rand($startTime, $endTime);
        $createTime = date('Y-m-d H:i:s', $ts) . '.' . str_pad((string) mt_rand(0, 999), 3, '0', STR_PAD_LEFT);
        $updateTime = $createTime;

        $datePath = date('Y/m/d', $ts);
        $hash = md5($fileName . $seq . $catId . $i . $b);
        $filePath = "uploads/{$tpl['type']}/{$datePath}/{$hash}.{$tpl['ext']}";

        if ($storageType === 'local') {
            $storagePath = "/data/storage/{$filePath}";
            $url = "https://cdn.example.com/{$filePath}";
        } else {
            $storagePath = "{$filePath}";
            $url = "https://{$storageType}.example.com/{$filePath}";
        }

        $status = mt_rand(1, 100) <= 98 ? 1 : 0;

        $values[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, 1, 1, ?, ?)';
        array_push($params, $catId, $fileName, $filePath, $fileSize, $tpl['type'], $tpl['mime'], $storageType, $storagePath, $url, $hash, $status, $createTime, $updateTime);
    }

    $sql = 'INSERT INTO sys_attachment (category_id, file_name, file_path, file_size, file_type, mime_type, storage_type, storage_path, url, md5, extra_info, status, create_by, update_by, create_time, update_time) VALUES ' . implode(',', $values);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $inserted += $count;
    $elapsed = microtime(true) - $t0;
    $speed = $inserted / max($elapsed, 0.001);
    $eta = ($total - $inserted) / max($speed, 1);
    printf("\r  进度: %d/%d (%.1f%%) | 速度: %.0f条/s | 预计剩余: %.0fs", $inserted, $total, $inserted / $total * 100, $speed, $eta);
}

$pdo->exec('ALTER TABLE sys_attachment ENABLE KEYS');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$elapsed = microtime(true) - $t0;
printf("\n\n✅ 完成！共插入 %d 条，耗时 %.1f秒 (%.0f条/s)\n", $inserted, $elapsed, $inserted / $elapsed);
