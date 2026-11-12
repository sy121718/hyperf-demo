import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ShieldBan, ShieldCheck } from "lucide-react";
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
import { RowActions } from "@/components/shared/row-actions";
import type { DataTableColumn } from "@/components/shared/data-table";
import { ResponsiveDataList } from "@/components/shared/responsive-data-list";
import { ListToolbar } from "@/components/shared/list-toolbar";
import { BanDialog } from "@/pages/system/ip-blacklist/ban-dialog";
import { getIpBlacklist, unbanIp } from "@/api/ip-blacklist";
import { formatDateTime } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useResponsiveList } from "@/lib/use-responsive-list";
import { IP_BAN_STATUS, IP_BAN_STATUS_LABELS, type IpBlacklistRow } from "@/types/ip-blacklist";

/** status: 0 已过期 / 1 生效中 / 2 已手动解除 */
function StatusCell({ status }: { status: number }) {
  const variant = status === IP_BAN_STATUS.ACTIVE ? "destructive" : status === 1 ? "secondary" : "outline";
  return <Badge variant={variant}>{IP_BAN_STATUS_LABELS[status] ?? status}</Badge>;
}

export default function IpBlacklistPage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [banOpen, setBanOpen] = useState(false);

  const canBan = usePermission("ip-blacklist:ban");
  const canUnban = usePermission("ip-blacklist:unban");

  const { items, total, loading, refreshing, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<IpBlacklistRow>({
      queryKey: queryKeys.ipBlacklist.all,
      fetcher: getIpBlacklist,
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
    });

  const unbanMutation = useMutation({
    mutationFn: unbanIp,
    onSuccess: () => {
      toast.success("已解除拉黑");
      void queryClient.invalidateQueries({ queryKey: queryKeys.ipBlacklist.all });
    },
  });

  /** 行操作在表格与卡片里共用 */
  const renderActions = (row: IpBlacklistRow) => (
    <RowActions>
      {canUnban && row.status === IP_BAN_STATUS.ACTIVE ? (
        <ConfirmDialog
          title="解除拉黑"
          description={`解除后 ${row.ip_address} 可以重新访问系统。`}
          confirmText="解除"
          onConfirm={() => unbanMutation.mutateAsync(row.id).then(() => undefined)}
          trigger={
            <Button variant="ghost" size="icon-sm" title="解除拉黑">
              <ShieldCheck />
            </Button>
          }
        />
      ) : null}
    </RowActions>
  );

  const columns: DataTableColumn<IpBlacklistRow>[] = [
    {
      key: "ip_address",
      header: "IP 地址",
      mobile: "title",
      cell: (row) => <code className="text-sm">{row.ip_address}</code>,
    },
    {
      key: "status",
      header: "状态",
      mobile: "badge",
      cell: (row) => <StatusCell status={row.status} />,
    },
    { key: "error_type", header: "错误类型", cell: (row) => row.error_type || "-" },
    {
      key: "ban_duration",
      header: "时长",
      align: "right",
      cell: (row) => `${row.ban_duration} 分钟`,
    },
    { key: "ban_count", header: "次数", align: "right", cell: (row) => row.ban_count },
    {
      key: "banned_time",
      header: "拉黑 / 到期",
      cell: (row) => (
        <div className="space-y-0.5 text-xs">
          <div>{formatDateTime(row.banned_time)}</div>
          <div className="text-muted-foreground">{formatDateTime(row.banned_until_time)}</div>
        </div>
      ),
    },
    {
      key: "ban_reason",
      header: "原因",
      cell: (row) => (
        <span className="line-clamp-1 max-w-48 text-muted-foreground">{row.ban_reason || "-"}</span>
      ),
    },
    {
      key: "actions",
      header: "操作",
      align: "right",
      cell: renderActions,
    },
  ];

  // 三态：0 已过期 / 1 生效中 / 2 已手动解除
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
        <SelectItem value={String(IP_BAN_STATUS.ACTIVE)}>
          {IP_BAN_STATUS_LABELS[IP_BAN_STATUS.ACTIVE]}
        </SelectItem>
        <SelectItem value={String(IP_BAN_STATUS.EXPIRED)}>
          {IP_BAN_STATUS_LABELS[IP_BAN_STATUS.EXPIRED]}
        </SelectItem>
        <SelectItem value={String(IP_BAN_STATUS.MANUAL_LIFTED)}>
          {IP_BAN_STATUS_LABELS[IP_BAN_STATUS.MANUAL_LIFTED]}
        </SelectItem>
      </SelectContent>
    </Select>
  );

  const createButton = canBan ? (
    <Button onClick={() => setBanOpen(true)}>
      <ShieldBan />
      拉黑 IP
    </Button>
  ) : null;

  return (
    <div className="space-y-4">
      <PageHeader
        title="IP 黑名单"
        description="被拉黑的 IP 在到期前无法访问登录接口。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索 IP 或原因"
        createButton={createButton}
        filters={statusFilter}
        onReset={listState.reset}
        hasActiveFilters={listState.filters.status !== undefined}
        summary={<Badge variant="secondary">共 {total} 条</Badge>}
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
        emptyText="暂无拉黑记录"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />

      <BanDialog open={banOpen} onOpenChange={setBanOpen} />
    </div>
  );
}
