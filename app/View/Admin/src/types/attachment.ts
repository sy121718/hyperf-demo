import type { Switch } from "@/types/api";

/** 附件行（AttachmentResponse::listFields） */
export interface AttachmentRow {
  id: number;
  category_id: number | null;
  file_name: string;
  file_path: string;
  file_size: number;
  file_type: string;
  mime_type: string | null;
  storage_type: string | null;
  url: string | null;
  status: Switch;
  create_time: string | null;
}

export interface AttachmentSaved {
  id: number;
  file_name: string;
  url: string | null;
}

/**
 * 上传请求体。
 *
 * 后端目前只接收文件元数据，不收 multipart 文件流（见 app/View/CLAUDE.md 6.8），
 * 所以前端也按 JSON 提交；等后端支持真实上传后只改 api/attachment.ts 一处。
 */
export interface AttachmentUploadPayload {
  file_name: string;
  file_path: string;
  file_size: number;
  file_type: string;
  mime_type?: string;
  storage_type?: string;
  storage_path?: string;
  url?: string;
  md5?: string;
  category_id?: number;
  extra_info?: Record<string, unknown>;
}

export interface AttachmentUpdatePayload {
  file_name?: string;
  file_type?: string;
  storage_type?: string;
  status?: Switch;
  category_id?: number;
  mime_type?: string;
  storage_path?: string;
  url?: string;
  extra_info?: Record<string, unknown>;
}
