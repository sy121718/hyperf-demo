import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Eye, Megaphone, Pencil, Plus, Trash2, Undo2 } from "lucide-react";
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
import { ListScopeTabs, type ListScope } from "@/components/shared/list-scope-tabs";
import type { DataTableColumn } from "@/components/shared/data-table";
import { ResponsiveDataList } from "@/components/shared/responsive-data-list";
import { ListToolbar } from "@/components/shared/list-toolbar";
import { NoticeFormDialog } from "@/pages/notify/notice/notice-form-dialog";
import { NoticePreviewDialog } from "@/pages/notify/notice/notice-preview-dialog";
import {
  deleteNotice,
  getNoticeList,
  publishNotice,
  withdrawNotice,
} from "@/api/notice";
import { formatDateTime } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useResponsiveList } from "@/lib/use-responsive-list";
import { NOTICE_STATUS, NOTICE_STATUS_LABELS, type NoticeRow } from "@/types/notice";

function StatusBadge({ status }: { status: number }) {
  const variant =
    status === NOTICE_STATUS.PUBLISHED
      ? "secondary"
      : status === NOTICE_STATUS.WITHDRAWN
        ? "outline"
        : "ghost";
  return <Badge variant={variant}>{NOTICE_STATUS_LABELS[status] ?? status}</Badge>;
}

export default function NoticePage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [scope, setScope] = useState<ListScope>("all");
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | undefined>(undefined);
  const [previewId, setPreviewId] = useState<number | null>(null);

  // 「全部」与「我的」是两个独立权限点，只有全量权限才显示切换
  const canViewAll = usePermission("notice:list");
  const canViewMine = usePermission("notice:myList");
  const canCreate = usePermission("notice:create");
  const canUpdate = usePermission("notice:update");
  const canDelete = usePermission("notice:delete");
  const canPublish = usePermission("notice:publish");
  const canWithdraw = usePermission("notice:withdraw");

  const effectiveScope: ListScope = canViewAll ? scope : "mine";

  // scope 决定调哪个接口，拼进 key 才能让「全部/我的」各自缓存
  const { items, total, loading, refreshing, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<NoticeRow>({
      queryKey: [...queryKeys.notice.all, effectiveScope],
      fetcher: (params) => getNoticeList(effectiveScope, params),
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
      enabled: canViewAll || canViewMine,
    });

  const invalidate = () => void queryClient.invalidateQueries({ queryKey: queryKeys.notice.all });

  const deleteMutation = useMutation({
    mutationFn: deleteNotice,
    onSuccess: () => {
      toast.success("公告已删除");
      invalidate();
    },
  });

  const publishMutation = useMutation({
    mutationFn: (id: number) => publishNotice(id),
    onSuccess: () => {
      toast.success("公告已发布");
      invalidate();
    },
  });

  const withdrawMutation = useMutation({
    mutationFn: withdrawNotice,
    onSuccess: () => {
      toast.success("公告已撤回");
      invalidate();
    },
  });

  /** 标题前带置顶标记，表格与卡片共用 */
  const renderTitle = (row: NoticeRow) => (
    <div className="flex items-center gap-1.5">
      {row.is_top === 1 ? <Badge variant="secondary">置顶</Badge> : null}
      <span className="max-w-72 truncate font-medium">{row.title}</span>
    </div>
  );

  /** 行操作在表格与卡片里共用 */
  const renderActions = (row: NoticeRow) => (
    <RowActions>
      <Button variant="ghost" size="icon-sm" title="预览" onClick={() => setPreviewId(row.id)}>
        <Eye />
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
      {canPublish && row.status === NOTICE_STATUS.DRAFT ? (
        <ConfirmDialog
          title="发布公告"
          description={`发布后「${row.title}」将对目标用户立即可见。`}
          confirmText="发布"
          onConfirm={() => publishMutation.mutateAsync(row.id).then(() => undefined)}
          trigger={
            <Button variant="ghost" size="icon-sm" title="发布">
              <Megaphone />
            </Button>
          }
        />
      ) : null}
      {canWithdraw && row.status === NOTICE_STATUS.PUBLISHED ? (
        <ConfirmDialog
          title="撤回公告"
          description={`撤回后「${row.title}」将从用户视图消失，可再次编辑后发布。`}
          confirmText="撤回"
          onConfirm={() => withdrawMutation.mutateAsync(row.id).then(() => undefined)}
          trigger={
            <Button variant="ghost" size="icon-sm" title="撤回">
              <Undo2 />
            </Button>
          }
        />
      ) : null}
      {canDelete ? (
        <ConfirmDialog
          title="删除公告"
          description={`确定删除「${row.title}」吗？`}
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

  const columns: DataTableColumn<NoticeRow>[] = [
    {
      key: "title",
      header: "标题",
      mobile: "title",
      cell: renderTitle,
    },
    {
      key: "status",
      header: "状态",
      mobile: "badge",
      cell: (row) => <StatusBadge status={row.status} />,
    },
    { key: "notice_type", header: "类型", cell: (row) => <Badge variant="outline">{row.notice_type}</Badge> },
    {
      key: "publish_time",
      header: "发布时间",
      cell: (row) => formatDateTime(row.publish_time),
    },
    {
      key: "expire_time",
      header: "过期时间",
      cell: (row) => formatDateTime(row.expire_time),
    },
    { key: "read_count", header: "已读", align: "right", cell: (row) => row.read_count },
    {
      key: "actions",
      header: "操作",
      align: "right",
      cell: renderActions,
    },
  ];

  const filters = (
    <>
      {/* 类型取值与新建表单保持一致：1 通知 / 2 公告 / 3 警告 */}
      <Select
        value={String(listState.filters.notice_type ?? "all")}
        onValueChange={(value) =>
          listState.setFilter("notice_type", value === "all" ? undefined : Number(value))
        }
      >
        <SelectTrigger className="w-full sm:w-32">
          <SelectValue placeholder="类型" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">全部类型</SelectItem>
          <SelectItem value="1">通知</SelectItem>
          <SelectItem value="2">公告</SelectItem>
          <SelectItem value="3">警告</SelectItem>
        </SelectContent>
      </Select>
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
          <SelectItem value={String(NOTICE_STATUS.DRAFT)}>
            {NOTICE_STATUS_LABELS[NOTICE_STATUS.DRAFT]}
          </SelectItem>
          <SelectItem value={String(NOTICE_STATUS.PUBLISHED)}>
            {NOTICE_STATUS_LABELS[NOTICE_STATUS.PUBLISHED]}
          </SelectItem>
          <SelectItem value={String(NOTICE_STATUS.WITHDRAWN)}>
            {NOTICE_STATUS_LABELS[NOTICE_STATUS.WITHDRAWN]}
          </SelectItem>
        </SelectContent>
      </Select>
    </>
  );

  const createButton = canCreate ? (
    <Button
      onClick={() => {
        setEditingId(undefined);
        setFormOpen(true);
      }}
    >
      <Plus />
      新建公告
    </Button>
  ) : null;

  const hasActiveFilters =
    listState.filters.notice_type !== undefined || listState.filters.status !== undefined;

  return (
    <div className="space-y-4">
      <PageHeader
        title="通知公告"
        description="公告发布后按有效期对用户可见；「我的」只显示自己创建的公告。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索标题 / 正文"
        createButton={createButton}
        filters={filters}
        onReset={listState.reset}
        hasActiveFilters={hasActiveFilters}
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
        emptyText="还没有公告，点上方新建"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />

      <NoticeFormDialog open={formOpen} onOpenChange={setFormOpen} noticeId={editingId} />
      <NoticePreviewDialog noticeId={previewId} onClose={() => setPreviewId(null)} />
    </div>
  );
}
