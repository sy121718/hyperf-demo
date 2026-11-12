/**
 * 列表页通用状态：分页、搜索、额外筛选条件。
 *
 * 后端列表接口统一认 page / limit / search / sort 与模型白名单里的筛选字段，
 * 这里只负责把这些参数收拢成一个对象，页面不必各自拼装。
 */
import { useCallback, useMemo, useState } from "react";

import { DEFAULT_PAGE_SIZE } from "@/lib/constants";

/** 传给后端的扁平查询参数；值为 undefined 的键不会出现在请求串里 */
export type ListQuery = Record<string, string | number | undefined>;

export interface ListState {
  page: number;
  limit: number;
  search: string;
  /** 除分页与搜索外的筛选条件 */
  filters: ListQuery;
}

export interface UseListStateResult extends ListState {
  /** 实际发给后端的参数，已去掉空值 */
  params: ListQuery;
  setPage: (page: number) => void;
  setLimit: (limit: number) => void;
  /** 搜索会重置到第 1 页，避免停在越界页 */
  setSearch: (search: string) => void;
  setFilter: (key: string, value: string | number | undefined) => void;
  /** 重置搜索与全部筛选条件，保留每页条数 */
  reset: () => void;
}

export function useListState(initialFilters: ListQuery = {}): UseListStateResult {
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(DEFAULT_PAGE_SIZE);
  const [search, setSearchValue] = useState("");
  const [filters, setFilters] = useState<ListQuery>(initialFilters);

  const params = useMemo<ListQuery>(
    () => ({
      page,
      limit,
      search: search || undefined,
      ...filters,
    }),
    [page, limit, search, filters],
  );

  const setSearch = useCallback((value: string) => {
    setSearchValue(value);
    setPage(1);
  }, []);

  const setFilter = useCallback((key: string, value: string | number | undefined) => {
    setFilters((prev) => {
      const next = { ...prev, [key]: value };
      if (value === undefined || value === "") {
        delete next[key];
      }
      return next;
    });
    setPage(1);
  }, []);

  const reset = useCallback(() => {
    setSearchValue("");
    setFilters(initialFilters);
    setPage(1);
  }, [initialFilters]);

  return {
    page,
    limit,
    search,
    filters,
    params,
    setPage,
    setLimit,
    setSearch,
    setFilter,
    reset,
  };
}
