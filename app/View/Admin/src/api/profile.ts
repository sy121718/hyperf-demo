import { http } from "@/lib/http";
import type { AdminInfo } from "@/types/auth";

/** GET /api/auth/profile：含 roles，但不含密码 */
export interface ProfileResult extends AdminInfo {
  roles: Array<{ id: number; role_name: string; role_code: string }>;
}

export function getProfile() {
  return http.get<ProfileResult>("/auth/profile");
}

/** 当前用户的权限码，供按钮显隐与列表视图切换使用 */
export interface MyPermissionsResult {
  admin_id: number;
  is_admin: number;
  permission_codes: string[];
}

export function getMyPermissions() {
  return http.get<MyPermissionsResult>("/auth/permissions");
}

export interface ProfileUpdatePayload {
  name?: string;
  email?: string;
  phone?: string;
  avatar?: string;
}

export function updateProfile(payload: ProfileUpdatePayload) {
  return http.put<null>("/auth/profile", payload);
}

export function changePassword(oldPassword: string, newPassword: string) {
  return http.post<null>("/auth/change-password", {
    old_password: oldPassword,
    new_password: newPassword,
  });
}
