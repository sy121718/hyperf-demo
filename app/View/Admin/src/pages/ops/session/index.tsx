import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Eraser, LogOut } from "lucide-react";
import { toast } from "sonner";

import { Badge } from "@rbac/ui/components/ui/badge";
import { Button } from "@rbac/ui/components/ui/button";
import { PageHeader } from "@/components/shared/page-header";
import { ConfirmDialog } from "@/components/shared/confirm-dialog";
import { StatusBadge } from "@/components/shared/status-badge";
import { RowActions } from "@/components/shared/row-actions";
import { ListScopeTabs, type ListScope } from "@/components/shared/list-scope-tabs";
import type { DataTableColumn } from "@/components/shared/data-table";
import { ResponsiveDataList } from "@/components/shared/responsive-data-list";
import { ListToolbar } from "@/components/shared/list-toolbar";
import {
  cleanExpiredSessions,
  getSessionList,
  kickSession,
} from "@/api/session";
import { formatDateTime } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useResponsiveList } from "@/lib/use-responsive-list";
import type { SessionRow } from "@/types/session";

export default function SessionPage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [scope, setScope] = useState<ListScope>("all");

  const canViewAll = usePermission("session:list");
  const canViewMine = usePermission("session:myList");
  const canKick = usePermission("session:kickout");
  const canClean = usePermission("session:clean");

  const effectiveScope: ListScope = canViewAll ? scope : "mine";

  // scope 决定接口，拼进 key 让「全部/我的」分开缓存
  const { items, total, loading, refreshing, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<SessionRow>({
      queryKey: [...queryKeys.session.all, effectiveScope],
      fetcher: (params) => getSessionList(effectiveScope, params),
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
      enabled: canViewAll || canViewMine,
    });

  const invalidate = () => void queryClient.invalidateQueries({ queryKey: queryKeys.session.all });

  const kickMutation = useMutation({
    mutationFn: kickSession,
    onSuccess: () => {
      toast.success("会话已踢下线");
      invalidate();
    },
  });

  const cleanMutation = useMutation({
    mutationFn: cleanExpiredSessions,
    onSuccess: (result) => {
      toast.success(`已清理 ${result.count} 个过期会话`);
      invalidate();
    },
  });

  /** 行操作在表格与卡片里共用 */
  const renderActions = (row: SessionRow) => (
    <RowActions>
      {canKick ? (
        <ConfirmDialog
          title="强制下线"
          description="该会话的 token 会立即失效，用户需要重新登录。"
          destructive
          confirmText="下线"
          onConfirm={() => kickMutation.mutateAsync(row.id).then(() => undefined)}
          trigger={
            <Button variant="ghost" size="icon-sm" title="踢下线">
              <LogOut className="text-destructive" />
            </Button>
          }
        />
      ) : null}
    </RowActions>
  );

  const columns: DataTableColumn<SessionRow>[] = [
    {
      key: "admin_name",
      header: "账号",
      cell: (row) => (
        <div className="space-y-0.5">
          <div>{row.admin_name || `#${row.admin_id}`}</div>
          <div className="text-xs text-muted-foreground">{row.admin_username || "-"}</div>
        </div>
      ),
    },
    {
      key: "login_ip",
      header: "登录 IP",
      mobile: "title",
      cell: (row) => (
        <div className="space-y-0.5">
          <code className="text-sm">{row.login_ip || "-"}</code>
          <div className="text-xs text-muted-foreground">{row.location || "-"}</div>
        </div>
      ),
    },
    {
      key: "device",
      header: "设备",
      cell: (row) => (
        <div className="space-y-0.5">
          <div>{row.device_type || "-"}</div>
          <div className="max-w-56 truncate text-xs text-muted-foreground" title={row.user_agent ?? ""}>
            {row.user_agent || "-"}
          </div>
        </div>
      ),
    },
    { key: "login_time", header: "登录时间", cell: (row) => formatDateTime(row.login_time) },
    {
      key: "last_active_time",
      header: "最后活跃",
      cell: (row) => formatDateTime(row.last_active_time),
    },
    {
      key: "status",
      header: "状态",
      mobile: "badge",
      cell: (row) => <StatusBadge status={row.status} />,
    },
    {
      key: "actions",
      header: "操作",
      align: "right",
      cell: renderActions,
    },
  ];

  // 会话列表没有业务筛选字段，只保留搜索与作用域切换
  return (
    <div className="space-y-4">
      <PageHeader
        title="在线会话"
        description="会话由登录流程创建，这里可以强制下线或清理已过期的记录。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索用户名"
        onReset={listState.reset}
        summary={<Badge variant="secondary">共 {total} 个会话</Badge>}
        leading={
          <ListScopeTabs
            scope={scope}
            onScopeChange={(next) => {
              setScope(next);
              listState.setPage(1);
            }}
            canViewAll={canViewAll}
            canViewMine={canViewMine}
          />
        }
        createButton={
          canClean ? (
            <ConfirmDialog
              title="清理过期会话"
              description="只会删除已过期的会话记录，不影响在线用户。"
              confirmText="清理"
              onConfirm={() => cleanMutation.mutateAsync().then(() => undefined)}
              trigger={
                <Button variant="outline">
                  <Eraser />
                  清理过期
                </Button>
              }
            />
          ) : null
        }
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
        emptyText="当前没有在线会话"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />
    </div>
  );
}
