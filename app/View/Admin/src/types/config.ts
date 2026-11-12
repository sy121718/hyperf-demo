import type { Switch } from "@/types/api";

/** 系统配置行（ConfigResponse::listFields） */
export interface ConfigRow {
  id: number;
  group_key: string;
  group_name: string;
  /** json 列，后端已 cast 成对象 */
  config_data: Record<string, unknown> | null;
  remark: string | null;
  status: Switch;
  create_time: string | null;
}

export interface ConfigDetail extends ConfigRow {
  update_time: string | null;
}

export interface ConfigSaved {
  id: number;
  group_key: string;
  group_name: string;
}

export interface ConfigPayload {
  group_key?: string;
  group_name?: string;
  config_data?: Record<string, unknown>;
  remark?: string;
  status?: Switch;
}
