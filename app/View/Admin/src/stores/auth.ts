import { create } from "zustand";

import type { AdminInfo } from "@/types/auth";
import { clearToken, getToken, setToken } from "@/lib/auth";

interface AuthState {
  admin: AdminInfo | null;
  /** 权限码集合，后端补齐「当前用户权限」接口后写入 */
  permissionCodes: string[];
  setSession: (token: string, admin: AdminInfo) => void;
  setPermissionCodes: (codes: string[]) => void;
  reset: () => void;
}

/**
 * 登录态。token 存在 localStorage（刷新后仍在），admin 信息只放内存，
 * 刷新页面时由路由守卫重新拉取。
 */
export const useAuthStore = create<AuthState>((set) => ({
  admin: null,
  permissionCodes: [],
  setSession: (token, admin) => {
    setToken(token);
    set({ admin });
  },
  setPermissionCodes: (codes) => set({ permissionCodes: codes }),
  reset: () => {
    clearToken();
    set({ admin: null, permissionCodes: [] });
  },
}));

export function isLoggedIn(): boolean {
  return getToken() !== null;
}
