-- Migration: Simplify RBAC Code Structure (Continued)
-- Date: 2026-03-30
-- Description: Complete sys_admin_role restructure and drop intermediate tables
-- Note: Steps 1-4 already completed

-- 5. Modify sys_admin_role table (drop old columns, add new code-based columns)
ALTER TABLE sys_admin_role DROP COLUMN admin_id;
ALTER TABLE sys_admin_role DROP COLUMN role_id;
ALTER TABLE sys_admin_role ADD COLUMN admin_code VARCHAR(50) NOT NULL AFTER id;
ALTER TABLE sys_admin_role ADD COLUMN role_code VARCHAR(50) NOT NULL AFTER admin_code;
ALTER TABLE sys_admin_role ADD INDEX idx_admin_role_code (admin_code, role_code);

-- 6. Drop intermediate tables
DROP TABLE IF EXISTS sys_menu_permission;
DROP TABLE IF EXISTS sys_role_menu;
