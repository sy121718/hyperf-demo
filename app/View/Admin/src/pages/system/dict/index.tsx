import { Fragment, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { ChevronDown, ChevronRight, Pencil, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@rbac/ui/components/ui/button";
import { Badge } from "@rbac/ui/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@rbac/ui/components/ui/table";
import { Skeleton } from "@rbac/ui/components/ui/skeleton";
import { PageHeader } from "@/components/shared/page-header";
import { SearchInput } from "@/components/shared/search-input";
import { ConfirmDialog } from "@/components/shared/confirm-dialog";
import { StatusBadge } from "@/components/shared/status-badge";
import { RowActions } from "@/components/shared/row-actions";
import { DictFormDialog } from "@/pages/system/dict/dict-form-dialog";
import { deleteDict, getDictItems, getDictTree } from "@/api/dict";
import { formatDateTime } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useIsMobile } from "@/lib/use-responsive-list";

/**
 * 字典页。
 *
 * 接口叫 tree 但返回的是扁平数组（字典没有父子层级），
 * 展开行才去拉该字典的字典项，避免一次请求把全部字典项带回来。
 */
export default function DictPage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | undefined>(undefined);

  const isMobile = useIsMobile();

  const canCreate = usePermission("dict:create");
  const canUpdate = usePermission("dict:update");
  const canDelete = usePermission("dict:delete");

  const { data, isLoading } = useQuery({
    queryKey: queryKeys.dict.tree(listState.params),
    queryFn: () => getDictTree(listState.params),
  });

  const removeMutation = useMutation({
    mutationFn: deleteDict,
    onSuccess: () => {
      toast.success("字典已删除");
      void queryClient.invalidateQueries({ queryKey: queryKeys.dict.all });
    },
  });

  const rows = data ?? [];

  return (
    <div className="space-y-4">
      <PageHeader
        title="字典管理"
        description="字典为业务提供可维护的枚举值，展开可查看字典项。"
        actions={
          canCreate ? (
            <Button
              onClick={() => {
                setEditingId(undefined);
                setFormOpen(true);
              }}
            >
              <Plus />
              新增字典
            </Button>
          ) : null
        }
      />

      <div className="flex flex-wrap items-center gap-2">
        <SearchInput
          value={listState.search}
          onChange={listState.setSearch}
          placeholder="搜索字典名称 / 编码"
        />
        <Button variant="outline" onClick={listState.reset}>
          重置
        </Button>
        <Badge variant="secondary">共 {rows.length} 个字典</Badge>
      </div>

      {isMobile ? (
        <div className="space-y-3">
          {rows.length === 0 ? (
            <div className="rounded-xl border bg-card p-10 text-center text-sm text-muted-foreground">
              暂无字典
            </div>
          ) : (
            rows.map((row) => (
              <div key={row.id} className="space-y-3 rounded-xl border bg-card p-4 shadow-sm">
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <p className="font-medium">{row.dict_name}</p>
                    <code className="text-xs text-muted-foreground">{row.dict_code}</code>
                  </div>
                  <StatusBadge status={row.status} />
                </div>

                <div className="grid grid-cols-2 gap-x-3 gap-y-1.5 text-sm">
                  <div>
                    <p className="text-xs text-muted-foreground">类型</p>
                    <p>{row.dict_type}</p>
                  </div>
                  <div>
                    <p className="text-xs text-muted-foreground">字典项</p>
                    <p>{row.items?.length ?? 0} 项</p>
                  </div>
                  <div className="col-span-2">
                    <p className="text-xs text-muted-foreground">创建时间</p>
                    <p>{formatDateTime(row.create_time)}</p>
                  </div>
                </div>

                <div className="flex items-center justify-end gap-1 border-t pt-2">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => setExpandedId(expandedId === row.id ? null : row.id)}
                  >
                    {expandedId === row.id ? "收起字典项" : "查看字典项"}
                  </Button>
                  {canUpdate ? (
                    <Button
                      variant="ghost"
                      size="icon-sm"
                      title="编辑"
                      onClick={() => {
                        setEditingId(row.id);
                        setFormOpen(true);
                      }}
                    >
                      <Pencil />
                    </Button>
                  ) : null}
                  {canDelete ? (
                    <ConfirmDialog
                      title="删除字典"
                      description={`确定删除字典「${row.dict_name}」吗？引用该字典的页面会取不到枚举值。`}
                      destructive
                      onConfirm={() => removeMutation.mutateAsync(row.id).then(() => undefined)}
                      trigger={
                        <Button variant="ghost" size="icon-sm" title="删除">
                          <Trash2 className="text-destructive" />
                        </Button>
                      }
                    />
                  ) : null}
                </div>

                {expandedId === row.id ? (
                  <div className="rounded-md bg-muted/40 p-2">
                    <DictItemsPanel dictId={row.id} />
                  </div>
                ) : null}
              </div>
            ))
          )}
        </div>
      ) : (
      <div className="overflow-x-auto rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>字典名称</TableHead>
              <TableHead>字典编码</TableHead>
              <TableHead>类型</TableHead>
              <TableHead>字典项</TableHead>
              <TableHead>状态</TableHead>
              <TableHead>创建时间</TableHead>
              <TableHead className="text-right">操作</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading ? (
              Array.from({ length: 5 }, (_, index) => (
                <TableRow key={`skeleton-${index}`}>
                  {Array.from({ length: 7 }, (_, cellIndex) => (
                    <TableCell key={cellIndex}>
                      <Skeleton className="h-4 w-full max-w-40" />
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : rows.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="h-24 text-center text-muted-foreground">
                  暂无字典
                </TableCell>
              </TableRow>
            ) : (
              rows.map((row) => {
                const expanded = expandedId === row.id;
                return (
                  <Fragment key={row.id}>
                    <TableRow>
                      <TableCell>
                        <div className="flex items-center">
                          <Button
                            variant="ghost"
                            size="icon-xs"
                            className="mr-1"
                            title={expanded ? "收起" : "展开字典项"}
                            onClick={() => setExpandedId(expanded ? null : row.id)}
                          >
                            {expanded ? <ChevronDown /> : <ChevronRight />}
                          </Button>
                          <span className="font-medium">{row.dict_name}</span>
                        </div>
                      </TableCell>
                      <TableCell>
                        <code className="text-xs text-muted-foreground">{row.dict_code}</code>
                      </TableCell>
                      <TableCell>{row.dict_type}</TableCell>
                      <TableCell>
                        <Badge variant="secondary">{row.items?.length ?? 0} 项</Badge>
                      </TableCell>
                      <TableCell>
                        <StatusBadge status={row.status} />
                      </TableCell>
                      <TableCell>{formatDateTime(row.create_time)}</TableCell>
                      <TableCell>
                        <RowActions>
                          {canUpdate ? (
                            <Button
                              variant="ghost"
                              size="icon-sm"
                              title="编辑"
                              onClick={() => {
                                setEditingId(row.id);
                                setFormOpen(true);
                              }}
                            >
                              <Pencil />
                            </Button>
                          ) : null}
                          {canDelete ? (
                            <ConfirmDialog
                              title="删除字典"
                              description={`确定删除字典「${row.dict_name}」吗？引用该字典的页面会取不到枚举值。`}
                              destructive
                              onConfirm={() => removeMutation.mutateAsync(row.id).then(() => undefined)}
                              trigger={
                                <Button variant="ghost" size="icon-sm" title="删除">
                                  <Trash2 className="text-destructive" />
                                </Button>
                              }
                            />
                          ) : null}
                        </RowActions>
                      </TableCell>
                    </TableRow>

                    {expanded ? (
                      <TableRow>
                        <TableCell colSpan={7} className="bg-muted/40">
                          <DictItemsPanel dictId={row.id} />
                        </TableCell>
                      </TableRow>
                    ) : null}
                  </Fragment>
                );
              })
            )}
          </TableBody>
        </Table>
      </div>
      )}

      <DictFormDialog open={formOpen} onOpenChange={setFormOpen} dictId={editingId} />
    </div>
  );
}

/** 展开行里的字典项明细，独立组件是为了让查询按需触发 */
function DictItemsPanel({ dictId }: { dictId: number }) {
  const { data, isLoading } = useQuery({
    queryKey: queryKeys.dict.items(dictId),
    queryFn: () => getDictItems(dictId),
    staleTime: 60_000,
  });

  if (isLoading) {
    return <Skeleton className="h-10 w-full" />;
  }

  if (!data || data.length === 0) {
    return <p className="text-sm text-muted-foreground">该字典暂无字典项</p>;
  }

  return (
    <div className="flex flex-wrap gap-2">
      {data.map((item) => (
        <div key={item.id} className="rounded-md border bg-background px-2 py-1 text-xs">
          <span className="font-medium">{item.item_label}</span>
          <span className="ml-1.5 text-muted-foreground">{item.item_value}</span>
        </div>
      ))}
    </div>
  );
}
