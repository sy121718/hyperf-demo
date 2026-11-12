/** 会话行（SessionResponse::listFields，jwt_token 不对外输出） */
export interface SessionRow {
  id: number;
  admin_id: number;
  /** 由后端预加载 admin 关联得出，管理员已删除时可能缺失 */
  admin_name?: string | null;
  admin_username?: string | null;
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
