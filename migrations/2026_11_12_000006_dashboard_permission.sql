-- Migration: 概览接口的权限点
-- Date: 2026-11-12
-- Description: 登记 GET /api/dashboard/overview
--
-- 为什么要登记：PermissionMiddleware 对未登记的接口直接放行，
-- 但「直接放行」和「登记后授予」在审计上是两回事——未登记的接口
-- 在权限字典里查不到，排查鉴权问题时会被误判成漏配。
--
-- 授予给 super_admin 角色（其它角色按需在角色管理里勾选）：
-- 该接口本身只返回当前用户有权查看的卡片，即使放开也不泄露越权数据，
-- 但没有权限码就无法在角色管理界面里控制它的可见性。

SET NAMES utf8mb4;

INSERT INTO `sys_permission_dict`
    (`perm_name`, `perm_code`, `parent_id`, `api_path`, `http_method`, `is_public`, `status`, `create_by`, `create_time`, `update_by`, `update_time`)
VALUES
    ('概览数据', 'dashboard:overview', 0, '/api/dashboard/overview', 'GET', 0, 1, 0, NOW(3), 0, NOW(3))
ON DUPLICATE KEY UPDATE `api_path` = VALUES(`api_path`), `http_method` = VALUES(`http_method`);

-- 授予内置超级管理员角色
UPDATE `sys_role`
SET `permission_codes` = JSON_ARRAY_APPEND(`permission_codes`, '$', 'dashboard:overview'),
    `update_time` = NOW(3)
WHERE `role_code` = 'super_admin'
  AND JSON_CONTAINS(`permission_codes`, '"dashboard:overview"') = 0;
