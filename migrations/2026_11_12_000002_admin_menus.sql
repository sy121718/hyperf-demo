-- 后台菜单：给每个模块目录挂上可路由页面
--
-- 种子里的 15 条记录全是 type=0 的目录，没有 path/component，
-- 前端据此生成不出任何路由。这里按后端接口补齐 1 类菜单，
-- component 与 app/View/Admin/src/pages 下的目录一一对应。
-- 父级 ID 由 2026_11_10_000002_seed_rbac.sql 显式指定为 1..15。
-- 全部按 menu_code 幂等写入，可重复执行。

SET NAMES utf8mb4;

-- 新增权限点：公告列表（管理端全量，含草稿与已撤回）
INSERT INTO `sys_permission_dict`
    (`perm_name`, `perm_code`, `parent_id`, `api_path`, `http_method`, `is_public`, `status`, `create_by`, `create_time`, `update_by`, `update_time`)
VALUES ('公告列表', 'notice:list', 0, '/api/notice/list', 'GET', 0, 1, 0, NOW(3), 0, NOW(3))
ON DUPLICATE KEY UPDATE `api_path` = VALUES(`api_path`), `http_method` = VALUES(`http_method`);

INSERT INTO `sys_menus`
    (`menu_code`, `menu_name`, `parent_id`, `type`, `title`, `path`, `component`, `icon`, `status`, `is_hidden`, `is_public`, `permission_code`, `sort_order`, `create_by`, `create_time`)
VALUES
    -- 管理员（目录 1）
    ('admin_list', '管理员列表', 1, 1, '管理员列表', '/system/admin', 'system/admin/index', 'user-round', 1, 0, 0, 'admin:list', 10, 0, NOW(3)),
    -- 附件（目录 2）
    ('attachment_list', '附件列表', 2, 1, '附件列表', '/system/attachment', 'system/attachment/index', 'file-text', 1, 0, 0, 'attachment:list', 10, 0, NOW(3)),
    -- 个人中心（目录 3）
    ('auth_profile', '个人中心', 3, 1, '个人中心', '/system/profile', 'system/profile/index', 'user-cog', 1, 0, 0, 'auth:profile', 10, 0, NOW(3)),
    -- 缓存（目录 4）
    ('cache_index', '缓存管理', 4, 1, '缓存管理', '/system/cache', 'system/cache/index', 'database', 1, 0, 0, 'cache:modules', 10, 0, NOW(3)),
    -- 系统配置（目录 5）
    ('config_list', '系统配置', 5, 1, '系统配置', '/system/config', 'system/config/index', 'settings', 1, 0, 0, 'config:list', 10, 0, NOW(3)),
    -- 定时任务（目录 6）
    ('cronjob_list', '定时任务', 6, 1, '定时任务', '/system/cron-job', 'system/cron-job/index', 'timer', 1, 0, 0, 'cron-job:list', 10, 0, NOW(3)),
    -- 字典（目录 7）
    ('dict_list', '字典管理', 7, 1, '字典管理', '/system/dict', 'system/dict/index', 'book-open', 1, 0, 0, 'dict:tree', 10, 0, NOW(3)),
    -- 邮件（目录 8）：发送 / 记录 / 模板
    ('email_send', '发送邮件', 8, 1, '发送邮件', '/notify/email/send', 'notify/email/send/index', 'mail', 1, 0, 0, 'email:send', 10, 0, NOW(3)),
    ('email_records', '发送记录', 8, 1, '发送记录', '/notify/email/records', 'notify/email/records/index', 'send', 1, 0, 0, 'email:sendRecords', 20, 0, NOW(3)),
    ('email_template', '邮件模板', 8, 1, '邮件模板', '/notify/email/template', 'notify/email/template/index', 'layout-template', 1, 0, 0, 'email:template-list', 30, 0, NOW(3)),
    -- IP 黑名单（目录 9）
    ('ip_list', 'IP 黑名单', 9, 1, 'IP 黑名单', '/system/ip-blacklist', 'system/ip-blacklist/index', 'shield-ban', 1, 0, 0, 'ip-blacklist:list', 10, 0, NOW(3)),
    -- 日志（目录 10）：登录 / 操作
    ('log_login', '登录日志', 10, 1, '登录日志', '/ops/log/login', 'ops/log/login/index', 'log-in', 1, 0, 0, 'log:loginLog', 10, 0, NOW(3)),
    ('log_operation', '操作日志', 10, 1, '操作日志', '/ops/log/operation', 'ops/log/operation/index', 'history', 1, 0, 0, 'log:operationLog', 20, 0, NOW(3)),
    -- 菜单（目录 11）
    ('menu_list', '菜单管理', 11, 1, '菜单管理', '/system/menu', 'system/menu/index', 'menu', 1, 0, 0, 'menu:tree', 10, 0, NOW(3)),
    -- 通知公告（目录 12）
    ('notice_list', '公告管理', 12, 1, '公告管理', '/notify/notice', 'notify/notice/index', 'megaphone', 1, 0, 0, 'notice:list', 10, 0, NOW(3)),
    -- 权限字典（目录 13）
    ('permission_list', '权限字典', 13, 1, '权限字典', '/system/permission', 'system/permission/index', 'key-round', 1, 0, 0, 'permission:tree', 10, 0, NOW(3)),
    -- 角色（目录 14）
    ('role_list', '角色管理', 14, 1, '角色管理', '/system/role', 'system/role/index', 'users', 1, 0, 0, 'role:list', 10, 0, NOW(3)),
    -- 会话（目录 15）
    ('session_list', '会话管理', 15, 1, '会话管理', '/ops/session', 'ops/session/index', 'monitor', 1, 0, 0, 'session:list', 10, 0, NOW(3))
ON DUPLICATE KEY UPDATE
    `menu_name` = VALUES(`menu_name`),
    `parent_id` = VALUES(`parent_id`),
    `type` = VALUES(`type`),
    `title` = VALUES(`title`),
    `path` = VALUES(`path`),
    `component` = VALUES(`component`),
    `icon` = VALUES(`icon`),
    `permission_code` = VALUES(`permission_code`),
    `sort_order` = VALUES(`sort_order`);

-- 一级目录补图标：侧边栏改为图标栏后，目录需要有图标才能区分
UPDATE `sys_menus` SET `icon` = 'users'        WHERE `menu_code` = 'admin_dir';
UPDATE `sys_menus` SET `icon` = 'paperclip'    WHERE `menu_code` = 'attachment_dir';
UPDATE `sys_menus` SET `icon` = 'user-cog'     WHERE `menu_code` = 'auth_dir';
UPDATE `sys_menus` SET `icon` = 'database'     WHERE `menu_code` = 'cache_dir';
UPDATE `sys_menus` SET `icon` = 'settings'     WHERE `menu_code` = 'config_dir';
UPDATE `sys_menus` SET `icon` = 'timer'        WHERE `menu_code` = 'cron-job_dir';
UPDATE `sys_menus` SET `icon` = 'book-open'    WHERE `menu_code` = 'dict_dir';
UPDATE `sys_menus` SET `icon` = 'mail'         WHERE `menu_code` = 'email_dir';
UPDATE `sys_menus` SET `icon` = 'shield-ban'   WHERE `menu_code` = 'ip-blacklist_dir';
UPDATE `sys_menus` SET `icon` = 'scroll-text'  WHERE `menu_code` = 'log_dir';
UPDATE `sys_menus` SET `icon` = 'menu'         WHERE `menu_code` = 'menu_dir';
UPDATE `sys_menus` SET `icon` = 'megaphone'    WHERE `menu_code` = 'notice_dir';
UPDATE `sys_menus` SET `icon` = 'key-round'    WHERE `menu_code` = 'permission_dir';
UPDATE `sys_menus` SET `icon` = 'shield-check' WHERE `menu_code` = 'role_dir';
UPDATE `sys_menus` SET `icon` = 'monitor'      WHERE `menu_code` = 'session_dir';
