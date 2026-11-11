import { QueryClient } from "@tanstack/react-query";

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // 列表数据在窗口重新聚焦时刷新会打断操作，关掉；写操作后主动 invalidate
      refetchOnWindowFocus: false,
      // 业务错误统一由 http 拦截器提示，react-query 不再重试
      retry: false,
      staleTime: 30_000,
    },
    mutations: {
      retry: false,
    },
  },
});
