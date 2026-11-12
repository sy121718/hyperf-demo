import { useEffect, useState } from "react";
import { useQuery } from "@tanstack/react-query";

import type { PageResult } from "@/types/api";
import type { ListQuery } from "@/lib/list";
import { flattenPages, useInfiniteList, useLoadMoreOnScroll } from "@/lib/use-infinite-list";

/**
 * 响应式列表数据源。
 *
 * 桌面端保持服务端分页（页码 + 每页条数），手机端切换成无限滚动：
 * 同一份接口、同一套筛选参数，只是把多页结果拼起来。
 *
 * 两个查询都声明出来，靠 enabled 只跑其中一个——hooks 不能条件调用。
 */
export interface UseResponsiveListOptions<T> {
  queryKey: readonly unknown[];
  fetcher: (params: ListQuery) => Promise<PageResult<T>>;
  params: ListQuery;
  page: number;
  limit: number;
  enabled?: boolean;
}

export interface ResponsiveListResult<T> {
  items: T[];
  total: number;
  /** 首屏加载：还没有任何数据可显示 */
  loading: boolean;
  /** 正在刷新：已有数据但新请求在飞（换页、改筛选、搜索） */
  refreshing: boolean;
  /** 手机上是否正在加载下一页 */
  loadingMore: boolean;
  hasNextPage: boolean;
  /** 挂到手机列表底部的哨兵元素 */
  sentinelRef: React.RefObject<HTMLDivElement | null>;
  isMobile: boolean;
}

/** 与 Tailwind 的 md 断点对齐：< 768px 视为手机 */
export function useIsMobile(): boolean {
  const [isMobile, setIsMobile] = useState(
    () => typeof window !== "undefined" && window.matchMedia("(max-width: 767px)").matches,
  );

  useEffect(() => {
    const query = window.matchMedia("(max-width: 767px)");
    const handler = (event: MediaQueryListEvent) => setIsMobile(event.matches);
    query.addEventListener("change", handler);
    return () => query.removeEventListener("change", handler);
  }, []);

  return isMobile;
}

export function useResponsiveList<T>({
  queryKey,
  fetcher,
  params,
  page,
  limit,
  enabled = true,
}: UseResponsiveListOptions<T>): ResponsiveListResult<T> {
  const isMobile = useIsMobile();

  const pagedQuery = useQuery({
    queryKey: [...queryKey, "paged", params, page, limit],
    queryFn: () => fetcher({ ...params, page, limit }),
    enabled: enabled && !isMobile,
    staleTime: 30_000,
  });

  const infiniteQuery = useInfiniteList<T>({
    queryKey: [...queryKey, "infinite"],
    fetcher,
    params,
    limit,
    enabled: enabled && isMobile,
  });

  const items = isMobile ? flattenPages<T>(infiniteQuery.data?.pages) : (pagedQuery.data?.list ?? []);
  const total = isMobile ? (infiniteQuery.data?.pages[0]?.total ?? 0) : (pagedQuery.data?.total ?? 0);

  const sentinelRef = useLoadMoreOnScroll({
    hasNextPage: infiniteQuery.hasNextPage,
    isFetchingNextPage: infiniteQuery.isFetchingNextPage,
    loadedCount: items.length,
    total,
    limit,
    fetchNextPage: () => void infiniteQuery.fetchNextPage(),
  });

  return {
    items,
    total,
    loading: isMobile ? infiniteQuery.isLoading : pagedQuery.isLoading,
    // 刷新与首屏分开：刷新时保留旧数据继续显示，只做轻提示，避免表格闪成骨架
    refreshing: isMobile
      ? infiniteQuery.isFetching && !infiniteQuery.isFetchingNextPage
      : pagedQuery.isFetching,
    loadingMore: infiniteQuery.isFetchingNextPage,
    hasNextPage: infiniteQuery.hasNextPage,
    sentinelRef,
    isMobile,
  };
}
