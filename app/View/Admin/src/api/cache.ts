import { http } from "@/lib/http";

export interface CacheModule {
  value: string;
  label: string;
}

export interface CacheClearResult {
  cleared: Array<{ module: string; label: string; deleted_keys: number }>;
  total_deleted: number;
  is_full_clear: boolean;
}

export function getCacheModules() {
  return http.get<CacheModule[]>("/cache/modules");
}

/** modules 不传表示全部清除；后端只有超管能调（403 由拦截器提示） */
export function clearCache(modules?: string[]) {
  return http.post<CacheClearResult>("/cache/clear", { modules });
}
