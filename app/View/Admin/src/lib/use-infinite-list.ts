import { useCallback, useEffect, useRef } from "react";
import { useInfiniteQuery } from "@tanstack/react-query";

import type { PageResult } from "@/types/api";
import type { ListQuery } from "@/lib/list";

/**
 * 移动端无限滚动列表。
 *
 * 后端分页协议不变（page / limit + `{ total, list }`），这里把多页结果
 * 拼成一条连续列表：滚到底部自动取下一页，并在快接近末页时提前预取。
 */
export interface UseInfiniteListOptions<T> {
  /** 查询 key 前缀，页面用 queryKeys.xxx 保持一致 */
  queryKey: readonly unknown[];
  fetcher: (params: ListQuery) => Promise<PageResult<T>>;
  /** 除 page 外的全部查询参数（搜索、筛选） */
  params: ListQuery;
  limit: number;
  enabled?: boolean;
}

export function useInfiniteList<T>({
  queryKey,
  fetcher,
  params,
  limit,
  enabled = true,
}: UseInfiniteListOptions<T>) {
  return useInfiniteQuery({
    queryKey: [...queryKey, params, limit],
    queryFn: ({ pageParam }) => fetcher({ ...params, page: pageParam, limit }),
    initialPageParam: 1,
    getNextPageParam: (lastPage, allPages) => {
      const loaded = allPages.length * limit;
      return loaded >= lastPage.total ? undefined : allPages.length + 1;
    },
    enabled,
    staleTime: 30_000,
  });
}

/**
 * 触底加载 + 邻近末页预取。
 *
 * 返回的 ref 挂到列表底部的哨兵元素上：进入视口就取下一页。
 * 另外当已加载数据距离 total 只剩不到 2 页时，主动预取，
 * 这样用户滑到底之前数据通常已经就位。
 */
export function useLoadMoreOnScroll(options: {
  hasNextPage: boolean;
  isFetchingNextPage: boolean;
  loadedCount: number;
  total: number;
  limit: number;
  fetchNextPage: () => void;
}) {
  const { hasNextPage, isFetchingNextPage, loadedCount, total, limit, fetchNextPage } = options;
  const sentinelRef = useRef<HTMLDivElement | null>(null);

  const loadMore = useCallback(() => {
    if (hasNextPage && !isFetchingNextPage) {
      fetchNextPage();
    }
  }, [hasNextPage, isFetchingNextPage, fetchNextPage]);

  /** 邻近末页（剩余不足 2 页）时提前拉，避免滑到底才等网络 */
  useEffect(() => {
    if (!hasNextPage || isFetchingNextPage) {
      return;
    }
    const remaining = total - loadedCount;
    if (remaining > 0 && remaining <= limit * 2) {
      fetchNextPage();
    }
  }, [hasNextPage, isFetchingNextPage, total, loadedCount, limit, fetchNextPage]);

  useEffect(() => {
    const node = sentinelRef.current;
    if (!node || !hasNextPage) {
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          loadMore();
        }
      },
      { rootMargin: "200px" },
    );
    observer.observe(node);

    return () => observer.disconnect();
  }, [hasNextPage, loadMore]);

  return sentinelRef;
}

/** 把分页结果拍平，页面直接渲染 items */
export function flattenPages<T>(pages: Array<PageResult<T>> | undefined): T[] {
  return pages?.flatMap((page) => page.list) ?? [];
}
