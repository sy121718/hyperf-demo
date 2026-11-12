import { http } from "@/lib/http";
import type { PageResult } from "@/types/api";
import type { SessionDetail, SessionRow } from "@/types/session";
import type { ListQuery } from "@/lib/list";

/** 会话列表：all 看全部在线会话，mine 只看自己的设备 */
export function getSessionList(scope: "all" | "mine", params: ListQuery) {
  const url = scope === "mine" ? "/session/my-list" : "/session/list";
  return http.get<PageResult<SessionRow>>(url, { params });
}

export function getSessionInfo(id: number) {
  return http.get<SessionDetail>(`/session/info/${id}`);
}

export function kickSession(id: number) {
  return http.post<null>("/session/kickout", { id });
}

export function batchKickSessions(ids: number[]) {
  return http.post<{ count: number }>("/session/batch-kickout", { ids });
}

/** 清理已过期会话，返回清理条数 */
export function cleanExpiredSessions() {
  return http.post<{ count: number }>("/session/clean");
}
