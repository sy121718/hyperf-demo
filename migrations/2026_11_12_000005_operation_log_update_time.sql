-- Migration: 操作日志表补齐更新时间字段
-- Date: 2026-11-12
-- Description: sys_operation_logs 增加 update_time
--
-- 为什么补字段而不是改模型绕开：
--   SysOperationLogModel 声明了 UPDATED_AT = null，框架的 getDates() 会把
--   null 当成一个日期字段放进数组（array_unique 不会去掉它），
--   toArray() 遍历时执行 $attributes[null]，PHP 8.5 触发
--   "Using null as an array offset is deprecated"，被全局 ErrorExceptionHandler
--   转成异常 —— 只要表里有数据，操作日志列表接口就直接 500。
--
--   补上 update_time 后模型可以沿用基类的标准约定（CREATED_AT / UPDATED_AT），
--   不需要在模型里覆写 getDates()。
--
-- 只补 update_time，不补 update_by：
--   操作日志是只写一次的事件记录，不存在「谁更新了这条日志」，
--   update_by 永远是 NULL，属于无用列。update_time 保留是因为框架的
--   UPDATED_AT 约定需要它存在（即使实际从不更新）。

ALTER TABLE `sys_operation_logs`
    ADD COLUMN `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间' AFTER `create_time`;
