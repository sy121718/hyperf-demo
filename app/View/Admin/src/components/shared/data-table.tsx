import type { ReactNode } from "react";

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@rbac/ui/components/ui/table";
import { Skeleton } from "@rbac/ui/components/ui/skeleton";

/**
 * 服务端分页表格的展示壳。
 *
 * 这里不做分页计算——分页条在 DataTablePagination 里，数据由调用方
 * 从 react-query 拿。组件只负责 loading / 空态 / 行渲染三件事，
 * 避免每个页面重复写几百行表格样板。
 */
export interface DataTableColumn<T> {
  /** 列标识，同时用作 React key */
  key: string;
  header: ReactNode;
  /** 单元格渲染；不传时直接读 row[key] */
  cell?: (row: T) => ReactNode;
  /** 单元格对齐，数字列用 right 更易读 */
  align?: "left" | "right" | "center";
  /** 列宽类名，如 "w-32" */
  className?: string;
  /**
   * 手机端卡片的呈现方式，默认 field。
   *
   * 页面只定义一份 columns，手机端由 ResponsiveDataList 按这个标记
   * 自动拆成：标题、徽标、字段、操作区。新页面不用再单独写卡片。
   */
  mobile?: "title" | "badge" | "hidden";
  /** 手机端字段名；不填时用 header（必须是字符串）或 key */
  mobileLabel?: string;
}

export interface DataTableProps<T> {
  columns: DataTableColumn<T>[];
  rows: T[];
  rowKey: (row: T) => string | number;
  loading?: boolean;
  /** 空态文案，默认「暂无数据」 */
  emptyText?: string;
  /** 每页条数，用于 loading 时占位骨架行数 */
  skeletonRows?: number;
}

function alignClass(align: DataTableColumn<unknown>["align"]): string {
  return align === "right" ? "text-right" : align === "center" ? "text-center" : "";
}

export function DataTable<T>({
  columns,
  rows,
  rowKey,
  loading = false,
  emptyText = "暂无数据",
  skeletonRows = 5,
}: DataTableProps<T>) {
  return (
    <div className="overflow-x-auto rounded-xl border bg-card shadow-sm">
      <Table>
        <TableHeader className="bg-muted/40">
          <TableRow className="hover:bg-transparent">
            {columns.map((column) => (
              <TableHead
                key={column.key}
                className={`h-11 text-xs font-medium tracking-wide text-muted-foreground uppercase ${column.className ?? ""} ${alignClass(column.align)}`}
              >
                {column.header}
              </TableHead>
            ))}
          </TableRow>
        </TableHeader>
        <TableBody>
          {loading ? (
            Array.from({ length: skeletonRows }, (_, rowIndex) => (
              <TableRow key={`skeleton-${rowIndex}`}>
                {columns.map((column) => (
                  <TableCell key={column.key}>
                    <Skeleton className="h-4 w-full max-w-40" />
                  </TableCell>
                ))}
              </TableRow>
            ))
          ) : rows.length === 0 ? (
            <TableRow>
              <TableCell colSpan={columns.length} className="h-24 text-center text-muted-foreground">
                {emptyText}
              </TableCell>
            </TableRow>
          ) : (
            rows.map((row) => (
              <TableRow key={rowKey(row)} className="transition-colors">
                {columns.map((column) => (
                  <TableCell key={column.key} className={`py-3 ${alignClass(column.align)}`}>
                    {column.cell
                      ? column.cell(row)
                      : String((row as Record<string, unknown>)[column.key] ?? "-")}
                  </TableCell>
                ))}
              </TableRow>
            ))
          )}
        </TableBody>
      </Table>
    </div>
  );
}
