import { getAdminList } from "@/api/admin";
import { getNoticeStatistics, getUnreadNoticeCount } from "@/api/notice";
import { getRoleList } from "@/api/role";
import { getSessionList } from "@/api/session";
import type { DashboardOverview } from "@/types/dashboard";

/**
 * 首页概览聚合。
 *
 * 后端没有 dashboard 专用接口，总数只能从各列表接口的 total 取。
 * 这里统一传 limit=1：分页响应里 total 与每页条数无关，只要总数就不必真拉数据，
 * 既少传网络负载也避免被后端每页条数上限影响。
 */
export async function getDashboardOverview(): Promise<DashboardOverview> {
  const [admins, roles, sessions, unread, statistics] = await Promise.all([
    getAdminList({ limit: 1 }),
    getRoleList({ limit: 1 }),
    getSessionList("all", { limit: 1 }),
    getUnreadNoticeCount(),
    getNoticeStatistics({}),
  ]);

  return {
    adminTotal: admins.total,
    roleTotal: roles.total,
    sessionTotal: sessions.total,
    unreadTotal: unread.count,
    noticeStatistics: statistics,
  };
}
