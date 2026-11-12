import { http } from "@/lib/http";
import type { PageResult } from "@/types/api";
import type {
  AttachmentRow,
  AttachmentSaved,
  AttachmentUpdatePayload,
  AttachmentUploadPayload,
} from "@/types/attachment";
import type { ListQuery } from "@/lib/list";

/**
 * 附件列表。scope 为 all 时走全量接口，mine 时只返回当前用户上传的。
 *
 * 两个接口拆开是后端刻意为之：普通管理员只发 attachment:myList 权限
 * 也能进入同一个页面，前端按是否有全量权限切换 scope。
 */
export function getAttachmentList(scope: "all" | "mine", params: ListQuery) {
  const url = scope === "mine" ? "/attachment/my-list" : "/attachment/list";
  return http.get<PageResult<AttachmentRow>>(url, { params });
}

/** 后端当前只收文件元数据，不收 multipart，见 app/View/CLAUDE.md 6.8 */
export function uploadAttachment(payload: AttachmentUploadPayload) {
  return http.post<AttachmentSaved>("/attachment/upload", payload);
}

export function updateAttachment(id: number, payload: AttachmentUpdatePayload) {
  return http.put<AttachmentSaved>(`/attachment/update/${id}`, payload);
}

export function deleteAttachment(id: number) {
  return http.delete<null>(`/attachment/delete/${id}`);
}
