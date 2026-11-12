import { http } from "@/lib/http";
import type {
  BatchDeleteResult,
  PermissionDetail,
  PermissionOption,
  PermissionPayload,
  PermissionSaved,
  PermissionTreeNode,
} from "@/types/permission";
import type { ListQuery } from "@/lib/list";

export function getPermissionTree(params: ListQuery) {
  return http.get<PermissionTreeNode[]>("/permission/tree", { params });
}

export function getPermissionInfo(id: number) {
  return http.get<PermissionDetail>(`/permission/info/${id}`);
}

export function getPermissionOptions() {
  return http.get<PermissionOption[]>("/permission/options");
}

export function createPermission(payload: PermissionPayload) {
  return http.post<PermissionSaved>("/permission/create", payload);
}

export function updatePermission(id: number, payload: PermissionPayload) {
  return http.put<PermissionSaved>(`/permission/update/${id}`, payload);
}

export function deletePermission(id: number) {
  return http.delete<null>(`/permission/delete/${id}`);
}

export function batchDeletePermissions(ids: number[]) {
  return http.post<BatchDeleteResult>("/permission/batch-delete", { ids });
}

/** 批量创建：已存在的 perm_code 会被后端跳过，返回值里带跳过清单 */
export function batchCreatePermissions(items: PermissionPayload[]) {
  return http.post<{ created_count: number; skipped_codes: string[] }>(
    "/permission/batch-create",
    { items },
  );
}

export function batchUpdatePermissions(items: PermissionPayload[]) {
  return http.post<{ updated_count: number }>("/permission/batch-update", { items });
}
