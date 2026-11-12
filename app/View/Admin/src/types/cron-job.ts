import type { Switch } from "@/types/api";

/** 定时任务行（CronJobResponse::listFields） */
export interface CronJobRow {
  id: number;
  job_name: string;
  job_category: string;
  command: string;
  cron_expression: string;
  status: Switch;
  last_sync_time: string | null;
  create_time: string | null;
}

export interface CronJobDetail extends CronJobRow {
  description: string | null;
  sort_order: number | null;
  update_time: string | null;
}

export interface CronJobSaved {
  id: number;
  job_name: string;
}

export interface CronJobPayload {
  job_name?: string;
  cron_expression?: string;
  command?: string;
  job_category?: string;
  description?: string;
  status?: Switch;
  sort_order?: number;
}

/** POST /api/cron-job/run/{id} 的返回 */
export interface CronJobRunResult {
  job_id: number;
  job_name: string;
  exit_code: number | null;
  output: string;
  error_output: string;
  success: boolean;
}
