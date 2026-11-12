import axios, { AxiosError, type AxiosRequestConfig } from "axios";
import { toast } from "sonner";

import type { ApiResponse, ValidationErrors } from "@/types/api";
import { getToken } from "@/lib/auth";
import { useAuthStore } from "@/stores/auth";

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

/**
 * 401 族：清登录态并跳转登录页，带上来源路径供登录后回跳。
 *
 * 这里用 window.location 而不是 react-router 的 navigate——拦截器在
 * 组件树之外执行。BrowserRouter 的 basename 是 /admin，所以跳转地址
 * 要带前缀，而 redirect 参数存的是去掉前缀的路径，登录后由 router 处理。
 */
function handleUnauthorized(): void {
  // 走 store.reset() 同时清 localStorage 与内存态，路由会跟着切回登录页
  useAuthStore.getState().reset();
  const basename = "/admin";
  const path = window.location.pathname.startsWith(basename)
    ? window.location.pathname.slice(basename.length)
    : window.location.pathname;
  const current = `${path || "/"}${window.location.search}`;

  if (path !== "/login") {
    window.location.replace(`${basename}/login?redirect=${encodeURIComponent(current)}`);
  }
}

client.interceptors.response.use(
  (response) => {
    // 滑动续期：后端在剩余有效期不足时下发新 token，必须替换否则会被登出
    const newToken = response.headers["new-token"];
    if (typeof newToken === "string" && newToken) {
      // 走 store 才能同步内存态，否则续期后 store.token 还是旧值
      useAuthStore.getState().setToken(newToken);
    }

    const body = response.data as ApiResponse<unknown>;

    // 非标准响应（如静态文件）直接放过
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

    // 422 交给表单按字段回填，不弹全局提示
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

/** 统一解包 body.data，调用方拿到的直接是业务数据 */
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
