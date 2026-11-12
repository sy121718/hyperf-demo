import { http } from "@/lib/http";
import type { PageResult } from "@/types/api";
import type {
  IpBanPayload,
  IpBlacklistRow,
  IpBlacklistSaved,
  IpCheckResult,
} from "@/types/ip-blacklist";
import type { ListQuery } from "@/lib/list";

export function getIpBlacklist(params: ListQuery) {
  return http.get<PageResult<IpBlacklistRow>>("/ip-blacklist/list", { params });
}

export function banIp(payload: IpBanPayload) {
  return http.post<IpBlacklistSaved>("/ip-blacklist/ban", payload);
}

/** 解除拉黑按记录 ID 操作（不是 IP 字符串） */
export function unbanIp(id: number) {
  return http.post<IpBlacklistSaved>("/ip-blacklist/unban", { id });
}

export function checkIp(ipAddress: string) {
  return http.post<IpCheckResult>("/ip-blacklist/check", { ip_address: ipAddress });
}
