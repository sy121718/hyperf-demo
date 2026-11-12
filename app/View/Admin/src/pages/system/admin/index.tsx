import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { KeyRound, Pencil, Plus, ShieldOff, ShieldCheck, Trash2 } from "lucide-react";
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
import { AdminFormDialog } from "@/pages/system/admin/admin-form-dialog";
import { ResetPasswordDialog } from "@/pages/system/admin/reset-password-dialog";
import { banAdmin, deleteAdmin, getAdminList, unbanAdmin } from "@/api/admin";
import { formatDateTime } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useResponsiveList } from "@/lib/use-responsive-list";
import type { AdminRow } from "@/types/admin";

export default function AdminPage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | undefined>(undefined);
  const [resetTarget, setResetTarget] = useState<AdminRow | null>(null);

  const canCreate = usePermission("admin:create");
  const canUpdate = usePermission("admin:update");
  const canDelete = usePermission("admin:delete");
  const canReset = usePermission("admin:resetPassword");
  const canBan = usePermission("admin:ban");

  // 桌面端服务端分页、手机端无限滚动，同一份筛选参数
  const { items, total, loading, refreshing, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<AdminRow>({
      queryKey: queryKeys.admin.all,
      fetcher: getAdminList,
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
    });

  const invalidate = () =>
    void queryClient.invalidateQueries({ queryKey: queryKeys.admin.all });

  const deleteMutation = useMutation({
    mutationFn: deleteAdmin,
    onSuccess: () => {
      toast.success("管理员已删除");
      invalidate();
    },
  });

  const banMutation = useMutation({
    mutationFn: (row: AdminRow) => (row.status === 1 ? banAdmin(row.id) : unbanAdmin(row.id)),
    onSuccess: () => {
      toast.success("状态已更新");
      invalidate();
    },
  });

  /** 行操作在表格与卡片里共用 */
  const renderActions = (row: AdminRow) => (
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
      {canReset ? (
        <Button
          variant="ghost"
          size="icon-sm"
          title="重置密码"
          onClick={() => setResetTarget(row)}
        >
          <KeyRound />
        </Button>
      ) : null}
      {canBan && row.is_admin !== 1 ? (
        <ConfirmDialog
          title={row.status === 1 ? "封禁管理员" : "解封管理员"}
          description={
            row.status === 1
              ? `封禁后「${row.username}」将无法登录。`
              : `解封后「${row.username}」可以重新登录。`
          }
          destructive={row.status === 1}
          confirmText={row.status === 1 ? "封禁" : "解封"}
          onConfirm={() => banMutation.mutateAsync(row).then(() => undefined)}
          trigger={
            <Button variant="ghost" size="icon-sm" title={row.status === 1 ? "封禁" : "解封"}>
              {row.status === 1 ? <ShieldOff /> : <ShieldCheck />}
            </Button>
          }
        />
      ) : null}
      {canDelete && row.is_admin !== 1 ? (
        <ConfirmDialog
          title="删除管理员"
          description={`确定删除「${row.username}」吗？该操作会同时清理其角色关联与登录会话。`}
          destructive
          onConfirm={() => deleteMutation.mutateAsync(row.id).then(() => undefined)}
          trigger={
            <Button variant="ghost" size="icon-sm" title="删除">
              <Trash2 className="text-destructive" />
            </Button>
          }
        />
      ) : null}
    </RowActions>
  );

  const columns: DataTableColumn<AdminRow>[] = [
    { key: "username", header: "用户名", mobile: "title" },
    { key: "name", header: "姓名", cell: (row) => row.name || "-" },
    {
      key: "is_admin",
      header: "类型",
      cell: (row) =>
        row.is_admin === 1 ? <Badge>超级管理员</Badge> : <Badge variant="outline">普通管理员</Badge>,
    },
    {
      key: "status",
      header: "状态",
      mobile: "badge",
      cell: (row) => <StatusBadge status={row.status} />,
    },
    { key: "email", header: "邮箱", cell: (row) => row.email || "-" },
    { key: "phone", header: "手机号", cell: (row) => row.phone || "-" },
    {
      key: "last_login_time",
      header: "最后登录",
      mobileLabel: "最后登录 / IP",
      cell: (row) => (
        <div className="space-y-0.5">
          <div>{formatDateTime(row.last_login_time)}</div>
          <div className="text-xs text-muted-foreground">{row.last_login_ip || "-"}</div>
        </div>
      ),
    },
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
      新增管理员
    </Button>
  ) : null;

  return (
    <div className="space-y-4">
      <PageHeader
        title="管理员"
        description="超管仅由初始化脚本产生，不能在界面上创建或删除。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索用户名 / 姓名 / 邮箱 / 手机号"
        createButton={createButton}
        filters={statusFilter}
        onReset={listState.reset}
        hasActiveFilters={listState.filters.status !== undefined}
        summary={<Badge variant="secondary">共 {total} 人</Badge>}
      />

      <ResponsiveDataList
        columns={columns}
        items={items}
        total={total}
        loading={loading}
        refreshing={refreshing}
        loadingMore={loadingMore}
        hasNextPage={hasNextPage}
        sentinelRef={sentinelRef}
        isMobile={isMobile}
        rowKey={(row) => row.id}
        emptyText="还没有管理员"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />

      <AdminFormDialog open={formOpen} onOpenChange={setFormOpen} adminId={editingId} />

      {resetTarget ? (
        <ResetPasswordDialog
          open={resetTarget !== null}
          onOpenChange={(open) => {
            if (!open) {
              setResetTarget(null);
            }
          }}
          adminId={resetTarget.id}
          username={resetTarget.username}
        />
      ) : null}
    </div>
  );
}
