import { http } from "@/lib/http";
import type { PageResult } from "@/types/api";
import type {
  AdminCreatePayload,
  AdminDetail,
  AdminPermissionsResult,
  AdminRolesResult,
  AdminRow,
  AdminSaved,
  AdminUpdatePayload,
} from "@/types/admin";
import type { ListQuery } from "@/lib/list";

export function getAdminList(params: ListQuery) {
  return http.get<PageResult<AdminRow>>("/admin/list", { params });
}

export function getAdminInfo(id: number) {
  return http.get<AdminDetail>(`/admin/info/${id}`);
}

export function createAdmin(payload: AdminCreatePayload) {
  return http.post<AdminSaved>("/admin/create", payload);
}

export function updateAdmin(id: number, payload: AdminUpdatePayload) {
  return http.put<AdminSaved>(`/admin/update/${id}`, payload);
}

export function deleteAdmin(id: number) {
  return http.delete<null>(`/admin/delete/${id}`);
}

export function resetAdminPassword(adminId: number, newPassword: string) {
  return http.post<null>("/admin/reset-password", {
    admin_id: adminId,
    new_password: newPassword,
  });
}

export function banAdmin(id: number) {
  return http.put<null>(`/admin/ban/${id}`);
}

export function unbanAdmin(id: number) {
  return http.put<null>(`/admin/unban/${id}`);
}

export function resetAdminFailure(id: number) {
  return http.put<null>(`/admin/reset-failure/${id}`);
}

export function getAdminRoles(id: number) {
  return http.get<AdminRolesResult>(`/admin/roles/${id}`);
}

export function getAdminPermissions(id: number) {
  return http.get<AdminPermissionsResult>(`/admin/permissions/${id}`);
}
