import type { Switch } from "@/types/api";

/**
 * 管理员列表行（GET /api/admin/list 的 list 项）。
 *
 * 字段对应 AdminResponse::listFields()，接口出参保持 snake_case。
 */
export interface AdminRow {
  id: number;
  username: string;
  name: string | null;
  email: string | null;
  phone: string | null;
  avatar: string | null;
  status: Switch;
  is_admin: Switch;
  last_login_ip: string | null;
  last_login_time: string | null;
  create_time: string | null;
}

/** GET /api/admin/info/{id} 的返回 */
export interface AdminDetail extends AdminRow {
  admin_code: string;
  last_login_location: string | null;
  last_login_isp: string | null;
  locked_until_time: string | null;
  update_time: string | null;
  roles: RoleBrief[];
}

/** 管理员关联的角色（belongsToMany sys_role） */
export interface RoleBrief {
  id: number;
  role_code: string;
  role_name: string;
}

/** 写操作回执（AdminResponse::saved） */
export interface AdminSaved {
  id: number;
  username: string;
  admin_code: string;
}

export interface AdminCreatePayload {
  username: string;
  password: string;
  name?: string;
  email?: string;
  phone?: string;
  avatar?: string;
  status?: Switch;
  role_ids?: number[];
}

export interface AdminUpdatePayload {
  username?: string;
  password?: string;
  name?: string;
  email?: string;
  phone?: string;
  avatar?: string;
  status?: Switch;
  is_admin?: Switch;
  role_ids?: number[];
}

/** GET /api/admin/roles/{id} 的返回 */
export interface AdminRolesResult {
  admin_id: number;
  roles: RoleBrief[];
}

/** GET /api/admin/permissions/{id} 的返回 */
export interface AdminPermissionsResult {
  admin_id: number;
  admin_code: string;
  roles: Array<{
    role_code: string;
    role_name: string;
    permission_codes: string[];
  }>;
  permission_codes: string[];
}
