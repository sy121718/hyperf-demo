-- Migration: 初始化建表（从 Model 反推）
-- Date: 2026-11-10
-- 说明: 仓库历史中从未提交过建表语句，此文件依据 app/Model 下的模型定义
--       及 app/Service 中的实际读写字段反推生成，用于在新库（hyper）中重建结构。
--       字段类型参考 base 库中残留的同名表结构（sys_admin / sys_menus / notice 等）。
-- 执行: mysql -h 127.0.0.1 -P 3306 -u root -proot hyper < migrations/2026_11_10_000001_init_schema.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 管理员
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_admin` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `username` VARCHAR(50) NOT NULL COMMENT '登录账号',
    `admin_code` VARCHAR(50) NOT NULL COMMENT '管理员标识码（角色关联用）',
    `password` VARCHAR(100) NOT NULL COMMENT '密码哈希',
    `name` VARCHAR(50) DEFAULT NULL COMMENT '姓名',
    `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像',
    `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态 0=禁用,1=启用',
    `is_admin` TINYINT NOT NULL DEFAULT 0 COMMENT '是否超级管理员 1=是',
    `login_failure_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '连续登录失败次数',
    `locked_until_time` DATETIME(3) DEFAULT NULL COMMENT '锁定截止时间',
    `metadata` JSON DEFAULT NULL COMMENT '扩展元数据',
    `last_failure_time` DATETIME(3) DEFAULT NULL COMMENT '最后失败时间',
    `register_ip` VARCHAR(50) DEFAULT NULL COMMENT '注册IP',
    `register_location` VARCHAR(100) DEFAULT NULL COMMENT '注册地',
    `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录IP',
    `last_login_location` VARCHAR(100) DEFAULT NULL COMMENT '最后登录地',
    `last_login_isp` VARCHAR(50) DEFAULT NULL COMMENT '最后登录运营商',
    `last_login_time` DATETIME(3) DEFAULT NULL COMMENT '最后登录时间',
    `create_by` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    `deleted_time` DATETIME(3) DEFAULT NULL COMMENT '软删除时间',
    `remark` VARCHAR(255) DEFAULT NULL COMMENT '备注',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_admin_code` (`admin_code`),
    KEY `idx_email` (`email`),
    KEY `idx_status` (`status`),
    KEY `idx_deleted_time` (`deleted_time`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '管理员表';

-- ============================================================
-- 角色
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_role` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `role_code` VARCHAR(50) NOT NULL COMMENT '角色标识',
    `role_name` VARCHAR(100) NOT NULL COMMENT '角色名称',
    `role_type` VARCHAR(20) NOT NULL DEFAULT '1' COMMENT '角色类型',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态 0=禁用,1=启用',
    `remark` VARCHAR(200) DEFAULT NULL COMMENT '备注',
    `permission_codes` JSON DEFAULT NULL COMMENT '权限码集合',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    `deleted_time` DATETIME(3) DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_role_code` (`role_code`),
    KEY `idx_status` (`status`),
    KEY `idx_deleted_time` (`deleted_time`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '角色表';

-- ============================================================
-- 管理员-角色关联（code 关联，无中间表 ID）
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_admin_role` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `admin_code` VARCHAR(50) NOT NULL COMMENT '管理员标识码',
    `role_code` VARCHAR(50) NOT NULL COMMENT '角色标识',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_admin_role` (`admin_code`, `role_code`),
    KEY `idx_admin_code` (`admin_code`),
    KEY `idx_role_code` (`role_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '管理员角色关联表';

-- ============================================================
-- 管理员登录会话
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_admin_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `admin_id` BIGINT UNSIGNED NOT NULL COMMENT '管理员ID',
    `device_type` VARCHAR(20) DEFAULT NULL COMMENT '设备类型 desktop/mobile/tablet',
    `device_info` VARCHAR(255) DEFAULT NULL COMMENT '设备信息（User-Agent）',
    `jwt_token` VARCHAR(1024) NOT NULL COMMENT 'JWT 令牌',
    `login_ip` VARCHAR(50) DEFAULT NULL COMMENT '登录IP',
    `location` VARCHAR(100) DEFAULT NULL COMMENT '登录地',
    `isp` VARCHAR(50) DEFAULT NULL COMMENT '运营商',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT '完整 User-Agent',
    `login_time` DATETIME(3) DEFAULT NULL COMMENT '登录时间',
    `last_active_time` DATETIME(3) DEFAULT NULL COMMENT '最后活跃时间',
    `expires_time` DATETIME(3) DEFAULT NULL COMMENT '过期时间',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态 0=失效,1=有效',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_jwt_token` (`jwt_token`(255)),
    KEY `idx_status` (`status`),
    KEY `idx_expires_time` (`expires_time`),
    KEY `idx_last_active_time` (`last_active_time`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '管理员登录会话表';

-- ============================================================
-- 菜单
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_menus` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `menu_code` VARCHAR(50) NOT NULL COMMENT '菜单标识码',
    `menu_name` VARCHAR(50) NOT NULL COMMENT '菜单名称（标识用）',
    `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级ID，0=顶级',
    `type` TINYINT NOT NULL DEFAULT 2 COMMENT '类型 0=目录,1=菜单,2=页面,3=按钮',
    `title` VARCHAR(50) DEFAULT NULL COMMENT '显示标题',
    `path` VARCHAR(255) DEFAULT NULL COMMENT '路由路径',
    `component` VARCHAR(255) DEFAULT NULL COMMENT '前端组件',
    `external_url` VARCHAR(300) DEFAULT NULL COMMENT '外链地址',
    `icon` VARCHAR(50) DEFAULT NULL COMMENT '图标',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态 0=禁用,1=启用',
    `is_hidden` TINYINT NOT NULL DEFAULT 0 COMMENT '是否隐藏',
    `is_public` TINYINT NOT NULL DEFAULT 0 COMMENT '是否公开',
    `permission_code` VARCHAR(100) DEFAULT NULL COMMENT '权限码（多个用逗号分隔）',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    `deleted_time` DATETIME(3) DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_menu_code` (`menu_code`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_status` (`status`),
    KEY `idx_permission_code` (`permission_code`),
    KEY `idx_deleted_time` (`deleted_time`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '菜单表';

-- ============================================================
-- 权限字典（权限码与接口绑定）
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_permission_dict` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `perm_name` VARCHAR(100) NOT NULL COMMENT '权限名称',
    `perm_code` VARCHAR(100) NOT NULL COMMENT '权限码',
    `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级ID',
    `api_path` VARCHAR(255) DEFAULT NULL COMMENT '接口路径',
    `http_method` VARCHAR(10) DEFAULT NULL COMMENT 'HTTP 方法',
    `controller_method` VARCHAR(255) DEFAULT NULL COMMENT '控制器方法',
    `is_public` TINYINT NOT NULL DEFAULT 0 COMMENT '是否公开接口',
    `rate_limit` INT NOT NULL DEFAULT 0 COMMENT '限流阈值',
    `route_priority` INT NOT NULL DEFAULT 0 COMMENT '路由优先级',
    `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态 0=禁用,1=启用',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_perm_code` (`perm_code`),
    KEY `idx_api_path` (`api_path`, `http_method`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_status` (`status`),
    KEY `idx_route_priority` (`route_priority`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '权限字典表';

-- ============================================================
-- 字典
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_dict` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `dict_name` VARCHAR(100) NOT NULL COMMENT '字典名称',
    `dict_code` VARCHAR(50) NOT NULL COMMENT '字典编码',
    `dict_type` VARCHAR(50) NOT NULL DEFAULT '1' COMMENT '字典类型',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_dict_code` (`dict_code`),
    KEY `idx_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '字典表';

CREATE TABLE IF NOT EXISTS `sys_dict_item` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `dict_id` BIGINT UNSIGNED NOT NULL COMMENT '所属字典ID',
    `item_label` VARCHAR(100) NOT NULL COMMENT '显示名称',
    `item_value` VARCHAR(100) NOT NULL COMMENT '值',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_dict_id` (`dict_id`),
    KEY `idx_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '字典项表';

-- ============================================================
-- 系统配置
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_config` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `group_key` VARCHAR(50) NOT NULL COMMENT '配置分组键名',
    `group_name` VARCHAR(100) NOT NULL COMMENT '配置分组名称',
    `config_data` JSON NOT NULL COMMENT '配置数据',
    `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_group_key` (`group_key`),
    KEY `idx_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '系统配置表';

-- ============================================================
-- 定时任务
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_cron_job` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `job_name` VARCHAR(100) NOT NULL COMMENT '任务名称',
    `job_category` VARCHAR(50) NOT NULL COMMENT '任务分类',
    `description` TEXT DEFAULT NULL COMMENT '任务描述',
    `cron_expression` VARCHAR(100) NOT NULL COMMENT 'Cron 表达式',
    `command` VARCHAR(500) NOT NULL COMMENT '执行命令',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `sort_order` INT DEFAULT NULL COMMENT '排序',
    `last_sync_time` DATETIME(3) DEFAULT NULL COMMENT '上次执行时间',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_job_category` (`job_category`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '定时任务表';

-- ============================================================
-- 文件分类
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_file_category` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `category_name` VARCHAR(100) NOT NULL COMMENT '分类名称',
    `category_code` VARCHAR(50) NOT NULL COMMENT '分类编码',
    `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父级ID',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    `icon` VARCHAR(50) DEFAULT NULL COMMENT '图标',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_category_code` (`category_code`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '文件分类表';

-- ============================================================
-- 附件
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_attachment` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `category_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '分类ID',
    `file_name` VARCHAR(255) NOT NULL COMMENT '文件名',
    `original_name` VARCHAR(255) DEFAULT NULL COMMENT '原始文件名',
    `file_path` VARCHAR(500) NOT NULL COMMENT '文件路径',
    `file_size` BIGINT NOT NULL COMMENT '文件大小（字节）',
    `file_type` VARCHAR(50) NOT NULL COMMENT '文件类型',
    `mime_type` VARCHAR(100) DEFAULT NULL COMMENT 'MIME 类型',
    `width` INT UNSIGNED DEFAULT NULL COMMENT '图片宽度',
    `height` INT UNSIGNED DEFAULT NULL COMMENT '图片高度',
    `storage_type` VARCHAR(50) NOT NULL DEFAULT 'local' COMMENT '存储类型',
    `storage_path` VARCHAR(500) DEFAULT NULL COMMENT '存储路径',
    `url` VARCHAR(500) DEFAULT NULL COMMENT '访问 URL',
    `md5` VARCHAR(32) DEFAULT NULL COMMENT '文件 MD5',
    `sha256` VARCHAR(64) DEFAULT NULL COMMENT '文件 SHA256',
    `extra_info` JSON DEFAULT NULL COMMENT '额外信息',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    `deleted_time` DATETIME(3) DEFAULT NULL COMMENT '软删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_file_type` (`file_type`),
    KEY `idx_storage_type` (`storage_type`),
    KEY `idx_status` (`status`),
    KEY `idx_md5` (`md5`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '附件表';

-- ============================================================
-- IP 黑名单
-- ============================================================
CREATE TABLE IF NOT EXISTS `ip_blacklist` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `ip_address` VARCHAR(45) NOT NULL COMMENT 'IP 地址',
    `error_type` VARCHAR(50) NOT NULL COMMENT '违规类型',
    `ban_reason` VARCHAR(500) DEFAULT NULL COMMENT '封禁原因',
    `ban_duration` INT NOT NULL COMMENT '封禁时长（秒）',
    `banned_time` DATETIME(3) NOT NULL COMMENT '封禁开始时间',
    `banned_until_time` DATETIME(3) NOT NULL COMMENT '封禁截止时间',
    `ban_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '累计封禁次数',
    `operator_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
    `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态 0=已过期,1=生效中,2=手动解除',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_ip` (`ip_address`),
    KEY `idx_status` (`status`),
    KEY `idx_banned_until_time` (`banned_until_time`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'IP 黑名单表';

-- ============================================================
-- 通知公告
-- ============================================================
CREATE TABLE IF NOT EXISTS `notice` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `title` VARCHAR(200) NOT NULL COMMENT '标题',
    `content` LONGTEXT DEFAULT NULL COMMENT '内容',
    `notice_type` TINYINT NOT NULL DEFAULT 1 COMMENT '通知类型',
    `is_top` TINYINT NOT NULL DEFAULT 0 COMMENT '是否置顶',
    `is_popup` TINYINT NOT NULL DEFAULT 0 COMMENT '是否弹窗',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态 0=草稿,1=已发布',
    `publish_time` DATETIME(3) DEFAULT NULL COMMENT '发布时间',
    `expire_time` DATETIME(3) DEFAULT NULL COMMENT '过期时间',
    `read_count` INT NOT NULL DEFAULT 0 COMMENT '阅读次数',
    `create_by` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_notice_type` (`notice_type`),
    KEY `idx_status` (`status`),
    KEY `idx_publish_time` (`publish_time`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '通知公告表';

CREATE TABLE IF NOT EXISTS `notice_target` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `notice_id` BIGINT UNSIGNED NOT NULL COMMENT '通知ID',
    `target_type` TINYINT NOT NULL COMMENT '目标类型',
    `target_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '目标ID',
    PRIMARY KEY (`id`),
    KEY `idx_notice_id` (`notice_id`),
    KEY `idx_target` (`target_type`, `target_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '通知目标表';

CREATE TABLE IF NOT EXISTS `notice_read` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `notice_id` BIGINT UNSIGNED NOT NULL COMMENT '通知ID',
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    `read_time` DATETIME(3) DEFAULT NULL COMMENT '阅读时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_notice_user` (`notice_id`, `user_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '通知阅读记录表';

-- ============================================================
-- 邮件
-- ============================================================
CREATE TABLE IF NOT EXISTS `email_template` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `template_name` VARCHAR(100) NOT NULL COMMENT '模板名称',
    `template_code` VARCHAR(50) NOT NULL COMMENT '模板编码',
    `subject` VARCHAR(200) NOT NULL COMMENT '邮件主题',
    `content` TEXT NOT NULL COMMENT '邮件内容',
    `template_type` VARCHAR(20) NOT NULL DEFAULT 'html' COMMENT '模板类型',
    `variables` JSON DEFAULT NULL COMMENT '变量定义',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_template_code` (`template_code`),
    KEY `idx_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '邮件模板表';

CREATE TABLE IF NOT EXISTS `email_send_record` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `template_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '模板ID',
    `subject` VARCHAR(200) NOT NULL COMMENT '邮件主题',
    `content` TEXT DEFAULT NULL COMMENT '邮件内容',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '状态',
    `total_recipients` INT NOT NULL DEFAULT 0 COMMENT '收件人总数',
    `success_count` INT NOT NULL DEFAULT 0 COMMENT '成功数',
    `failed_count` INT NOT NULL DEFAULT 0 COMMENT '失败数',
    `error_message` VARCHAR(500) DEFAULT NULL COMMENT '错误信息',
    `sent_time` DATETIME(3) DEFAULT NULL COMMENT '发送时间',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_template_id` (`template_id`),
    KEY `idx_status` (`status`),
    KEY `idx_sent_time` (`sent_time`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '邮件发送记录表';

CREATE TABLE IF NOT EXISTS `email_send_recipient` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `record_id` BIGINT UNSIGNED NOT NULL COMMENT '发送记录ID',
    `email` VARCHAR(100) NOT NULL COMMENT '收件人邮箱',
    `name` VARCHAR(50) DEFAULT NULL COMMENT '收件人名称',
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '发送状态',
    `error_message` VARCHAR(500) DEFAULT NULL COMMENT '错误信息',
    `sent_time` DATETIME(3) DEFAULT NULL COMMENT '发送时间',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_record_id` (`record_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '邮件收件人表';

-- ============================================================
-- 日志
-- ============================================================
CREATE TABLE IF NOT EXISTS `sys_login_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `user_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户ID',
    `username` VARCHAR(50) DEFAULT NULL COMMENT '用户名',
    `ip_address` VARCHAR(50) DEFAULT NULL COMMENT 'IP 地址',
    `location` VARCHAR(100) DEFAULT NULL COMMENT '归属地',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User-Agent',
    `device_type` VARCHAR(20) DEFAULT NULL COMMENT '设备类型',
    `login_time` DATETIME(3) DEFAULT NULL COMMENT '登录时间',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '结果 0=失败,1=成功',
    `msg` VARCHAR(255) DEFAULT NULL COMMENT '消息',
    `session_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '会话ID',
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_username` (`username`),
    KEY `idx_ip_address` (`ip_address`),
    KEY `idx_login_time` (`login_time`),
    KEY `idx_status` (`status`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '登录日志表';

CREATE TABLE IF NOT EXISTS `sys_operation_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `admin_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '管理员ID',
    `menu_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '菜单ID',
    `perm_dict_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '权限字典ID',
    `service_method` VARCHAR(255) DEFAULT NULL COMMENT '服务方法',
    `operation` VARCHAR(255) DEFAULT NULL COMMENT '操作描述',
    `api_path` VARCHAR(255) DEFAULT NULL COMMENT '接口路径',
    `http_method` VARCHAR(10) DEFAULT NULL COMMENT 'HTTP 方法',
    `request_data` JSON DEFAULT NULL COMMENT '请求数据',
    `response_data` JSON DEFAULT NULL COMMENT '响应数据',
    `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP 地址',
    `location` VARCHAR(100) DEFAULT NULL COMMENT '归属地',
    `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User-Agent',
    `result` TINYINT NOT NULL DEFAULT 1 COMMENT '结果 0=失败,1=成功',
    `content` TEXT DEFAULT NULL COMMENT '内容',
    `execution_time` INT NOT NULL DEFAULT 0 COMMENT '执行耗时（毫秒）',
    `create_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建人ID',
    `create_time` DATETIME(3) DEFAULT NULL COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_menu_id` (`menu_id`),
    KEY `idx_perm_dict_id` (`perm_dict_id`),
    KEY `idx_result` (`result`),
    KEY `idx_http_method` (`http_method`),
    KEY `idx_create_time` (`create_time`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '操作日志表';

SET FOREIGN_KEY_CHECKS = 1;
