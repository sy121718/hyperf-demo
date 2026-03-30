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
use Swoole\Atomic;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine\WaitGroup;

/**
 * 100万请求压测 - 真实流量模型
 * 30% 重查询（673万行附件列表） + 70% 轻查询（缓存接口）
 * 用法: php bench.php <token>.
 */
$token = $argv[1] ?? '';
if (! $token) {
    echo "用法: php bench.php <token>\n";
    exit(1);
}

$host = '127.0.0.1';
$port = 9501;

$heavyEndpoints = [
    ['GET', '/api/attachment/list?page=1&limit=20'],
    ['GET', '/api/attachment/list?page=2&limit=20'],
    ['GET', '/api/attachment/list?page=1&limit=50&status=1'],
    ['GET', '/api/attachment/list?page=10&limit=20'],
    ['GET', '/api/attachment/list?page=100&limit=20'],
    ['GET', '/api/attachment/list?page=5&limit=50'],
];

$lightEndpoints = [
    ['GET', '/api/role/options'],
    ['GET', '/api/menu/tree'],
    ['GET', '/api/permission/options'],
    ['GET', '/api/dict/tree'],
    ['GET', '/api/auth/profile'],
    ['GET', '/api/role/all'],
];

// 30% heavy, 70% light — 接近真实场景
$weightedEndpoints = [];
foreach ($heavyEndpoints as $ep) {
    $weightedEndpoints[] = ['ep' => $ep, 'type' => 'heavy'];
}
foreach ($lightEndpoints as $ep) {
    $weightedEndpoints[] = ['ep' => $ep, 'type' => 'light'];
    $weightedEndpoints[] = ['ep' => $ep, 'type' => 'light'];
}

// 并发递增，每阶段请求总量递增，真正模拟流量高峰
$stages = [
    ['c' => 200, 'rpc' => 400],   // 80,000    热身
    ['c' => 400, 'rpc' => 500],   // 200,000   中等
    ['c' => 600, 'rpc' => 500],   // 300,000   高负载
    ['c' => 800, 'rpc' => 300],   // 240,000   峰值
    ['c' => 1000, 'rpc' => 200],   // 200,000   极限
];

function workerMem(): array
{
    $out = shell_exec("ps aux | grep 'hyperf-rbac.Worker' | grep -v grep");
    $total = 0;
    $count = 0;
    $max = 0;
    foreach (explode("\n", trim($out ?? '')) as $line) {
        if (preg_match('/\S+\s+\d+\s+\S+\s+\S+\s+(\d+)\s+(\d+)/', $line, $m)) {
            $rss = (int) $m[2];
            $total += $rss;
            ++$count;
            $max = max($max, $rss);
        }
    }
    return ['total' => $total, 'count' => $count, 'max' => $max, 'avg' => $count > 0 ? intdiv($total, $count) : 0];
}

function run(int $c, int $rpc, string $token, string $host, int $port, array $eps): array
{
    $ok = new Atomic(0);
    $ko = new Atomic(0);
    $totalMs = new Atomic(0);
    $maxMs = new Atomic(0);
    $e5xx = new Atomic(0);
    $eConn = new Atomic(0);
    $eTmo = new Atomic(0);
    $eOther = new Atomic(0);
    $slowCount = new Atomic(0);
    $heavyOk = new Atomic(0);
    $heavyTotal = new Atomic(0);
    $lightOk = new Atomic(0);
    $lightTotal = new Atomic(0);

    $t0 = microtime(true);
    $wg = new WaitGroup();

    for ($i = 0; $i < $c; ++$i) {
        $wg->add();
        Coroutine::create(function () use (
            $wg,
            $rpc,
            $token,
            $host,
            $port,
            $eps,
            $ok,
            $ko,
            $totalMs,
            $maxMs,
            $e5xx,
            $eConn,
            $eTmo,
            $eOther,
            $slowCount,
            $heavyOk,
            $heavyTotal,
            $lightOk,
            $lightTotal
        ) {
            $cli = new Client($host, $port);
            $cli->set(['timeout' => 30, 'keep_alive' => true]);
            $cli->setHeaders(['Authorization' => "Bearer {$token}", 'Content-Type' => 'application/json', 'Connection' => 'keep-alive']);

            $reconnects = 0;
            for ($j = 0; $j < $rpc; ++$j) {
                $pick = $eps[array_rand($eps)];
                $ep = $pick['ep'];
                $isHeavy = $pick['type'] === 'heavy';

                $t = microtime(true);
                $cli->get($ep[1]);
                $ms = (int) ((microtime(true) - $t) * 1000);
                $code = $cli->getStatusCode();
                $ec = $cli->errCode;

                $totalMs->add($ms);
                $cur = $maxMs->get();
                if ($ms > $cur) {
                    $maxMs->set($ms);
                }
                if ($ms > 2000) {
                    $slowCount->add(1);
                }

                if ($isHeavy) {
                    $heavyTotal->add(1);
                } else {
                    $lightTotal->add(1);
                }

                if ($code > 0 && $code < 500) {
                    $ok->add(1);
                    if ($isHeavy) {
                        $heavyOk->add(1);
                    } else {
                        $lightOk->add(1);
                    }
                } else {
                    $ko->add(1);
                    if ($code === -1) {
                        match (true) {
                            $ec === 111 => $eConn->add(1),
                            $ec === 110 => $eTmo->add(1),
                            default => $eOther->add(1),
                        };
                        if ($reconnects < 3) {
                            $cli->close();
                            $cli = new Client($host, $port);
                            $cli->set(['timeout' => 30, 'keep_alive' => true]);
                            $cli->setHeaders(['Authorization' => "Bearer {$token}", 'Content-Type' => 'application/json', 'Connection' => 'keep-alive']);
                            ++$reconnects;
                        } else {
                            break;
                        }
                    } elseif ($code === -2) {
                        $eTmo->add(1);
                    } elseif ($code >= 500) {
                        $e5xx->add(1);
                    } else {
                        $eOther->add(1);
                    }
                }
            }
            $cli->close();
            $wg->done();
        });
    }

    $wg->wait();
    $el = microtime(true) - $t0;
    $s = $ok->get();
    $f = $ko->get();
    $n = $s + $f;

    return [
        'c' => $c, 'rpc' => $rpc, 'n' => $n, 'ok' => $s, 'ko' => $f,
        'qps' => $n / max($el, 0.001), 'avg' => $n > 0 ? $totalMs->get() / $n : 0,
        'max' => $maxMs->get(), 'sec' => $el, 'fr' => $n > 0 ? $f / $n * 100 : 0,
        'e5xx' => $e5xx->get(), 'eConn' => $eConn->get(), 'eTmo' => $eTmo->get(), 'eOther' => $eOther->get(),
        'slow' => $slowCount->get(),
        'heavyOk' => $heavyOk->get(), 'heavyTotal' => $heavyTotal->get(),
        'lightOk' => $lightOk->get(), 'lightTotal' => $lightTotal->get(),
    ];
}

Co\run(function () use ($stages, $token, $host, $port, $weightedEndpoints) {
    echo "╔═══════════════════════════════════════════════════════════════════╗\n";
    echo "║  100万请求压测 - 30%重查询(673万行) + 70%轻查询(带缓存)        ║\n";
    echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

    $mem0 = workerMem();
    printf(
        "Workers: %d个, 基线内存: 总%dMB / 均%dMB / 峰%dMB\n\n",
        $mem0['count'],
        intdiv($mem0['total'], 1024),
        intdiv($mem0['avg'], 1024),
        intdiv($mem0['max'], 1024)
    );

    $results = [];
    $cumReq = 0;
    foreach ($stages as $i => $s) {
        $c = $s['c'];
        $rpc = $s['rpc'];
        $n = $c * $rpc;
        $cumReq += $n;
        printf("── 第%d轮: %d并发 × %d请求/连接 = %dk 请求 (累计 %dk) ──\n", $i + 1, $c, $rpc, intdiv($n, 1000), intdiv($cumReq, 1000));

        $r = run($c, $rpc, $token, $host, $port, $weightedEndpoints);
        $results[] = $r;
        $mem = workerMem();

        printf("   QPS: %-6.0f  Avg: %-6.0fms  Max: %-6dms  慢(>2s): %d\n", $r['qps'], $r['avg'], $r['max'], $r['slow']);
        printf("   成功: %-7d  失败: %-5d (%.2f%%)  耗时: %.1fs\n", $r['ok'], $r['ko'], $r['fr'], $r['sec']);
        printf(
            "   重查询: %d/%d (%.1f%%)  轻查询: %d/%d (%.1f%%)\n",
            $r['heavyOk'],
            $r['heavyTotal'],
            $r['heavyTotal'] > 0 ? $r['heavyOk'] / $r['heavyTotal'] * 100 : 0,
            $r['lightOk'],
            $r['lightTotal'],
            $r['lightTotal'] > 0 ? $r['lightOk'] / $r['lightTotal'] * 100 : 0
        );
        printf("   内存: 总%dMB / 均%dMB / 峰%dMB\n", intdiv($mem['total'], 1024), intdiv($mem['avg'], 1024), intdiv($mem['max'], 1024));

        if ($r['ko'] > 0) {
            printf("   错误: 拒连:%d 超时:%d 5xx:%d 其他:%d\n", $r['eConn'], $r['eTmo'], $r['e5xx'], $r['eOther']);
        }

        if ($r['fr'] > 15) {
            echo "   ⚠️  失败率>15%，停止！\n\n";
            break;
        }

        $chk = new Client($host, $port);
        $chk->set(['timeout' => 5]);
        $chk->get('/');
        $alive = $chk->getStatusCode() > 0;
        $chk->close();
        if (! $alive) {
            echo "   💥 服务挂了！\n\n";
            break;
        }

        if ($i < count($stages) - 1) {
            echo "   ✅ 通过，休息5s\n\n";
            Coroutine::sleep(5);
        } else {
            echo "\n";
        }
    }

    $memF = workerMem();
    $allN = array_sum(array_column($results, 'n'));
    $allF = array_sum(array_column($results, 'ko'));
    $allT = array_sum(array_column($results, 'sec'));
    $allSlow = array_sum(array_column($results, 'slow'));
    $allHeavy = array_sum(array_column($results, 'heavyTotal'));
    $allLight = array_sum(array_column($results, 'lightTotal'));

    echo "\n╔═══════════════════════════════════════════════════════════════════╗\n";
    echo "║                      100万请求汇总报告                           ║\n";
    echo "╚═══════════════════════════════════════════════════════════════════╝\n";
    printf("%-6s %-6s %-8s %-7s %-7s %-7s %-8s %-6s %-7s\n", '并发', 'req/c', '请求数', 'QPS', 'Avg', 'Max', '慢(>2s)', '失败', '耗时');
    echo str_repeat('─', 70) . "\n";
    foreach ($results as $r) {
        printf(
            "%-6d %-6d %-8d %-7.0f %-7.0f %-7d %-8d %-6d %.1fs\n",
            $r['c'],
            $r['rpc'],
            $r['n'],
            $r['qps'],
            $r['avg'],
            $r['max'],
            $r['slow'],
            $r['ko'],
            $r['sec']
        );
    }

    echo str_repeat('─', 70) . "\n";
    printf(
        "总请求: %d | 失败: %d (%.3f%%) | 慢请求: %d (%.1f%%)\n",
        $allN,
        $allF,
        $allN > 0 ? $allF / $allN * 100 : 0,
        $allSlow,
        $allN > 0 ? $allSlow / $allN * 100 : 0
    );
    printf("总耗时: %.1fs | 平均QPS: %.0f\n", $allT, $allN / max($allT, 1));
    printf(
        "查询分布: 重查询 %d (%.0f%%) + 轻查询 %d (%.0f%%)\n",
        $allHeavy,
        $allN > 0 ? $allHeavy / $allN * 100 : 0,
        $allLight,
        $allN > 0 ? $allLight / $allN * 100 : 0
    );
    printf(
        "内存变化: 总%dMB → %dMB (+%dMB) / 单worker峰 %dMB → %dMB\n",
        intdiv($mem0['total'], 1024),
        intdiv($memF['total'], 1024),
        intdiv(max($memF['total'] - $mem0['total'], 0), 1024),
        intdiv($mem0['max'], 1024),
        intdiv($memF['max'], 1024)
    );
});
