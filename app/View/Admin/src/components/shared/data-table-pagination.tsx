import { ChevronLeft, ChevronRight } from "lucide-react";

import { Button } from "@rbac/ui/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@rbac/ui/components/ui/select";
import { PAGE_SIZE_OPTIONS } from "@/lib/constants";

export interface DataTablePaginationProps {
  total: number;
  page: number;
  limit: number;
  onPageChange: (page: number) => void;
  onLimitChange: (limit: number) => void;
}

/**
 * 服务端分页条。
 *
 * 后端返回的是 { total, list }，页码由前端自己维护并回传，
 * 所以这里只负责展示与回调，不做任何切片计算。
 */
export function DataTablePagination({
  total,
  page,
  limit,
  onPageChange,
  onLimitChange,
}: DataTablePaginationProps) {
  const pageCount = Math.max(1, Math.ceil(total / limit));
  const canPrev = page > 1;
  const canNext = page < pageCount;

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
      <p className="text-muted-foreground">
        共 {total} 条，第 {page} / {pageCount} 页
      </p>

      <div className="flex items-center gap-2">
        <Select value={String(limit)} onValueChange={(value) => onLimitChange(Number(value))}>
          <SelectTrigger size="sm" className="w-24">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            {PAGE_SIZE_OPTIONS.map((option) => (
              <SelectItem key={option} value={String(option)}>
                {option} 条/页
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <Button variant="outline" size="sm" disabled={!canPrev} onClick={() => onPageChange(page - 1)}>
          <ChevronLeft />
          上一页
        </Button>
        <Button variant="outline" size="sm" disabled={!canNext} onClick={() => onPageChange(page + 1)}>
          下一页
          <ChevronRight />
        </Button>
      </div>
    </div>
  );
}
