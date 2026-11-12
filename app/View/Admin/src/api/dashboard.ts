import { http } from "@/lib/http";
import type { DashboardOverview } from "@/types/dashboard";

/**
 * 概览页指标。
 *
 * 后端一个接口返回全部卡片，且已按当前用户权限过滤——无权限的模块不返回。
 * 前端不做二次判断，只渲染拿到的卡片。
 */
export function getDashboardOverview() {
  return http.get<DashboardOverview>("/dashboard/overview");
}
