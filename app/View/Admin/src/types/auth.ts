import type { Switch } from "@/types/api";

/** 登录返回的 admin_info（密码已由后端剔除） */
export interface AdminInfo {
  id: number;
  admin_code: string;
  username: string;
  name: string | null;
  avatar: string | null;
  email: string | null;
  phone: string | null;
  status: Switch;
  is_admin: Switch;
  last_login_time: string | null;
  last_login_ip: string | null;
}

/** POST /api/auth/login 的返回 */
export interface LoginResult {
  token: string;
  token_type: string;
  expires_in: number;
  admin_info: AdminInfo;
}

export interface LoginPayload {
  username: string;
  password: string;
}
