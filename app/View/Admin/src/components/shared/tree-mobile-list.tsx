import type { ReactNode } from "react";
import { ChevronDown, ChevronRight } from "lucide-react";

import { Button } from "@rbac/ui/components/ui/button";
import { Skeleton } from "@rbac/ui/components/ui/skeleton";
import { cn } from "@rbac/ui/lib/utils";

/**
 * 手机端树形列表。
 *
 * 树表在窄屏没法看，这里把每个节点渲染成一张卡片：
 * 有子节点时卡片头部可点开折叠，子节点缩进显示在下方。
 */
export interface TreeMobileNode {
  id: number;
  children?: TreeMobileNode[];
}

export interface TreeMobileListProps<T extends TreeMobileNode> {
  roots: T[];
  title: (node: T) => ReactNode;
  /** 标题右侧徽标，如状态 */
  badge?: (node: T) => ReactNode;
  /** 卡片内的字段行 */
  fields: Array<{ label: string; render: (node: T) => ReactNode }>;
  actions?: (node: T) => ReactNode;
  expandedIds: Set<number>;
  onToggle: (id: number) => void;
  loading?: boolean;
  emptyText?: string;
  depth?: number;
}

export function TreeMobileList<T extends TreeMobileNode>({
  roots,
  title,
  badge,
  fields,
  actions,
  expandedIds,
  onToggle,
  loading = false,
  emptyText = "暂无数据",
  depth = 0,
}: TreeMobileListProps<T>) {
  if (loading) {
    return (
      <div className="space-y-3">
        {Array.from({ length: 4 }, (_, index) => (
          <div key={`skeleton-${index}`} className="space-y-2 rounded-xl border bg-card p-4">
            <Skeleton className="h-5 w-1/2" />
            <Skeleton className="h-4 w-3/4" />
          </div>
        ))}
      </div>
    );
  }

  if (roots.length === 0) {
    return (
      <div className="rounded-xl border bg-card p-10 text-center text-sm text-muted-foreground">
        {emptyText}
      </div>
    );
  }

  return (
    <div className={cn("space-y-2", depth > 0 && "mt-2 border-l pl-3")}>
      {roots.map((node) => {
        const children = (node.children ?? []) as T[];
        const hasChildren = children.length > 0;
        const expanded = expandedIds.has(node.id);

        return (
          <div key={node.id} className="space-y-2">
            <div className="space-y-2 rounded-xl border bg-card p-3.5 shadow-sm">
              <div className="flex items-start justify-between gap-2">
                <div className="flex min-w-0 items-center gap-1">
                  {hasChildren ? (
                    <Button
                      variant="ghost"
                      size="icon-xs"
                      className="-ml-1"
                      title={expanded ? "收起" : "展开"}
                      onClick={() => onToggle(node.id)}
                    >
                      {expanded ? <ChevronDown /> : <ChevronRight />}
                    </Button>
                  ) : (
                    <span className="inline-block size-6" />
                  )}
                  <span className="min-w-0 font-medium">{title(node)}</span>
                </div>
                {badge ? <div className="shrink-0">{badge(node)}</div> : null}
              </div>

              <dl className="grid grid-cols-2 gap-x-3 gap-y-1.5 pl-1 text-sm">
                {fields.map((field) => (
                  <div key={field.label} className="min-w-0">
                    <dt className="text-xs text-muted-foreground">{field.label}</dt>
                    <dd className="truncate">{field.render(node)}</dd>
                  </div>
                ))}
              </dl>

              {actions ? (
                <div className="flex items-center justify-end gap-1 border-t pt-2">
                  {actions(node)}
                </div>
              ) : null}
            </div>

            {expanded && hasChildren ? (
              <TreeMobileList
                roots={children}
                title={title}
                badge={badge}
                fields={fields}
                actions={actions}
                expandedIds={expandedIds}
                onToggle={onToggle}
                depth={depth + 1}
              />
            ) : null}
          </div>
        );
      })}
    </div>
  );
}
