-- Migration: 超管唯一约束
-- Date: 2026-11-12
-- Description: 用生成列 + 唯一索引保证全库最多一个未删除的超级管理员
--
-- 为什么放数据库层：
--   is_admin 是普通字段，代码里有多条写入路径（create / update / 未来的批量操作），
--   只靠 Service 判断无法保证不会出现第二个超管。索引是最后一道强约束。
--
-- 为什么排除软删除：
--   超管被软删后不该继续占用名额，否则删掉旧超管就再也无法创建新的。
--   生成列在 deleted_time 非空时取 NULL，而唯一索引允许多个 NULL。

ALTER TABLE sys_admin
    ADD COLUMN super_slot TINYINT
        GENERATED ALWAYS AS (IF(is_admin = 1 AND deleted_time IS NULL, 1, NULL)) STORED
        COMMENT '超管唯一槽位：未删除的超管为 1，其余为 NULL';

ALTER TABLE sys_admin
    ADD UNIQUE KEY uk_super_slot (super_slot);
