import type { ReactNode } from "react";

import { Skeleton } from "@rbac/ui/components/ui/skeleton";
import { cn } from "@rbac/ui/lib/utils";

/**
 * 手机端列表：一条数据一张卡片，纵向滚动。
 *
 * 表格在窄屏下横向滚动既难看也难操作，所以手机端统一换成卡片；
 * 卡片内容由页面给出主信息 + 次要字段 + 操作区。
 */
export interface MobileCardListProps<T> {
  items: T[];
  rowKey: (item: T) => string | number;
  loading?: boolean;
  emptyText?: string;
  /** 卡片标题区，通常是名称或主标识 */
  title: (item: T) => ReactNode;
  /** 标题右侧的徽标，如状态 */
  badge?: (item: T) => ReactNode;
  /** 标题下方的键值对，按数组顺序渲染 */
  fields: Array<{ label: string; render: (item: T) => ReactNode }>;
  /** 卡片底部操作按钮 */
  actions?: (item: T) => ReactNode;
  /** 触底加载的哨兵元素 */
  sentinelRef?: React.RefObject<HTMLDivElement | null>;
  /** 底部加载中的提示 */
  loadingMore?: boolean;
  hasNextPage?: boolean;
}

export function MobileCardList<T>({
  items,
  rowKey,
  loading = false,
  emptyText = "暂无数据",
  title,
  badge,
  fields,
  actions,
  sentinelRef,
  loadingMore = false,
  hasNextPage = false,
}: MobileCardListProps<T>) {
  if (loading) {
    return (
      <div className="space-y-3">
        {Array.from({ length: 4 }, (_, index) => (
          <div key={`skeleton-${index}`} className="space-y-2 rounded-xl border bg-card p-4">
            <Skeleton className="h-5 w-1/2" />
            <Skeleton className="h-4 w-3/4" />
            <Skeleton className="h-4 w-2/3" />
          </div>
        ))}
      </div>
    );
  }

  if (items.length === 0) {
    return (
      <div className="rounded-xl border bg-card p-10 text-center text-sm text-muted-foreground">
        {emptyText}
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {items.map((item) => (
        <div
          key={rowKey(item)}
          className={cn("space-y-3 rounded-xl border bg-card p-4 shadow-sm")}
        >
          <div className="flex items-start justify-between gap-2">
            <div className="min-w-0 font-medium">{title(item)}</div>
            {badge ? <div className="shrink-0">{badge(item)}</div> : null}
          </div>

          <dl className="grid grid-cols-2 gap-x-3 gap-y-1.5 text-sm">
            {fields.map((field) => (
              <div key={field.label} className="min-w-0">
                <dt className="text-xs text-muted-foreground">{field.label}</dt>
                <dd className="truncate">{field.render(item)}</dd>
              </div>
            ))}
          </dl>

          {actions ? (
            <div className="flex items-center justify-end gap-1 border-t pt-2">{actions(item)}</div>
          ) : null}
        </div>
      ))}

      {/* 哨兵：进入视口触发下一页 */}
      <div ref={sentinelRef} className="h-1" />

      {loadingMore ? (
        <p className="py-3 text-center text-sm text-muted-foreground">加载中…</p>
      ) : hasNextPage ? (
        <p className="py-3 text-center text-sm text-muted-foreground">上滑加载更多</p>
      ) : (
        <p className="py-3 text-center text-sm text-muted-foreground">已经到底了</p>
      )}
    </div>
  );
}
