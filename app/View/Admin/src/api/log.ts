import { http } from "@/lib/http";
import type { PageResult } from "@/types/api";
import type { LoginLogRow, OperationLogRow } from "@/types/log";
import type { ListQuery } from "@/lib/list";

/** 登录日志：all 看全部，mine 只看本人 */
export function getLoginLogs(scope: "all" | "mine", params: ListQuery) {
  const url = scope === "mine" ? "/log/my-login" : "/log/login";
  return http.get<PageResult<LoginLogRow>>(url, { params });
}

/** 操作日志：all 看全部，mine 只看本人 */
export function getOperationLogs(scope: "all" | "mine", params: ListQuery) {
  const url = scope === "mine" ? "/log/my-operation" : "/log/operation";
  return http.get<PageResult<OperationLogRow>>(url, { params });
}
