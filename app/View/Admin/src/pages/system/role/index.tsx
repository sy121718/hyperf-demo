import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
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
import { ListToolbar } from "@/components/shared/list-toolbar";
import { RoleFormDialog } from "@/pages/system/role/role-form-dialog";
import { deleteRole, getRoleList } from "@/api/role";
import { getPermissionOptions } from "@/api/permission";
import { formatDateTime } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { ResponsiveDataList } from "@/components/shared/responsive-data-list";
import { useResponsiveList } from "@/lib/use-responsive-list";
import type { RoleRow } from "@/types/role";

export default function RolePage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | undefined>(undefined);

  const canCreate = usePermission("role:create");
  const canUpdate = usePermission("role:update");
  const canDelete = usePermission("role:delete");

  const { items, total, loading, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<RoleRow>({
      queryKey: queryKeys.role.all,
      fetcher: getRoleList,
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
    });

  /** 权限码候选：权限字典的 options 接口，扁平列出即可 */
  const { data: permissionOptions } = useQuery({
    queryKey: queryKeys.permission.options,
    queryFn: getPermissionOptions,
    staleTime: 5 * 60_000,
  });

  const removeMutation = useMutation({
    mutationFn: deleteRole,
    onSuccess: () => {
      toast.success("角色已删除");
      void queryClient.invalidateQueries({ queryKey: queryKeys.role.all });
    },
  });

  const permissionSelectOptions = useMemo(
    () =>
      (permissionOptions ?? []).map((item) => ({
        value: item.perm_code,
        label: item.perm_name,
        hint: item.perm_code,
      })),
    [permissionOptions],
  );

  const openCreate = () => {
    setEditingId(undefined);
    setFormOpen(true);
  };

  const openEdit = (id: number) => {
    setEditingId(id);
    setFormOpen(true);
  };

  /** 行操作在表格与卡片里共用 */
  const renderActions = (row: RoleRow) => (
    <RowActions>
      {canUpdate ? (
        <Button variant="ghost" size="icon-sm" title="编辑" onClick={() => openEdit(row.id)}>
          <Pencil />
        </Button>
      ) : null}
      {canDelete ? (
        <ConfirmDialog
          title="删除角色"
          description={`确定删除角色「${row.role_name}」吗？已分配该角色的管理员会失去对应权限。`}
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

  const columns: DataTableColumn<RoleRow>[] = [
    { key: "role_name", header: "角色名称", mobile: "title" },
    {
      key: "role_code",
      header: "角色标识",
      cell: (row) => <code className="text-xs text-muted-foreground">{row.role_code}</code>,
    },
    { key: "sort_order", header: "排序", align: "right" },
    { key: "status", header: "状态", mobile: "badge", cell: (row) => <StatusBadge status={row.status} /> },
    {
      key: "remark",
      header: "备注",
      cell: (row) => (
        <span className="line-clamp-1 max-w-60 text-muted-foreground">{row.remark || "-"}</span>
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
    <Button onClick={openCreate}>
      <Plus />
      新增角色
    </Button>
  ) : null;

  return (
    <div className="space-y-4">
      <PageHeader
        title="角色管理"
        description="角色决定管理员能看到哪些菜单、能调用哪些接口。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索角色名称 / 标识 / 备注"
        createButton={createButton}
        filters={statusFilter}
        onReset={listState.reset}
        hasActiveFilters={listState.filters.status !== undefined}
        summary={<Badge variant="secondary">共 {total} 个角色</Badge>}
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
        emptyText="还没有角色，点上方新增"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />

      <RoleFormDialog
        open={formOpen}
        onOpenChange={setFormOpen}
        roleId={editingId}
        permissionOptions={permissionSelectOptions}
      />
    </div>
  );
}
