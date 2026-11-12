import type { Switch } from "@/types/api";

/** 发送记录状态：0 待发送 / 1 发送中 / 2 成功 / 3 失败 */
export const EMAIL_RECORD_STATUS = {
  PENDING: 0,
  SENDING: 1,
  SUCCESS: 2,
  FAILED: 3,
} as const;

export const EMAIL_RECORD_STATUS_LABELS: Record<number, string> = {
  [EMAIL_RECORD_STATUS.PENDING]: "待发送",
  [EMAIL_RECORD_STATUS.SENDING]: "发送中",
  [EMAIL_RECORD_STATUS.SUCCESS]: "成功",
  [EMAIL_RECORD_STATUS.FAILED]: "失败",
};

/** 发送记录行（EmailResponse::listFields 的子集） */
export interface EmailRecordRow {
  id: number;
  template_id: number | null;
  subject: string;
  status: number;
  total_recipients: number;
  success_count: number;
  failed_count: number;
  error_message: string | null;
  sent_time: string | null;
  create_time: string | null;
}

/**
 * 邮件模板行。
 *
 * 模板列表与发送记录共用 EmailResponse::page()，字段取两张表的并集，
 * 所以模板行同样会带 subject 与 status（模板自己的主题与启用状态）。
 */
export interface EmailTemplateRow {
  id: number;
  template_name: string;
  template_code: string;
  template_type: string;
  variables: Record<string, string> | null;
  subject?: string;
  status?: Switch;
  create_time: string | null;
}

/** GET /api/email/template/list 的返回项带 subject 与 status */
export interface EmailTemplateDetail extends EmailTemplateRow {
  subject: string;
  content: string;
  status: Switch;
}

export interface EmailTemplateSaved {
  id: number;
  template_code: string;
  template_name: string;
  status: Switch;
}

export interface EmailTemplatePayload {
  template_name?: string;
  template_code?: string;
  subject?: string;
  content?: string;
  template_type?: string;
  variables?: Record<string, string>;
  status?: Switch;
}

/** POST /api/email/send 的提交体，to_list 优先于 to */
export interface EmailSendPayload {
  subject: string;
  content: string;
  to?: string;
  to_list?: string[];
  template_id?: number;
}

/** POST /api/email/send 的返回 */
export interface EmailSendResult {
  record_id: number;
  recipient_count: number;
}
