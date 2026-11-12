/**
 * 概览页指标卡片。
 *
 * 后端按当前用户的权限码决定返回哪些卡片：没有权限的模块整卡不下发，
 * 前端只渲染拿到的数据，不需要再做权限判断（也不该做——判定口径
 * 应与接口鉴权一致，放在后端才不会有偏差）。
 */
export interface DashboardCard {
  /** 稳定标识，前端用它选图标 */
  key: DashboardCardKey;
  title: string;
  value: number;
  /** 点击卡片跳转的后端菜单路径，如 /system/admin */
  path: string;
}

export type DashboardCardKey =
  | "admin_total"
  | "role_total"
  | "session_total"
  | "notice_unread"
  | "menu_total"
  | "permission_total"
  | "operation_today"
  | "login_failed_today";

/** GET /api/dashboard/overview 的返回 */
export interface DashboardOverview {
  cards: DashboardCard[];
}
