import type { ReactNode } from "react";

import { useAuthStore } from "@/stores/auth";

/**
 * 权限判断。超管恒为 true。
 *
 * 后端目前没有「当前用户权限码」接口（见 app/View/CLAUDE.md 8.2），
 * 非超管在接口补齐前拿不到权限码，这里只按 is_admin 放行，
 * 按钮级隐藏等后端补齐后用同一函数即可，调用点无需改动。
 */
export function hasPermission(code: string): boolean {
  const admin = useAuthStore((state) => state.admin);
  if (!admin) {
    return false;
  }
  if (admin.is_admin === 1) {
    return true;
  }
  return useAuthStore.getState().permissionCodes.includes(code);
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
  return <>{hasPermission(code) ? children : fallback}</>;
}
