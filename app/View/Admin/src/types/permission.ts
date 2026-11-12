import type { Switch } from "@/types/api";

/** 权限树节点（PermissionResponse::listFields） */
export interface PermissionTreeNode {
  id: number;
  perm_name: string;
  perm_code: string;
  parent_id: number;
  api_path: string | null;
  http_method: string | null;
  is_public: Switch;
  route_priority: number;
  status: Switch;
  children?: PermissionTreeNode[];
}

/** GET /api/permission/info/{id} 的返回 */
export interface PermissionDetail {
  id: number;
  perm_name: string;
  perm_code: string;
  parent_id: number;
  api_path: string | null;
  http_method: string | null;
  controller_method: string | null;
  is_public: Switch;
  rate_limit: number;
  route_priority: number;
  remark: string | null;
  status: Switch;
  create_time: string | null;
  update_time: string | null;
}

/** GET /api/permission/options 的返回 */
export interface PermissionOption {
  id: number;
  perm_name: string;
  perm_code: string;
  parent_id: number;
}

export interface PermissionSaved {
  id: number;
  perm_code: string;
  perm_name: string;
}

export interface PermissionPayload {
  perm_code?: string;
  perm_name?: string;
  parent_id?: number;
  api_path?: string;
  http_method?: string;
  controller_method?: string;
  is_public?: Switch;
  rate_limit?: number;
  route_priority?: number;
  remark?: string;
  status?: Switch;
}

/** POST /api/permission/batch-delete 的返回 */
export interface BatchDeleteResult {
  deleted: number;
}
