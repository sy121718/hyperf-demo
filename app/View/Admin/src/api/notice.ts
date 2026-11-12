import { http } from "@/lib/http";
import type { PageResult } from "@/types/api";
import type {
  NoticeDetail,
  NoticePayload,
  NoticeRow,
  NoticeStatistics,
} from "@/types/notice";
import type { ListQuery } from "@/lib/list";

/**
 * 公告列表。scope 为 all 时看全量，mine 时只看自己创建的。
 *
 * 两个接口权限码不同（notice:list / notice:myList），前端按权限切换，
 * 页面本身是同一个。
 */
export function getNoticeList(scope: "all" | "mine", params: ListQuery) {
  const url = scope === "mine" ? "/notice/my-list" : "/notice/list";
  return http.get<PageResult<NoticeRow>>(url, { params });
}

/** 列表行不含正文，预览与编辑前都要单独取详情 */
export function getNoticeInfo(id: number) {
  return http.get<NoticeDetail>(`/notice/info/${id}`);
}

/** 创建返回 { id }；详情没有独立接口，列表行已含 content 之外的字段 */
export function createNotice(payload: NoticePayload) {
  return http.post<{ id: number }>("/notice/create", payload);
}

export function updateNotice(id: number, payload: NoticePayload) {
  return http.put<null>(`/notice/update/${id}`, payload);
}

export function deleteNotice(id: number) {
  return http.delete<null>(`/notice/delete/${id}`);
}

export function publishNotice(id: number, publishTime?: string) {
  return http.post<null>("/notice/publish", { id, publish_time: publishTime });
}

/** 撤回的 id 在请求体里（路由没带参数） */
export function withdrawNotice(id: number) {
  return http.post<null>("/notice/withdraw", { id });
}

export function setNoticeTargets(id: number, targetType: number, targetIds?: number[]) {
  return http.post<null>("/notice/set-targets", {
    id,
    target_type: targetType,
    target_ids: targetIds,
  });
}

export function getNoticeStatistics(params: ListQuery) {
  return http.get<NoticeStatistics>("/notice/statistics", { params });
}

/** 面向普通用户的定向推送，返回实际写入的目标条数 */
export function batchPushNotice(noticeId: number, targetUsers: number[]) {
  return http.post<{ count: number }>("/notice/batch-push", {
    notice_id: noticeId,
    target_users: targetUsers,
  });
}

/** 已读由后端幂等处理，重复调用不会重复累加 read_count；userId 由登录态决定，前端不传 */
export function markNoticeRead(noticeId: number) {
  return http.post<null>("/notice/mark-read", {
    notice_id: noticeId,
  });
}

/** 未读数按「已发布总数 - 本人已读」计算，userId 由登录态决定，前端不传 */
export function getUnreadNoticeCount() {
  return http.get<{ count: number }>("/notice/unread-count");
}
