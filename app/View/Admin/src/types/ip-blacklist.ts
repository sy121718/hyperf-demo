/** IP 拉黑状态：0 已过期 / 1 生效中 / 2 已手动解除 */
export const IP_BAN_STATUS = {
  EXPIRED: 0,
  ACTIVE: 1,
  MANUAL_LIFTED: 2,
} as const;

export const IP_BAN_STATUS_LABELS: Record<number, string> = {
  [IP_BAN_STATUS.EXPIRED]: "已过期",
  [IP_BAN_STATUS.ACTIVE]: "生效中",
  [IP_BAN_STATUS.MANUAL_LIFTED]: "已解除",
};

/** IP 黑名单行（IpBlacklistResponse::listFields） */
export interface IpBlacklistRow {
  id: number;
  ip_address: string;
  error_type: string | null;
  ban_reason: string | null;
  ban_duration: number;
  banned_time: string | null;
  banned_until_time: string | null;
  ban_count: number;
  status: number;
  create_time: string | null;
}

export interface IpBlacklistSaved {
  id: number;
  ip_address: string;
  status: number;
}

export interface IpBanPayload {
  ip_address: string;
  error_type?: string;
  ban_reason?: string;
  /** 分钟数，后端默认 30 */
  ban_duration?: number;
  remark?: string;
}

/** POST /api/ip-blacklist/check 的返回 */
export interface IpCheckResult {
  is_banned: boolean;
  ban_info: IpBlacklistRow | null;
}
