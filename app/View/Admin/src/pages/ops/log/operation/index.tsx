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
import { ListScopeTabs, type ListScope } from "@/components/shared/list-scope-tabs";
import type { DataTableColumn } from "@/components/shared/data-table";
import { ResponsiveDataList } from "@/components/shared/responsive-data-list";
import { ListToolbar } from "@/components/shared/list-toolbar";
import { getOperationLogs } from "@/api/log";
import { formatDateTime, formatDuration } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useResponsiveList } from "@/lib/use-responsive-list";
import type { OperationLogRow } from "@/types/log";

/**
 * 操作日志。
 *
 * 接口层用 result=success/failed 表达结果，库里存的是 1/0，
 * 所以列渲染按后端返回的数字展示，筛选参数才用字符串。
 */
function ResultBadge({ result }: { result: OperationLogRow["result"] }) {
  const normalized = typeof result === "string" ? (result === "success" ? 1 : 0) : result;
  return normalized === 1 ? (
    <Badge variant="secondary">成功</Badge>
  ) : (
    <Badge variant="destructive">失败</Badge>
  );
}

export default function OperationLogPage() {
  const listState = useListState();
  const [scope, setScope] = useState<ListScope>("all");

  const canViewAll = usePermission("log:operationLog");
  const canViewMine = usePermission("log:myOperationLog");
  const effectiveScope: ListScope = canViewAll ? scope : "mine";

  // scope 决定调哪个接口，拼进 key 才能分出「全部/我的」两份缓存
  const { items, total, loading, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<OperationLogRow>({
      queryKey: [...queryKeys.log.all, "operation", effectiveScope],
      fetcher: (params) => getOperationLogs(effectiveScope, params),
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
      enabled: canViewAll || canViewMine,
    });

  const columns: DataTableColumn<OperationLogRow>[] = [
    {
      key: "operation",
      header: "操作",
      mobile: "title",
      cell: (row) => (
        <div className="space-y-0.5">
          <div className="font-medium">{row.operation || "-"}</div>
          <div className="text-xs text-muted-foreground">{row.service_method || "-"}</div>
        </div>
      ),
    },
    {
      key: "api",
      header: "接口",
      cell: (row) => (
        <div className="flex items-center gap-1.5 text-xs">
          <Badge variant="outline">{row.http_method || "ANY"}</Badge>
          <span className="max-w-56 truncate text-muted-foreground">{row.api_path || "-"}</span>
        </div>
      ),
    },
    {
      key: "result",
      header: "结果",
      mobile: "badge",
      cell: (row) => <ResultBadge result={row.result} />,
    },
    {
      key: "execution_time",
      header: "耗时",
      align: "right",
      cell: (row) => formatDuration(row.execution_time),
    },
    {
      key: "ip",
      header: "IP / 位置",
      cell: (row) => (
        <div className="space-y-0.5">
          <code className="text-sm">{row.ip || "-"}</code>
          <div className="text-xs text-muted-foreground">{row.location || "-"}</div>
        </div>
      ),
    },
    { key: "admin_id", header: "管理员 ID", align: "right", cell: (row) => row.admin_id },
    { key: "create_time", header: "操作时间", cell: (row) => formatDateTime(row.create_time) },
  ];

  // 接口层用 success / failed 表达结果，与后端 LogRequest 的校验规则一致
  const resultFilter = (
    <Select
      value={String(listState.filters.result ?? "all")}
      onValueChange={(value) =>
        listState.setFilter("result", value === "all" ? undefined : value)
      }
    >
      <SelectTrigger className="w-full sm:w-32">
        <SelectValue placeholder="结果" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="all">全部结果</SelectItem>
        <SelectItem value="success">成功</SelectItem>
        <SelectItem value="failed">失败</SelectItem>
      </SelectContent>
    </Select>
  );

  return (
    <div className="space-y-4">
      <PageHeader
        title="操作日志"
        description="记录写操作与关键接口调用，含耗时与结果。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索操作名 / 接口 / 内容"
        filters={resultFilter}
        onReset={listState.reset}
        hasActiveFilters={listState.filters.result !== undefined}
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
        emptyText="暂无操作日志"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />
    </div>
  );
}
