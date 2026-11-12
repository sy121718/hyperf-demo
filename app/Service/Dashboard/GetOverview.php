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
use App\Model\Admin\SysAdminSessionsModel;
use App\Model\Admin\SysLoginLogModel;
use App\Model\Admin\SysOperationLogModel;
use App\Model\Notify\NoticeModel;
use App\Model\Notify\NoticeReadModel;
use App\Model\Rbac\SysMenuModel;
use App\Model\Rbac\SysPermissionDictModel;
use App\Model\Rbac\SysRoleModel;

/**
 * 概览页指标聚合.
 *
 * 每个指标都绑定一个权限码：调用方只应传入当前用户实际拥有的权限码，
 * 没有权限的卡片直接不返回——前端据此隐藏整张卡，也省掉一次无谓查询。
 * 权限判定本身不在这里做，由 Gate 类统一处理，避免两处口径漂移。
 */
class GetOverview
{
    public function __construct(
        protected SysAdminModel $adminModel,
        protected SysRoleModel $roleModel,
        protected SysAdminSessionsModel $sessionModel,
        protected NoticeModel $noticeModel,
        protected NoticeReadModel $readModel,
        protected SysMenuModel $menuModel,
        protected SysPermissionDictModel $permissionModel,
        protected SysOperationLogModel $operationLogModel,
        protected SysLoginLogModel $loginLogModel,
    ) {
    }

    /**
     * @param null|array<int, string> $permissionCodes 当前用户持有的权限码；
     *                                                 传 null 表示超管，全部放行
     * @return array<int, array<string, mixed>>
     */
    public function handle(?array $permissionCodes): array
    {
        $cards = [];

        foreach ($this->definitions() as $definition) {
            // null = 超管，不看权限码直接给全部卡片
            if ($permissionCodes !== null
                && ! in_array($definition['permission'], $permissionCodes, true)) {
                continue;
            }

            $cards[] = [
                'key' => $definition['key'],
                'title' => $definition['title'],
                'value' => (int) ($definition['count'])(),
                'path' => $definition['path'],
            ];
        }

        return $cards;
    }

    /**
     * 指标定义：key / 标题 / 所需权限码 / 跳转路径 / 计数回调.
     *
     * @return array<int, array{key: string, title: string, permission: string, path: string, count: callable(): int}>
     */
    private function definitions(): array
    {
        return [
            [
                'key' => 'admin_total',
                'title' => '管理员',
                'permission' => 'admin:list',
                'path' => '/system/admin',
                'count' => fn () => $this->adminModel->query()->count(),
            ],
            [
                'key' => 'role_total',
                'title' => '角色',
                'permission' => 'role:list',
                'path' => '/system/role',
                'count' => fn () => $this->roleModel->query()->count(),
            ],
            [
                'key' => 'session_total',
                'title' => '在线会话',
                'permission' => 'session:list',
                'path' => '/ops/session',
                'count' => fn () => $this->sessionModel->query()->where('status', 1)->count(),
            ],
            [
                'key' => 'notice_unread',
                'title' => '未读公告',
                'permission' => 'notice:unreadCount',
                'path' => '/notify/notice',
                'count' => fn () => $this->unreadNoticeCount(),
            ],
            [
                'key' => 'menu_total',
                'title' => '菜单',
                'permission' => 'menu:tree',
                'path' => '/system/menu',
                'count' => fn () => $this->menuModel->query()->where('status', 1)->count(),
            ],
            [
                'key' => 'permission_total',
                'title' => '权限点',
                'permission' => 'permission:tree',
                'path' => '/system/permission',
                'count' => fn () => $this->permissionModel->query()->where('status', 1)->count(),
            ],
            [
                'key' => 'operation_today',
                'title' => '今日操作',
                'permission' => 'log:operationLog',
                'path' => '/ops/log/operation',
                'count' => fn () => $this->todayCount($this->operationLogModel, 'create_time'),
            ],
            [
                'key' => 'login_failed_today',
                'title' => '今日登录失败',
                'permission' => 'log:loginLog',
                'path' => '/ops/log/login',
                // 登录日志表没有 create_time，时间列是 login_time
                'count' => fn () => $this->todayCount($this->loginLogModel, 'login_time', ['status' => 0]),
            ],
        ];
    }

    /**
     * 当前登录用户的未读公告数.
     *
     * 复用 GetUnreadCount 同一口径（已发布且本人没有已读记录），
     * 直接数而不是「总数 - 已读数」：过期公告的已读记录会让后者算少。
     */
    private function unreadNoticeCount(): int
    {
        $userId = (int) currentUser('id');

        return (int) $this->noticeModel
            ->published()
            ->whereNotExists(function ($query) use ($userId) {
                $query->selectRaw('1')
                    ->from($this->readModel->getTable())
                    ->whereColumn($this->readModel->getTable() . '.notice_id', $this->noticeModel->getTable() . '.id')
                    ->where($this->readModel->getTable() . '.user_id', $userId);
            })
            ->count();
    }

    /**
     * 今日条数，按 UTC 自然日切分（与写入时的时间口径一致）.
     *
     * @param string $timeColumn 各日志表的时间列名不同：操作日志用 create_time，
     *                           登录日志用 login_time
     * @param array<string, mixed> $conditions
     */
    private function todayCount(mixed $model, string $timeColumn, array $conditions = []): int
    {
        $start = date('Y-m-d 00:00:00.000');
        $end = date('Y-m-d 23:59:59.999');

        return (int) $model->query()
            ->where($timeColumn, '>=', $start)
            ->where($timeColumn, '<=', $end)
            ->where($conditions)
            ->count();
    }
}
