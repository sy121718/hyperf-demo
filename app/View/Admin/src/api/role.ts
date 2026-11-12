import { http } from "@/lib/http";
import type { PageResult } from "@/types/api";
import type {
  RoleBrief,
  RoleCreatePayload,
  RoleDetail,
  RoleOption,
  RoleRow,
  RoleSaved,
  RoleUpdatePayload,
} from "@/types/role";
import type { ListQuery } from "@/lib/list";

export function getRoleList(params: ListQuery) {
  return http.get<PageResult<RoleRow>>("/role/list", { params });
}

export function getRoleInfo(id: number) {
  return http.get<RoleDetail>(`/role/info/${id}`);
}

export function getRoleOptions() {
  return http.get<RoleOption[]>("/role/options");
}

export function getAllRoles() {
  return http.get<RoleBrief[]>("/role/all");
}

export function createRole(payload: RoleCreatePayload) {
  return http.post<RoleSaved>("/role/create", payload);
}

export function updateRole(id: number, payload: RoleUpdatePayload) {
  return http.put<RoleSaved>(`/role/update/${id}`, payload);
}

export function deleteRole(id: number) {
  return http.delete<null>(`/role/delete/${id}`);
}

export function batchDeleteRoles(ids: number[]) {
  return http.post<{ deleted: number }>("/role/batch-delete", { ids });
}

/** role_code 是关联键，菜单分配接口按角色标识而不是 ID 提交 */
export function assignRoleMenus(roleCode: string, menuIds: number[]) {
  return http.post<{ role_code: string; menu_ids: number[]; permission_codes: string[] }>(
    "/role/assign-menus",
    { role_code: roleCode, menu_ids: menuIds },
  );
}
