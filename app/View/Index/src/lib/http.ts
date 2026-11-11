import axios, { AxiosError, type AxiosRequestConfig } from "axios";
import { toast } from "sonner";

import type { ApiResponse, ValidationErrors } from "@/types/api";
import { clearToken, getToken, setToken } from "@/lib/auth";

/** 业务异常：带上后端 code 与字段级错误，供表单与路由层区分处理 */
export class ApiError extends Error {
  constructor(
    message: string,
    public readonly code: number,
    public readonly errors: ValidationErrors | null = null,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

const client = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? "/api",
  timeout: 30_000,
});

client.interceptors.request.use((config) => {
  const token = getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

/** 401 族：清登录态并跳转登录页。前台路由不带 /admin 前缀 */
function handleUnauthorized(): void {
  clearToken();
  const current = `${window.location.pathname}${window.location.search}`;
  if (window.location.pathname !== "/login") {
    window.location.replace(`/login?redirect=${encodeURIComponent(current)}`);
  }
}

client.interceptors.response.use(
  (response) => {
    const newToken = response.headers["new-token"];
    if (typeof newToken === "string" && newToken) {
      setToken(newToken);
    }

    const body = response.data as ApiResponse<unknown>;

    if (typeof body?.code !== "number") {
      return response;
    }

    if (body.code === 200) {
      return response;
    }

    if (body.code === 401) {
      handleUnauthorized();
      return Promise.reject(new ApiError(body.message, body.code));
    }

    if (body.code === 422) {
      return Promise.reject(
        new ApiError(body.message, body.code, body.data as ValidationErrors),
      );
    }

    toast.error(body.message || "请求失败");
    return Promise.reject(new ApiError(body.message, body.code));
  },
  (error: AxiosError<ApiResponse<unknown>>) => {
    const body = error.response?.data;

    if (body && typeof body.code === "number") {
      if (body.code === 401) {
        handleUnauthorized();
        return Promise.reject(new ApiError(body.message, body.code));
      }

      if (body.code === 422) {
        return Promise.reject(
          new ApiError(body.message, body.code, body.data as ValidationErrors),
        );
      }

      toast.error(body.message || "请求失败");
      return Promise.reject(new ApiError(body.message, body.code));
    }

    toast.error("网络异常，请稍后重试");
    return Promise.reject(new ApiError("网络异常，请稍后重试", 0));
  },
);

async function request<T>(config: AxiosRequestConfig): Promise<T> {
  const response = await client.request<ApiResponse<T>>(config);
  return response.data.data;
}

export const http = {
  get: <T>(url: string, config?: AxiosRequestConfig) =>
    request<T>({ ...config, method: "GET", url }),
  post: <T>(url: string, data?: unknown, config?: AxiosRequestConfig) =>
    request<T>({ ...config, method: "POST", url, data }),
  put: <T>(url: string, data?: unknown, config?: AxiosRequestConfig) =>
    request<T>({ ...config, method: "PUT", url, data }),
  delete: <T>(url: string, config?: AxiosRequestConfig) =>
    request<T>({ ...config, method: "DELETE", url }),
};
