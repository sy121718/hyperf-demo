-- 菜单改为按后端模块分组
--
-- 原来每个模块目录（admin_dir、role_dir…）各自成组，侧边栏上会出现
-- 十几个只有一个子项的分组。按后端 Controller 的划分收敛成三个一级分组：
--   System  → app/Controller/System
--   Ops     → app/Controller/Ops
--   Notify  → app/Controller/Notify
-- 原来的模块目录整体停用（软删），叶子菜单直接挂到一级分组下。
-- 按 menu_code 幂等执行。

SET NAMES utf8mb4;

-- 一级分组
INSERT INTO `sys_menus`
    (`menu_code`, `menu_name`, `parent_id`, `type`, `title`, `path`, `component`, `icon`, `status`, `is_hidden`, `is_public`, `permission_code`, `sort_order`, `create_by`, `create_time`)
VALUES
    ('system_group', '系统管理', 0, 0, '系统管理', NULL, NULL, 'settings', 1, 0, 0, NULL, 10, 0, NOW(3)),
    ('ops_group',    '运维管理', 0, 0, '运维管理', NULL, NULL, 'activity', 1, 0, 0, NULL, 20, 0, NOW(3)),
    ('notify_group', '通知中心', 0, 0, '通知中心', NULL, NULL, 'bell', 1, 0, 0, NULL, 30, 0, NOW(3))
ON DUPLICATE KEY UPDATE
    `menu_name` = VALUES(`menu_name`),
    `type` = VALUES(`type`),
    `title` = VALUES(`title`),
    `icon` = VALUES(`icon`),
    `sort_order` = VALUES(`sort_order`);

-- 系统管理
UPDATE `sys_menus` AS child
JOIN `sys_menus` AS parent ON parent.`menu_code` = 'system_group'
SET child.`parent_id` = parent.`id`,
    child.`sort_order` = CASE child.`menu_code`
        WHEN 'admin_list'      THEN 10
        WHEN 'role_list'       THEN 20
        WHEN 'menu_list'       THEN 30
        WHEN 'permission_list' THEN 40
        WHEN 'dict_list'       THEN 50
        WHEN 'config_list'     THEN 60
        WHEN 'cronjob_list'    THEN 70
        WHEN 'attachment_list' THEN 80
        WHEN 'ip_list'         THEN 90
        WHEN 'auth_profile'    THEN 100
    END
WHERE child.`menu_code` IN (
    'admin_list', 'role_list', 'menu_list', 'permission_list', 'dict_list',
    'config_list', 'cronjob_list', 'attachment_list', 'ip_list', 'auth_profile'
);

-- 运维管理
UPDATE `sys_menus` AS child
JOIN `sys_menus` AS parent ON parent.`menu_code` = 'ops_group'
SET child.`parent_id` = parent.`id`,
    child.`sort_order` = CASE child.`menu_code`
        WHEN 'session_list'  THEN 10
        WHEN 'log_login'     THEN 20
        WHEN 'log_operation' THEN 30
        WHEN 'cache_index'   THEN 40
    END
WHERE child.`menu_code` IN ('session_list', 'log_login', 'log_operation', 'cache_index');

-- 通知中心
UPDATE `sys_menus` AS child
JOIN `sys_menus` AS parent ON parent.`menu_code` = 'notify_group'
SET child.`parent_id` = parent.`id`,
    child.`sort_order` = CASE child.`menu_code`
        WHEN 'notice_list'    THEN 10
        WHEN 'email_send'     THEN 20
        WHEN 'email_records'  THEN 30
        WHEN 'email_template' THEN 40
    END
WHERE child.`menu_code` IN ('notice_list', 'email_send', 'email_records', 'email_template');

-- 停用原来的 15 个模块目录（软删，不物理删除，便于回滚）
UPDATE `sys_menus`
SET `deleted_time` = NOW(3)
WHERE `type` = 0
  AND `menu_code` IN (
    'admin_dir', 'attachment_dir', 'auth_dir', 'cache_dir', 'config_dir',
    'cron-job_dir', 'dict_dir', 'email_dir', 'ip-blacklist_dir', 'log_dir',
    'menu_dir', 'notice_dir', 'permission_dir', 'role_dir', 'session_dir'
  )
  AND `deleted_time` IS NULL;
