/** 后端统一响应体，成功与否只看 code，不看 HTTP 状态码 */
export interface ApiResponse<T> {
  success: boolean;
  code: number;
  message: string;
  data: T;
}

/** 分页列表响应 */
export interface PageResult<T> {
  total: number;
  list: T[];
}

/** 参数校验失败时 data 的形态：字段名 -> 错误信息数组 */
export type ValidationErrors = Record<string, string[]>;

/** 后端 status 类字段统一是数字，不是布尔 */
export type Switch = 0 | 1;
