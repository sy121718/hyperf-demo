/** 登录日志行（LogResponse::listFields 的登录部分） */
export interface LoginLogRow {
  id: number;
  user_id: number;
  username: string | null;
  status: number;
  ip_address: string | null;
  location: string | null;
  user_agent: string | null;
  device_type: string | null;
  login_time: string | null;
  /** 仅详情返回，列表不含此字段 */
  msg?: string | null;
}

/** 操作日志行（LogResponse::listFields 的操作部分） */
export interface OperationLogRow {
  id: number;
  admin_id: number;
  /** 由后端预加载 admin 关联得出，管理员已删除时可能缺失 */
  admin_name?: string | null;
  menu_id: number | null;
  operation: string | null;
  service_method: string | null;
  api_path: string | null;
  http_method: string | null;
  /** 接口层用 success / failed，后端在查询时转成 1 / 0 */
  result: number | string | null;
  execution_time: number;
  ip: string | null;
  location: string | null;
  create_time: string | null;
}

/** 操作日志详情，含请求/响应原文 */
export interface OperationLogDetail extends OperationLogRow {
  request_data: unknown;
  response_data: unknown;
  user_agent: string | null;
  content: string | null;
}
