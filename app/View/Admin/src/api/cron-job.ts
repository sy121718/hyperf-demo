import { http } from "@/lib/http";
import type { PageResult } from "@/types/api";
import type {
  CronJobDetail,
  CronJobPayload,
  CronJobRow,
  CronJobRunResult,
  CronJobSaved,
} from "@/types/cron-job";
import type { ListQuery } from "@/lib/list";

export function getCronJobList(params: ListQuery) {
  return http.get<PageResult<CronJobRow>>("/cron-job/list", { params });
}

export function getCronJobInfo(id: number) {
  return http.get<CronJobDetail>(`/cron-job/info/${id}`);
}

export function createCronJob(payload: CronJobPayload) {
  return http.post<CronJobSaved>("/cron-job/create", payload);
}

export function updateCronJob(id: number, payload: CronJobPayload) {
  return http.put<CronJobSaved>(`/cron-job/update/${id}`, payload);
}

export function deleteCronJob(id: number) {
  return http.delete<null>(`/cron-job/delete/${id}`);
}

/** 手动执行一次，返回进程退出码与输出，耗时可长达 120s */
export function runCronJob(id: number) {
  return http.post<CronJobRunResult>(`/cron-job/run/${id}`, undefined, {
    timeout: 130_000,
  });
}

export function batchUpdateCronJobs(ids: number[], status?: number) {
  return http.post<{ updated: number }>("/cron-job/batch-update", { ids, status });
}
