import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Pencil, Play, Plus, Trash2 } from "lucide-react";
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
import { CronJobFormDialog } from "@/pages/system/cron-job/cron-job-form-dialog";
import { RunResultDialog } from "@/pages/system/cron-job/run-result-dialog";
import { deleteCronJob, getCronJobList, runCronJob } from "@/api/cron-job";
import { formatDateTime } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useResponsiveList } from "@/lib/use-responsive-list";
import type { CronJobRow, CronJobRunResult } from "@/types/cron-job";

export default function CronJobPage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | undefined>(undefined);
  const [runResult, setRunResult] = useState<CronJobRunResult | null>(null);

  const canCreate = usePermission("cron-job:create");
  const canUpdate = usePermission("cron-job:update");
  const canDelete = usePermission("cron-job:delete");
  const canRun = usePermission("cron-job:run");

  const { items, total, loading, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<CronJobRow>({
      queryKey: queryKeys.cronJob.all,
      fetcher: getCronJobList,
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
    });

  const removeMutation = useMutation({
    mutationFn: deleteCronJob,
    onSuccess: () => {
      toast.success("任务已删除");
      void queryClient.invalidateQueries({ queryKey: queryKeys.cronJob.all });
    },
  });

  // 后端进程最长可跑 120s，这里用 mutation 承接，按钮按行显示执行中
  const runMutation = useMutation({
    mutationFn: runCronJob,
    onSuccess: (result) => {
      setRunResult(result);
      void queryClient.invalidateQueries({ queryKey: queryKeys.cronJob.all });
    },
  });

  /** 行操作在表格与卡片里共用 */
  const renderActions = (row: CronJobRow) => (
    <RowActions>
      {canRun ? (
        <Button
          variant="ghost"
          size="icon-sm"
          title="立即执行"
          disabled={row.status !== 1 || runMutation.isPending}
          onClick={() => runMutation.mutate(row.id)}
        >
          <Play />
        </Button>
      ) : null}
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
          title="删除任务"
          description={`确定删除任务「${row.job_name}」吗？`}
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

  const columns: DataTableColumn<CronJobRow>[] = [
    { key: "job_name", header: "任务名称", mobile: "title" },
    {
      key: "job_category",
      header: "分类",
      cell: (row) => <Badge variant="outline">{row.job_category}</Badge>,
    },
    {
      key: "cron_expression",
      header: "cron",
      cell: (row) => <code className="text-xs">{row.cron_expression}</code>,
    },
    {
      key: "command",
      header: "命令",
      cell: (row) => (
        <code className="line-clamp-1 max-w-64 text-xs text-muted-foreground">{row.command}</code>
      ),
    },
    {
      key: "status",
      header: "状态",
      mobile: "badge",
      cell: (row) => <StatusBadge status={row.status} />,
    },
    {
      key: "last_sync_time",
      header: "上次同步",
      cell: (row) => formatDateTime(row.last_sync_time),
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
      新增任务
    </Button>
  ) : null;

  return (
    <div className="space-y-4">
      <PageHeader
        title="定时任务"
        description="任务由命令行进程执行，手动执行最长等待 120 秒。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索任务名称 / 命令"
        createButton={createButton}
        filters={statusFilter}
        onReset={listState.reset}
        hasActiveFilters={listState.filters.status !== undefined}
        summary={<Badge variant="secondary">共 {total} 个任务</Badge>}
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
        emptyText="还没有定时任务"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />

      <CronJobFormDialog open={formOpen} onOpenChange={setFormOpen} jobId={editingId} />
      <RunResultDialog result={runResult} onClose={() => setRunResult(null)} />
    </div>
  );
}
