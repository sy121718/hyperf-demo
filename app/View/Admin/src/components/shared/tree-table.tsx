import { Fragment, type ReactNode } from "react";
import { ChevronDown, ChevronRight } from "lucide-react";

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@rbac/ui/components/ui/table";
import { Skeleton } from "@rbac/ui/components/ui/skeleton";
import { Button } from "@rbac/ui/components/ui/button";

/**
 * 树形表格展示壳。
 *
 * 后端返回的是已经组装好的树（children 嵌套），不需要前端父子推导，
 * 这里只负责把树摊平成可折叠的行。展开状态由调用方按 id 集合控制，
 * 便于「全部展开 / 全部收起」这类批量操作。
 */
export interface TreeTableColumn<T> {
  key: string;
  header: ReactNode;
  cell: (row: T) => ReactNode;
  align?: "left" | "right" | "center";
  className?: string;
}

export interface TreeTableProps<T> {
  columns: TreeTableColumn<T>[];
  /** 根节点集合 */
  roots: T[];
  rowKey: (row: T) => number;
  /** 取子节点；返回空数组表示叶子 */
  childrenOf: (row: T) => T[] | undefined;
  /** 第一列显示展开箭头，默认取第一列 */
  expandedIds: Set<number>;
  onToggle: (id: number) => void;
  loading?: boolean;
  emptyText?: string;
  depth?: number;
}

function alignClass(align?: "left" | "right" | "center"): string {
  return align === "right" ? "text-right" : align === "center" ? "text-center" : "";
}

export function TreeTable<T>({
  columns,
  roots,
  rowKey,
  childrenOf,
  expandedIds,
  onToggle,
  loading = false,
  emptyText = "暂无数据",
  depth = 0,
}: TreeTableProps<T>) {
  if (loading) {
    return (
      <div className="overflow-x-auto rounded-xl border bg-card shadow-sm">
        <Table>
          <TableHeader>
            <TableRow>
              {columns.map((column) => (
                <TableHead key={column.key} className={column.className}>
                  {column.header}
                </TableHead>
              ))}
            </TableRow>
          </TableHeader>
          <TableBody>
            {Array.from({ length: 5 }, (_, index) => (
              <TableRow key={`skeleton-${index}`}>
                {columns.map((column) => (
                  <TableCell key={column.key}>
                    <Skeleton className="h-4 w-full max-w-40" />
                  </TableCell>
                ))}
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    );
  }

  if (roots.length === 0) {
    return (
      <div className="rounded-xl border bg-card p-10 text-center text-sm text-muted-foreground shadow-sm">
        {emptyText}
      </div>
    );
  }

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
          {roots.map((node) => {
            const id = rowKey(node);
            return (
              <Fragment key={id}>
                <TreeRow
                  node={node}
                  columns={columns}
                  depth={depth}
                  expanded={expandedIds.has(id)}
                  hasChildren={Boolean(childrenOf(node)?.length)}
                  onToggle={() => onToggle(id)}
                  rowKey={rowKey}
                  childrenOf={childrenOf}
                  expandedIds={expandedIds}
                  onToggleChild={onToggle}
                />
              </Fragment>
            );
          })}
        </TableBody>
      </Table>
    </div>
  );
}

interface TreeRowProps<T> extends Omit<TreeTableProps<T>, "roots" | "loading" | "emptyText"> {
  node: T;
  depth: number;
  expanded: boolean;
  hasChildren: boolean;
  onToggle: () => void;
  onToggleChild: (id: number) => void;
}

function TreeRow<T>({
  node,
  columns,
  depth,
  expanded,
  hasChildren,
  onToggle,
  rowKey,
  childrenOf,
  expandedIds,
  onToggleChild,
}: TreeRowProps<T>) {
  const children = childrenOf(node) ?? [];

  return (
    <>
      <TableRow>
        {columns.map((column, index) => (
          <TableCell key={column.key} className={`py-3 ${alignClass(column.align)}`}>
            {index === 0 ? (
              <div className="flex items-center" style={{ paddingLeft: depth * 16 }}>
                {hasChildren ? (
                  <Button
                    variant="ghost"
                    size="icon-xs"
                    className="mr-1"
                    onClick={onToggle}
                    title={expanded ? "收起" : "展开"}
                  >
                    {expanded ? <ChevronDown /> : <ChevronRight />}
                  </Button>
                ) : (
                  <span className="mr-1 inline-block size-6" />
                )}
                {column.cell(node)}
              </div>
            ) : (
              column.cell(node)
            )}
          </TableCell>
        ))}
      </TableRow>

      {expanded
        ? children.map((child) => {
            const childId = rowKey(child);
            return (
              <Fragment key={childId}>
                <TreeRow
                  node={child}
                  columns={columns}
                  depth={depth + 1}
                  expanded={expandedIds.has(childId)}
                  hasChildren={Boolean(childrenOf(child)?.length)}
                  onToggle={() => onToggleChild(childId)}
                  rowKey={rowKey}
                  childrenOf={childrenOf}
                  expandedIds={expandedIds}
                  onToggleChild={onToggleChild}
                />
              </Fragment>
            );
          })
        : null}
    </>
  );
}
