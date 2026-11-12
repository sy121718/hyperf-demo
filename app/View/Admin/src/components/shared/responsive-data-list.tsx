import type { ReactNode } from "react";

import { DataTable, type DataTableColumn } from "@/components/shared/data-table";
import { DataTablePagination } from "@/components/shared/data-table-pagination";
import { MobileCardList } from "@/components/shared/mobile-card-list";

/**
 * 一份 columns，两种呈现。
 *
 * 页面只定义表格列，并给需要特殊处理的列标上 mobile 语义：
 * - mobile="title"：作为卡片标题（不写时取第一列）
 * - mobile="badge"：作为标题右侧徽标（通常是状态列）
 * - mobile="hidden"：手机端不显示（如纯 ID 列）
 * 其余列自动转成卡片里的「标签 + 值」。
 *
 * 这样新增列表页只要写一次 columns，手机端卡片自动就位，
 * 不需要再维护第二套 UI。
 */
export interface ResponsiveDataListProps<T> {
  columns: DataTableColumn<T>[];
  /** 已由 useResponsiveList 取好的当前页数据 */
  items: T[];
  total: number;
  loading: boolean;
  /** 手机端无限滚动的状态，桌面端忽略 */
  loadingMore: boolean;
  hasNextPage: boolean;
  sentinelRef: React.RefObject<HTMLDivElement | null>;
  isMobile: boolean;
  rowKey: (row: T) => string | number;
  emptyText?: string;
  /** 分页状态，仅桌面端渲染分页条时使用 */
  page: number;
  limit: number;
  onPageChange: (page: number) => void;
  onLimitChange: (limit: number) => void;
  /** 卡片标题的兜底渲染，覆写 mobile="title" */
  mobileTitle?: (row: T) => ReactNode;
  /** 卡片徽标的兜底渲染，覆写 mobile="badge" */
  mobileBadge?: (row: T) => ReactNode;
  /** 操作列在手机端底部单独展示 */
  mobileActions?: (row: T) => ReactNode;
  /** 手机端字段最多显示几个，超出的收成「更多」 */
  mobileMaxFields?: number;
}

/** 取列的手机端标签 */
function labelOf<T>(column: DataTableColumn<T>): string {
  if (column.mobileLabel) {
    return column.mobileLabel;
  }
  return typeof column.header === "string" ? column.header : column.key;
}

/** 取列在手机端要渲染的内容 */
function renderCell<T>(column: DataTableColumn<T>, row: T): ReactNode {
  if (column.cell) {
    return column.cell(row);
  }
  return String((row as Record<string, unknown>)[column.key] ?? "-");
}

export function ResponsiveDataList<T>({
  columns,
  items,
  total,
  loading,
  loadingMore,
  hasNextPage,
  sentinelRef,
  isMobile,
  rowKey,
  emptyText,
  page,
  limit,
  onPageChange,
  onLimitChange,
  mobileTitle,
  mobileBadge,
  mobileActions,
  mobileMaxFields = 6,
}: ResponsiveDataListProps<T>) {
  if (!isMobile) {
    return (
      <>
        <DataTable
          columns={columns}
          rows={items}
          rowKey={rowKey}
          loading={loading}
          emptyText={emptyText}
        />
        <DataTablePagination
          total={total}
          page={page}
          limit={limit}
          onPageChange={onPageChange}
          onLimitChange={onLimitChange}
        />
      </>
    );
  }

  // 自动拆分：标题、徽标、字段、操作
  const titleColumn = columns.find((column) => column.mobile === "title") ?? columns[0];
  const badgeColumn = columns.find((column) => column.mobile === "badge");
  const actionColumn = columns.find((column) => column.key === "actions");
  const fieldColumns = columns
    .filter(
      (column) =>
        column !== titleColumn &&
        column !== badgeColumn &&
        column !== actionColumn &&
        column.mobile !== "hidden",
    )
    .slice(0, mobileMaxFields);

  return (
    <MobileCardList
      items={items}
      rowKey={rowKey}
      loading={loading}
      emptyText={emptyText}
      title={(row) =>
        mobileTitle ? mobileTitle(row) : titleColumn ? renderCell(titleColumn, row) : rowKey(row)
      }
      badge={
        mobileBadge
          ? (row) => mobileBadge(row)
          : badgeColumn
            ? (row) => renderCell(badgeColumn, row)
            : undefined
      }
      fields={fieldColumns.map((column) => ({
        label: labelOf(column),
        render: (row: T) => renderCell(column, row),
      }))}
      actions={
        mobileActions ??
        (actionColumn?.cell ? (row: T) => actionColumn.cell?.(row) : undefined)
      }
      sentinelRef={sentinelRef}
      loadingMore={loadingMore}
      hasNextPage={hasNextPage}
    />
  );
}
