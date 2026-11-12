import type { Switch } from "@/types/api";

/** 公告状态：0 草稿 / 1 已发布 / 2 已撤回 */
export const NOTICE_STATUS = {
  DRAFT: 0,
  PUBLISHED: 1,
  WITHDRAWN: 2,
} as const;

export const NOTICE_STATUS_LABELS: Record<number, string> = {
  [NOTICE_STATUS.DRAFT]: "草稿",
  [NOTICE_STATUS.PUBLISHED]: "已发布",
  [NOTICE_STATUS.WITHDRAWN]: "已撤回",
};

/** 公告行（NoticeResponse::listFields） */
export interface NoticeRow {
  id: number;
  title: string;
  notice_type: number;
  is_top: Switch;
  is_popup: Switch;
  status: number;
  publish_time: string | null;
  expire_time: string | null;
  read_count: number;
  is_read?: Switch;
  create_time: string | null;
}

export interface NoticeDetail extends NoticeRow {
  content: string | null;
  update_time: string | null;
}

export interface NoticeSaved {
  id: number;
  title: string;
}

export interface NoticePayload {
  title?: string;
  content?: string;
  notice_type?: number;
  is_top?: Switch;
  is_popup?: Switch;
  publish_time?: string;
  expire_time?: string;
  status?: number;
}

/** GET /api/notice/statistics 的返回 */
export interface NoticeStatistics {
  type_stats: Array<{ notice_type: number; count: number }>;
  status_stats: Array<{ status: number; count: number }>;
  total_read_count: number;
  date_range: { start_date: string; end_date: string };
}

/** POST /api/notice/set-targets 的提交体 */
export interface NoticeTargetPayload {
  id: number;
  /** 1 表示全员，其余为定向 */
  target_type: number;
  target_ids?: number[];
}
