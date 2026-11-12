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
use App\Service\Dashboard as DashboardAction;

class DashboardController extends AbstractController
{
    /**
     * 概览页指标
     * GET /api/dashboard/overview.
     *
     * 跨模块聚合：先由 Gate 取当前用户的权限码，再交给 GetOverview 按权限
     * 决定返回哪些指标卡片。没有权限的模块既不返回、也不产生查询。
     */
    public function overview(): array
    {
        $permissionCodes = container()->get(DashboardAction\Gate::class)->currentPermissionCodes();
        $cards = container()->get(DashboardAction\GetOverview::class)->handle($permissionCodes);

        return $this->success(['cards' => $cards], '获取成功');
    }
}
