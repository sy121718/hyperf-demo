-- 「我的」列表接口的权限点与菜单
--
-- 全部列表（notice:list 等）与「我的」列表是两条独立接口，
-- 各自一个权限码，普通管理员只发「我的」权限即可进入对应页面。
-- 前端用同一个页面，按权限切换数据源。

SET NAMES utf8mb4;

INSERT INTO `sys_permission_dict`
    (`perm_name`, `perm_code`, `parent_id`, `api_path`, `http_method`, `is_public`, `status`, `create_by`, `create_time`, `update_by`, `update_time`)
VALUES
    ('我的公告', 'notice:myList', 0, '/api/notice/my-list', 'GET', 0, 1, 0, NOW(3), 0, NOW(3)),
    ('我的附件', 'attachment:myList', 0, '/api/attachment/my-list', 'GET', 0, 1, 0, NOW(3), 0, NOW(3)),
    ('我的会话', 'session:myList', 0, '/api/session/my-list', 'GET', 0, 1, 0, NOW(3), 0, NOW(3)),
    ('我的登录日志', 'log:myLoginLog', 0, '/api/log/my-login', 'GET', 0, 1, 0, NOW(3), 0, NOW(3)),
    ('我的操作日志', 'log:myOperationLog', 0, '/api/log/my-operation', 'GET', 0, 1, 0, NOW(3), 0, NOW(3)),
    ('我的发送记录', 'email:mySendRecords', 0, '/api/email/my-records', 'GET', 0, 1, 0, NOW(3), 0, NOW(3)),
    ('当前用户权限', 'auth:permissions', 0, '/api/auth/permissions', 'GET', 0, 1, 0, NOW(3), 0, NOW(3)),
    ('公告详情', 'notice:info', 0, '/api/notice/info/{id}', 'GET', 0, 1, 0, NOW(3), 0, NOW(3))
ON DUPLICATE KEY UPDATE `api_path` = VALUES(`api_path`), `http_method` = VALUES(`http_method`);

-- 超管角色补上这些权限码（其余角色按需分配）
-- 用 JSON_MERGE_PRESERVE 合并后去重，重复执行不会产生重复项
UPDATE `sys_role`
SET `permission_codes` = (
    SELECT JSON_ARRAYAGG(`code`)
    FROM (
        SELECT DISTINCT `code`
        FROM JSON_TABLE(
            JSON_MERGE_PRESERVE(
                COALESCE(`permission_codes`, JSON_ARRAY()),
                JSON_ARRAY(
                    'notice:myList', 'attachment:myList', 'session:myList',
                    'log:myLoginLog', 'log:myOperationLog', 'email:mySendRecords',
                    'auth:permissions', 'notice:info'
                )
            ),
            '$[*]' COLUMNS (`code` VARCHAR(100) PATH '$')
        ) AS codes
    ) AS deduped
)
WHERE `role_code` = 'super_admin';
