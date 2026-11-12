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

namespace App\Controller\System;

use App\Controller\AbstractController;
use App\Dto\Cache\CacheRequest;
use App\Service\Cache\ClearCache;

class CacheController extends AbstractController
{
    /**
     * 获取可清除的缓存模块列表
     * GET /api/cache/modules.
     */
    public function modules(): array
    {
        $modules = container()->get(ClearCache::class)->getModules();
        return $this->success($modules);
    }

    /**
     * 手动清除缓存
     * POST /api/cache/clear.
     *
     * Body: { "modules": ["role", "menu"] }  — 可选，不传则全部清除
     */
    public function clear(CacheRequest $request): array
    {
        $result = container()->get(ClearCache::class)->handle($request);

        $msg = $result['is_full_clear'] ? '全部缓存已清除' : '选中模块缓存已清除';

        return $this->success($result, $msg);
    }
}
