import type { NoticeStatistics } from "@/types/notice";

/**
 * 首页概览数据。
 *
 * 全部字段来自既有列表/统计接口，后端没有专门的 dashboard 接口，
 * 这里只是把多个调用的结果收拢成一个结构。
 */
export interface DashboardOverview {
  adminTotal: number;
  roleTotal: number;
  sessionTotal: number;
  unreadTotal: number;
  noticeStatistics: NoticeStatistics;
}
