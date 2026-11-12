/** 会话行（SessionResponse::listFields，jwt_token 不对外输出） */
export interface SessionRow {
  id: number;
  admin_id: number;
  device_type: string | null;
  login_ip: string | null;
  location: string | null;
  isp: string | null;
  user_agent: string | null;
  login_time: string | null;
  last_active_time: string | null;
  status: number;
}

/** GET /api/session/info/{id} 的返回 */
export interface SessionDetail extends SessionRow {
  device_info: string | null;
  expires_time: string | null;
}
