import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Pencil, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@rbac/ui/components/ui/button";
import { Badge } from "@rbac/ui/components/ui/badge";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@rbac/ui/components/ui/select";
import { PageHeader } from "@/components/shared/page-header";
import { ConfirmDialog } from "@/components/shared/confirm-dialog";
import { StatusBadge } from "@/components/shared/status-badge";
import { RowActions } from "@/components/shared/row-actions";
import type { DataTableColumn } from "@/components/shared/data-table";
import { ResponsiveDataList } from "@/components/shared/responsive-data-list";
import { ListToolbar } from "@/components/shared/list-toolbar";
import { ConfigFormDialog } from "@/pages/system/config/config-form-dialog";
import { deleteConfig, getConfigList } from "@/api/config";
import { formatDateTime } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useResponsiveList } from "@/lib/use-responsive-list";
import type { ConfigRow } from "@/types/config";

export default function ConfigPage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | undefined>(undefined);

  const canCreate = usePermission("config:create");
  const canUpdate = usePermission("config:update");
  const canDelete = usePermission("config:delete");

  const { items, total, loading, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<ConfigRow>({
      queryKey: queryKeys.config.all,
      fetcher: getConfigList,
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
    });

  const removeMutation = useMutation({
    mutationFn: deleteConfig,
    onSuccess: () => {
      toast.success("配置已删除");
      void queryClient.invalidateQueries({ queryKey: queryKeys.config.all });
    },
  });

  /** 配置项名称列表，表格与卡片共用；最多展示 max 个，其余折叠成计数 */
  const renderConfigKeys = (row: ConfigRow, max: number) => {
    const keys = row.config_data ? Object.keys(row.config_data) : [];
    if (keys.length === 0) {
      return <span className="text-muted-foreground">空</span>;
    }
    return (
      <div className="flex flex-wrap gap-1">
        {keys.slice(0, max).map((key) => (
          <Badge key={key} variant="secondary">
            {key}
          </Badge>
        ))}
        {keys.length > max ? <Badge variant="outline">+{keys.length - max}</Badge> : null}
      </div>
    );
  };

  /** 行操作在表格与卡片里共用 */
  const renderActions = (row: ConfigRow) => (
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
          title="删除配置"
          description={`确定删除配置分组「${row.group_name}」吗？读取该分组的业务会取不到配置。`}
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
  );

  const columns: DataTableColumn<ConfigRow>[] = [
    {
      key: "group_key",
      header: "分组键",
      cell: (row) => <code className="text-xs">{row.group_key}</code>,
    },
    { key: "group_name", header: "分组名称", mobile: "title" },
    {
      key: "config_data",
      header: "配置项",
      cell: (row) => renderConfigKeys(row, 4),
    },
    {
      key: "status",
      header: "状态",
      mobile: "badge",
      cell: (row) => <StatusBadge status={row.status} />,
    },
    {
      key: "remark",
      header: "备注",
      cell: (row) => (
        <span className="line-clamp-1 max-w-48 text-muted-foreground">{row.remark || "-"}</span>
      ),
    },
    { key: "create_time", header: "创建时间", cell: (row) => formatDateTime(row.create_time) },
    {
      key: "actions",
      header: "操作",
      align: "right",
      cell: renderActions,
    },
  ];

  const statusFilter = (
    <Select
      value={String(listState.filters.status ?? "all")}
      onValueChange={(value) =>
        listState.setFilter("status", value === "all" ? undefined : Number(value))
      }
    >
      <SelectTrigger className="w-full sm:w-32">
        <SelectValue placeholder="状态" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="all">全部状态</SelectItem>
        <SelectItem value="1">启用</SelectItem>
        <SelectItem value="0">禁用</SelectItem>
      </SelectContent>
    </Select>
  );

  const createButton = canCreate ? (
    <Button
      onClick={() => {
        setEditingId(undefined);
        setFormOpen(true);
      }}
    >
      <Plus />
      新增配置
    </Button>
  ) : null;

  return (
    <div className="space-y-4">
      <PageHeader
        title="系统配置"
        description="按分组存放键值对配置，修改后相关缓存会自动失效。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索分组键 / 名称 / 备注"
        createButton={createButton}
        filters={statusFilter}
        onReset={listState.reset}
        hasActiveFilters={listState.filters.status !== undefined}
        summary={<Badge variant="secondary">共 {total} 组</Badge>}
      />

      <ResponsiveDataList
        columns={columns}
        items={items}
        total={total}
        loading={loading}
        loadingMore={loadingMore}
        hasNextPage={hasNextPage}
        sentinelRef={sentinelRef}
        isMobile={isMobile}
        rowKey={(row) => row.id}
        emptyText="还没有配置分组"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />

      <ConfigFormDialog open={formOpen} onOpenChange={setFormOpen} configId={editingId} />
    </div>
  );
}
