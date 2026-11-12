import { http } from "@/lib/http";
import type { PageResult } from "@/types/api";
import type { ConfigDetail, ConfigPayload, ConfigRow, ConfigSaved } from "@/types/config";
import type { ListQuery } from "@/lib/list";

export function getConfigList(params: ListQuery) {
  return http.get<PageResult<ConfigRow>>("/config/list", { params });
}

export function getConfigInfo(id: number) {
  return http.get<ConfigDetail>(`/config/info/${id}`);
}

export function createConfig(payload: ConfigPayload) {
  return http.post<ConfigSaved>("/config/create", payload);
}

export function updateConfig(id: number, payload: ConfigPayload) {
  return http.put<ConfigSaved>(`/config/update/${id}`, payload);
}

export function deleteConfig(id: number) {
  return http.delete<null>(`/config/delete/${id}`);
}
