import { http } from "@/lib/http";
import type { LoginPayload, LoginResult } from "@/types/auth";
import type { MenuNode } from "@/types/menu";

export function login(payload: LoginPayload) {
  return http.post<LoginResult>("/auth/login", payload);
}

export function logout() {
  return http.post<null>("/auth/logout");
}

/** GET /api/admin/menu：当前用户可见的菜单树，用于生成动态路由 */
export function getMenus() {
  return http.get<MenuNode[]>("/admin/menu");
}
