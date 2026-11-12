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
import { getEmailRecords } from "@/api/email";
import { formatDateTime } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useResponsiveList } from "@/lib/use-responsive-list";
import { EMAIL_RECORD_STATUS, EMAIL_RECORD_STATUS_LABELS, type EmailRecordRow } from "@/types/email";

/** 发送记录状态：0 待发送 / 1 发送中 / 2 成功 / 3 失败 */
function RecordStatus({ status }: { status: number }) {
  const variant =
    status === EMAIL_RECORD_STATUS.SUCCESS
      ? "secondary"
      : status === EMAIL_RECORD_STATUS.FAILED
        ? "destructive"
        : "outline";
  return <Badge variant={variant}>{EMAIL_RECORD_STATUS_LABELS[status] ?? status}</Badge>;
}

export default function EmailRecordsPage() {
  const listState = useListState();
  const [scope, setScope] = useState<ListScope>("all");

  const canViewAll = usePermission("email:sendRecords");
  const canViewMine = usePermission("email:mySendRecords");
  const effectiveScope: ListScope = canViewAll ? scope : "mine";

  // scope 决定接口，拼进 key 让「全部/我的」分开缓存
  const { items, total, loading, refreshing, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<EmailRecordRow>({
      queryKey: [...queryKeys.email.all, "records", effectiveScope],
      fetcher: (params) => getEmailRecords(effectiveScope, params),
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
      enabled: canViewAll || canViewMine,
    });

  const columns: DataTableColumn<EmailRecordRow>[] = [
    {
      key: "subject",
      header: "主题",
      mobile: "title",
      cell: (row) => <span className="max-w-72 truncate">{row.subject}</span>,
    },
    {
      key: "status",
      header: "状态",
      mobile: "badge",
      cell: (row) => <RecordStatus status={row.status} />,
    },
    {
      key: "recipients",
      header: "收件人",
      align: "right",
      cell: (row) => (
        <span className="text-sm">
          {row.success_count}/{row.total_recipients}
          {row.failed_count > 0 ? (
            <span className="ml-1 text-destructive">（失败 {row.failed_count}）</span>
          ) : null}
        </span>
      ),
    },
    {
      key: "error_message",
      header: "错误",
      cell: (row) => (
        <span className="line-clamp-1 max-w-56 text-xs text-destructive">
          {row.error_message || "-"}
        </span>
      ),
    },
    { key: "sent_time", header: "发送时间", cell: (row) => formatDateTime(row.sent_time) },
    { key: "create_time", header: "创建时间", cell: (row) => formatDateTime(row.create_time) },
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
        {Object.values(EMAIL_RECORD_STATUS).map((status) => (
          <SelectItem key={status} value={String(status)}>
            {EMAIL_RECORD_STATUS_LABELS[status]}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );

  return (
    <div className="space-y-4">
      <PageHeader
        title="发送记录"
        description="记录只包含提交与投递结果，邮件内容需回模板或发送时填写的内容查看。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索主题"
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
        refreshing={refreshing}
        loadingMore={loadingMore}
        hasNextPage={hasNextPage}
        sentinelRef={sentinelRef}
        isMobile={isMobile}
        rowKey={(row) => row.id}
        emptyText="还没有发送记录"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />
    </div>
  );
}
