import { http } from "@/lib/http";
import type { PageResult } from "@/types/api";
import type {
  EmailRecordRow,
  EmailSendPayload,
  EmailSendResult,
  EmailTemplatePayload,
  EmailTemplateRow,
  EmailTemplateSaved,
} from "@/types/email";
import type { ListQuery } from "@/lib/list";

export function sendEmail(payload: EmailSendPayload) {
  return http.post<EmailSendResult>("/email/send", payload);
}

export function testSendEmail(to: string) {
  return http.post<null>("/email/test", { to });
}

/** 发送记录：scope 为 all 看全量，mine 只看自己发的 */
export function getEmailRecords(scope: "all" | "mine", params: ListQuery) {
  const url = scope === "mine" ? "/email/my-records" : "/email/records";
  return http.get<PageResult<EmailRecordRow>>(url, { params });
}

export function getEmailTemplates(params: ListQuery) {
  return http.get<PageResult<EmailTemplateRow>>("/email/template/list", { params });
}

export function createEmailTemplate(payload: EmailTemplatePayload) {
  return http.post<EmailTemplateSaved>("/email/template/create", payload);
}

export function updateEmailTemplate(id: number, payload: EmailTemplatePayload) {
  return http.put<EmailTemplateSaved>(`/email/template/update/${id}`, payload);
}

export function deleteEmailTemplate(id: number) {
  return http.delete<null>(`/email/template/delete/${id}`);
}

