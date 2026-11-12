import { useQuery } from "@tanstack/react-query";
import type { ReactNode } from "react";

import { getMyPermissions } from "@/api/profile";
import { useAuthStore } from "@/stores/auth";

/**
 * 权限判断。超管恒为 true。
 *
 * 权限码来自 GET /api/auth/permissions，登录后由 usePermissionSync()
 * 拉一次写进 store；这里同步读取 store，保证组件渲染期间结果稳定。
 */
export function hasPermission(code: string): boolean {
  const admin = useAuthStore.getState().admin;
  if (!admin) {
    return false;
  }
  if (admin.is_admin === 1) {
    return true;
  }
  return useAuthStore.getState().permissionCodes.includes(code);
}

/** 响应式版本，权限码变化后组件会跟着重渲染 */
export function usePermission(code: string): boolean {
  const admin = useAuthStore((state) => state.admin);
  const codes = useAuthStore((state) => state.permissionCodes);
  if (!admin) {
    return false;
  }
  return admin.is_admin === 1 || codes.includes(code);
}

/** 是否超级管理员。少数接口（如清缓存）只对超管开放，光有权限码不够 */
export function useIsSuperAdmin(): boolean {
  const admin = useAuthStore((state) => state.admin);
  return admin?.is_admin === 1;
}

/** 是否至少拥有其中一个权限码 */
export function useAnyPermission(codes: string[]): boolean {
  const admin = useAuthStore((state) => state.admin);
  const owned = useAuthStore((state) => state.permissionCodes);
  if (!admin) {
    return false;
  }
  return admin.is_admin === 1 || codes.some((code) => owned.includes(code));
}

/**
 * 登录后把权限码写进 store。
 *
 * 挂在后台布局里调用一次即可；未登录时不发请求。
 */
export function usePermissionSync(): void {
  const admin = useAuthStore((state) => state.admin);
  const setPermissionCodes = useAuthStore((state) => state.setPermissionCodes);

  useQuery({
    queryKey: ["auth", "permissions"],
    queryFn: async () => {
      const result = await getMyPermissions();
      setPermissionCodes(result.permission_codes);
      return result;
    },
    enabled: admin !== null,
    staleTime: 5 * 60_000,
  });
}

export function Can({
  code,
  children,
  fallback = null,
}: {
  code: string;
  children: ReactNode;
  fallback?: ReactNode;
}) {
  return <>{usePermission(code) ? children : fallback}</>;
}
