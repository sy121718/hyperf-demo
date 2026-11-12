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

namespace App\Service\Dashboard;

use App\Model\Admin\SysAdminModel;

/**
 * 当前登录用户能看哪些模块.
 *
 * 概览页要按权限决定返回哪些卡片，判定必须和接口鉴权同口径，
 * 否则会出现「页面显示卡片但点进去 403」。这里只做「取当前用户权限码」
 * 这一件事，权限码本身仍由 SysAdminModel::permissionCodes() 统一解析。
 */
class Gate
{
    public function __construct(
        protected SysAdminModel $adminModel,
    ) {
    }

    /**
     * 当前用户的权限码；超管视为拥有全部权限.
     *
     * 返回 null 表示「全部放行」，调用方不需要再逐个比对。
     *
     * @return null|array<int, string>
     */
    public function currentPermissionCodes(): ?array
    {
        if ((int) currentUser('is_admin') === 1) {
            return null;
        }

        $adminCode = (string) currentUser('admin_code');
        if ($adminCode === '') {
            return [];
        }

        return SysAdminModel::permissionCodesByAdminCode($adminCode);
    }
}
