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
use App\Middleware\JwtAuthMiddleware;
use App\Middleware\PermissionMiddleware;
use Hyperf\HttpServer\Router\Router;

Router::get('/favicon.ico', function () {
    return '';
});

Router::get('/', function () {
    return ['success' => true, 'message' => 'Hyperf RBAC API'];
});

// ==================== 公开路由（无需认证） ====================
Router::addGroup('/api/', function () {
    Router::post('auth/login', 'App\Controller\System\AuthController@login');
});

// ==================== 认证路由（需要 JWT） ====================
Router::addGroup('/api/', function () {
    // 认证 & 个人中心
    Router::post('auth/logout', 'App\Controller\System\AuthController@logout');
    Router::get('auth/profile', 'App\Controller\System\AuthController@profile');
    Router::put('auth/profile', 'App\Controller\System\AuthController@updateProfile');
    Router::post('auth/change-password', 'App\Controller\System\AuthController@changePassword');

    // 管理员管理
    Router::get('admin/list', 'App\Controller\System\AdminController@list');
    Router::get('admin/all', 'App\Controller\System\AdminController@all');
    Router::get('admin/menu', 'App\Controller\System\AdminController@getMenu');
    Router::post('admin/create', 'App\Controller\System\AdminController@create');
    Router::put('admin/update/{id:\d+}', 'App\Controller\System\AdminController@update');
    Router::delete('admin/delete/{id:\d+}', 'App\Controller\System\AdminController@delete');
    Router::get('admin/info/{id:\d+}', 'App\Controller\System\AdminController@info');
    Router::get('admin/full-info/{id:\d+}', 'App\Controller\System\AdminController@fullInfo');
    Router::post('admin/reset-password', 'App\Controller\System\AdminController@resetPassword');
    Router::put('admin/ban/{id:\d+}', 'App\Controller\System\AdminController@ban');
    Router::put('admin/unban/{id:\d+}', 'App\Controller\System\AdminController@unban');
    Router::post('admin/force-logout', 'App\Controller\System\AdminController@forceLogout');
    Router::put('admin/reset-failure/{id:\d+}', 'App\Controller\System\AdminController@resetFailure');
    Router::delete('admin/kick/{id:\d+}', 'App\Controller\System\AdminController@kick');
    Router::get('admin/roles/{id:\d+}', 'App\Controller\System\AdminController@getRoles');
    Router::get('admin/permissions/{id:\d+}', 'App\Controller\System\AdminController@getPermissions');
    Router::post('admin/assign-roles', 'App\Controller\System\AdminController@assignRoles');
    Router::post('admin/revoke-role', 'App\Controller\System\AdminController@revokeRole');

    // 角色管理
    Router::get('role/list', 'App\Controller\System\RoleController@list');
    Router::get('role/all', 'App\Controller\System\RoleController@all');
    Router::get('role/options', 'App\Controller\System\RoleController@options');
    Router::get('role/info/{id:\d+}', 'App\Controller\System\RoleController@info');
    Router::post('role/create', 'App\Controller\System\RoleController@create');
    Router::put('role/update/{id:\d+}', 'App\Controller\System\RoleController@update');
    Router::delete('role/delete/{id:\d+}', 'App\Controller\System\RoleController@delete');
    Router::post('role/batch-delete', 'App\Controller\System\RoleController@batchDelete');
    Router::post('role/assign-menus', 'App\Controller\System\RoleController@assignMenus');
    Router::post('role/remove-menu', 'App\Controller\System\RoleController@removeMenu');
    Router::post('role/assign-to-admin', 'App\Controller\System\RoleController@assignToAdmin');
    Router::post('role/remove-from-admin', 'App\Controller\System\RoleController@removeFromAdmin');

    // 菜单管理
    Router::get('menu/tree', 'App\Controller\System\MenuController@tree');
    Router::get('menu/info/{id:\d+}', 'App\Controller\System\MenuController@info');
    Router::post('menu/create', 'App\Controller\System\MenuController@create');
    Router::put('menu/update/{id:\d+}', 'App\Controller\System\MenuController@update');
    Router::delete('menu/delete/{id:\d+}', 'App\Controller\System\MenuController@delete');
    Router::post('menu/batch-delete', 'App\Controller\System\MenuController@batchDelete');
    Router::post('menu/assign-permissions', 'App\Controller\System\MenuController@assignPermissions');
    Router::post('menu/remove-permission', 'App\Controller\System\MenuController@removePermission');

    // 权限字典
    Router::get('permission/tree', 'App\Controller\System\PermissionController@tree');
    Router::get('permission/info/{id:\d+}', 'App\Controller\System\PermissionController@info');
    Router::get('permission/options', 'App\Controller\System\PermissionController@options');
    Router::post('permission/create', 'App\Controller\System\PermissionController@create');
    Router::put('permission/update/{id:\d+}', 'App\Controller\System\PermissionController@update');
    Router::delete('permission/delete/{id:\d+}', 'App\Controller\System\PermissionController@delete');
    Router::post('permission/batch-create', 'App\Controller\System\PermissionController@batchCreate');
    Router::post('permission/batch-update', 'App\Controller\System\PermissionController@batchUpdate');
    Router::post('permission/batch-delete', 'App\Controller\System\PermissionController@batchDelete');

    // 系统配置
    Router::get('config/list', 'App\Controller\System\ConfigController@list');
    Router::get('config/info/{id:\d+}', 'App\Controller\System\ConfigController@info');
    Router::post('config/create', 'App\Controller\System\ConfigController@create');
    Router::put('config/update/{id:\d+}', 'App\Controller\System\ConfigController@update');
    Router::delete('config/delete/{id:\d+}', 'App\Controller\System\ConfigController@delete');

    // 字典管理
    Router::get('dict/tree', 'App\Controller\System\DictController@tree');
    Router::get('dict/info/{id:\d+}', 'App\Controller\System\DictController@info');
    Router::get('dict/items/{dict_id:\d+}', 'App\Controller\System\DictController@items');
    Router::post('dict/create', 'App\Controller\System\DictController@create');
    Router::put('dict/update/{id:\d+}', 'App\Controller\System\DictController@update');
    Router::delete('dict/delete/{id:\d+}', 'App\Controller\System\DictController@delete');

    // IP 黑名单
    Router::get('ip-blacklist/list', 'App\Controller\System\IpBlacklistController@list');
    Router::post('ip-blacklist/ban', 'App\Controller\System\IpBlacklistController@ban');
    Router::post('ip-blacklist/unban', 'App\Controller\System\IpBlacklistController@unban');
    Router::post('ip-blacklist/check', 'App\Controller\System\IpBlacklistController@check');

    // 附件管理
    Router::get('attachment/list', 'App\Controller\System\AttachmentController@list');
    Router::post('attachment/upload', 'App\Controller\System\AttachmentController@upload');
    Router::put('attachment/update/{id:\d+}', 'App\Controller\System\AttachmentController@update');
    Router::delete('attachment/delete/{id:\d+}', 'App\Controller\System\AttachmentController@delete');

    // 定时任务
    Router::get('cron-job/list', 'App\Controller\System\CronJobController@list');
    Router::get('cron-job/info/{id:\d+}', 'App\Controller\System\CronJobController@info');
    Router::post('cron-job/create', 'App\Controller\System\CronJobController@create');
    Router::put('cron-job/update/{id:\d+}', 'App\Controller\System\CronJobController@update');
    Router::delete('cron-job/delete/{id:\d+}', 'App\Controller\System\CronJobController@delete');
    Router::post('cron-job/run/{id:\d+}', 'App\Controller\System\CronJobController@run');
    Router::post('cron-job/batch-update', 'App\Controller\System\CronJobController@batchUpdate');

    // 邮件管理
    Router::post('email/send', 'App\Controller\Notify\EmailController@send');
    Router::get('email/records', 'App\Controller\Notify\EmailController@sendRecords');
    Router::post('email/test', 'App\Controller\Notify\EmailController@testSend');
    Router::get('email/template/list', 'App\Controller\Notify\EmailController@templateList');
    Router::post('email/template/create', 'App\Controller\Notify\EmailController@templateCreate');
    Router::put('email/template/update/{id:\d+}', 'App\Controller\Notify\EmailController@templateUpdate');
    Router::delete('email/template/delete/{id:\d+}', 'App\Controller\Notify\EmailController@templateDelete');

    // 通知公告
    Router::post('notice/create', 'App\Controller\Notify\NoticeController@create');
    Router::put('notice/update/{id:\d+}', 'App\Controller\Notify\NoticeController@update');
    Router::delete('notice/delete/{id:\d+}', 'App\Controller\Notify\NoticeController@delete');
    Router::post('notice/publish', 'App\Controller\Notify\NoticeController@publish');
    Router::post('notice/withdraw', 'App\Controller\Notify\NoticeController@withdraw');
    Router::post('notice/set-targets', 'App\Controller\Notify\NoticeController@setTargets');
    Router::post('notice/mark-read', 'App\Controller\Notify\NoticeController@markRead');
    Router::get('notice/user-notices', 'App\Controller\Notify\NoticeController@userNotices');
    Router::get('notice/unread-count', 'App\Controller\Notify\NoticeController@unreadCount');
    Router::get('notice/statistics', 'App\Controller\Notify\NoticeController@statistics');
    Router::post('notice/batch-push', 'App\Controller\Notify\NoticeController@batchPush');

    // 会话管理
    Router::get('session/list', 'App\Controller\Ops\SessionController@list');
    Router::get('session/info/{id:\d+}', 'App\Controller\Ops\SessionController@info');
    Router::post('session/kickout', 'App\Controller\Ops\SessionController@kickout');
    Router::post('session/batch-kickout', 'App\Controller\Ops\SessionController@batchKickout');
    Router::post('session/clean', 'App\Controller\Ops\SessionController@clean');

    // 缓存管理
    Router::get('cache/modules', 'App\Controller\System\CacheController@modules');
    Router::post('cache/clear', 'App\Controller\System\CacheController@clear');

    // 日志
    Router::get('log/login', 'App\Controller\Ops\LogController@loginLog');
    Router::get('log/operation', 'App\Controller\Ops\LogController@operationLog');
}, ['middleware' => [JwtAuthMiddleware::class, PermissionMiddleware::class]]);
