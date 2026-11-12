import { useState } from "react";

import { Badge } from "@rbac/ui/components/ui/badge";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@rbac/ui/components/ui/select";
import { PageHeader } from "@/components/shared/page-header";
import { StatusBadge } from "@/components/shared/status-badge";
import { ListScopeTabs, type ListScope } from "@/components/shared/list-scope-tabs";
import type { DataTableColumn } from "@/components/shared/data-table";
import { ResponsiveDataList } from "@/components/shared/responsive-data-list";
import { ListToolbar } from "@/components/shared/list-toolbar";
import { getLoginLogs } from "@/api/log";
import { formatDateTime } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useResponsiveList } from "@/lib/use-responsive-list";
import type { LoginLogRow } from "@/types/log";

/**
 * 登录日志。
 *
 * 列表接口支持 username / ip / date_from / date_to 几个筛选，
 * search 走模型白名单（username / ip_address / location）。
 */
export default function LoginLogPage() {
  const listState = useListState();
  const [scope, setScope] = useState<ListScope>("all");

  const canViewAll = usePermission("log:loginLog");
  const canViewMine = usePermission("log:myLoginLog");
  const effectiveScope: ListScope = canViewAll ? scope : "mine";

  // scope 决定调哪个接口，拼进 key 才能分出「全部/我的」两份缓存
  const { items, total, loading, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<LoginLogRow>({
      queryKey: [...queryKeys.log.all, "login", effectiveScope],
      fetcher: (params) => getLoginLogs(effectiveScope, params),
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
      enabled: canViewAll || canViewMine,
    });

  const columns: DataTableColumn<LoginLogRow>[] = [
    { key: "username", header: "用户名", mobile: "title", cell: (row) => row.username || "-" },
    {
      key: "status",
      header: "结果",
      mobile: "badge",
      cell: (row) => <StatusBadge status={row.status} />,
    },
    {
      key: "ip_address",
      header: "IP / 位置",
      cell: (row) => (
        <div className="space-y-0.5">
          <code className="text-sm">{row.ip_address || "-"}</code>
          <div className="text-xs text-muted-foreground">{row.location || "-"}</div>
        </div>
      ),
    },
    { key: "device_type", header: "设备", cell: (row) => row.device_type || "-" },
    {
      key: "user_agent",
      header: "UA",
      cell: (row) => (
        <span className="line-clamp-1 max-w-64 text-xs text-muted-foreground" title={row.user_agent ?? ""}>
          {row.user_agent || "-"}
        </span>
      ),
    },
    {
      key: "msg",
      header: "说明",
      cell: (row) => (
        <span className="line-clamp-1 max-w-48 text-xs">{row.msg || "-"}</span>
      ),
    },
    { key: "login_time", header: "登录时间", cell: (row) => formatDateTime(row.login_time) },
  ];

  // 登录结果：0 失败 / 1 成功
  const statusFilter = (
    <Select
      value={String(listState.filters.status ?? "all")}
      onValueChange={(value) =>
        listState.setFilter("status", value === "all" ? undefined : Number(value))
      }
    >
      <SelectTrigger className="w-full sm:w-32">
        <SelectValue placeholder="结果" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="all">全部结果</SelectItem>
        <SelectItem value="1">成功</SelectItem>
        <SelectItem value="0">失败</SelectItem>
      </SelectContent>
    </Select>
  );

  return (
    <div className="space-y-4">
      <PageHeader
        title="登录日志"
        description="记录每次登录尝试，成功与失败都会入库。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索用户名 / IP / 位置"
        filters={statusFilter}
        onReset={listState.reset}
        hasActiveFilters={listState.filters.status !== undefined}
        summary={<Badge variant="secondary">共 {total} 条</Badge>}
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
        emptyText="暂无登录日志"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />
    </div>
  );
}
