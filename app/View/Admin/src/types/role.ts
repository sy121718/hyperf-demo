import type { Switch } from "@/types/api";

/** 角色列表行（RoleResponse::listFields） */
export interface RoleRow {
  id: number;
  role_code: string;
  role_name: string;
  status: Switch;
  sort_order: number;
  remark: string | null;
  create_time: string | null;
}

/** GET /api/role/info/{id} 的返回 */
export interface RoleDetail extends RoleRow {
  role_type: string;
  /** 库里是 json 列，模型已 cast 成数组 */
  permission_codes: string[] | null;
  update_time: string | null;
}

export interface RoleSaved {
  id: number;
  role_code: string;
  role_name: string;
}

/** GET /api/role/options 的返回（下拉用） */
export interface RoleOption {
  value: number;
  label: string;
  code: string;
}

/** GET /api/role/all 的返回 */
export interface RoleBrief {
  id: number;
  role_name: string;
  role_code: string;
  remark: string | null;
  status: Switch;
}

export interface RoleCreatePayload {
  role_code: string;
  role_name: string;
  role_type?: string;
  sort_order?: number;
  status?: Switch;
  remark?: string;
  permission_codes?: string[];
}

export interface RoleUpdatePayload {
  role_name?: string;
  role_code?: string;
  role_type?: string;
  sort_order?: number;
  status?: Switch;
  remark?: string;
  permission_codes?: string[];
}
