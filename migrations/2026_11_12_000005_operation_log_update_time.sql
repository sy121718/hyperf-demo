-- Migration: 操作日志表补齐更新时间字段
-- Date: 2026-11-12
-- Description: sys_operation_logs 增加 update_by / update_time，与其余业务表对齐
--
-- 为什么补字段而不是改模型绕开：
--   SysOperationLogModel 声明了 UPDATED_AT = null，框架的 getDates() 会把
--   null 当成一个日期字段放进数组（array_unique 不会去掉它），
--   toArray() 遍历时执行 $attributes[null]，PHP 8.5 触发
--   "Using null as an array offset is deprecated"，被全局 ErrorExceptionHandler
--   转成异常 —— 只要表里有数据，操作日志列表接口就直接 500。
--
--   该表其余字段（create_by / create_time）都遵循同一套命名，缺 update_* 是遗漏，
--   补字段后模型可以沿用基类的标准约定，不需要在模型里覆写 getDates()。
--   日志虽然只写一次，但 update_time 与 update_by 保持与其它表一致的形状，
--   便于统一处理（例如后续要做归档/重放）。

ALTER TABLE `sys_operation_logs`
    ADD COLUMN `update_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新人ID' AFTER `create_by`,
    ADD COLUMN `update_time` DATETIME(3) DEFAULT NULL COMMENT '更新时间' AFTER `create_time`;
