import { create } from "zustand";

import type { AdminInfo } from "@/types/auth";
import { clearToken, getToken, setToken as persistToken } from "@/lib/auth";

interface AuthState {
  /** 当前 token，放进 store 是为了让路由能订阅登录态变化 */
  token: string | null;
  admin: AdminInfo | null;
  /** 权限码集合，登录后从 GET /api/auth/permissions 拉取 */
  permissionCodes: string[];
  setSession: (token: string, admin: AdminInfo) => void;
  /** 滑动续期时只换 token，不动 admin */
  setToken: (token: string) => void;
  /** 刷新后由 profile 接口补回管理员信息 */
  setAdmin: (admin: AdminInfo) => void;
  setPermissionCodes: (codes: string[]) => void;
  reset: () => void;
}

/**
 * 登录态。token 同时写 localStorage（刷新后仍在），
 * 内存里保留一份是为了让组件能响应登录/登出，而不必读 localStorage。
 */
export const useAuthStore = create<AuthState>((set) => ({
  token: getToken(),
  admin: null,
  permissionCodes: [],
  setSession: (token, admin) => {
    persistToken(token);
    set({ token, admin });
  },
  setToken: (token) => {
    persistToken(token);
    set({ token });
  },
  setAdmin: (admin) => set({ admin }),
  setPermissionCodes: (codes) => set({ permissionCodes: codes }),
  reset: () => {
    clearToken();
    set({ token: null, admin: null, permissionCodes: [] });
  },
}));

/** 判断是否已登录，组件里请用 useAuthStore((s) => s.token !== null) */
export function isLoggedIn(): boolean {
  return getToken() !== null;
}
