import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { ExternalLink, Plus, Trash2 } from "lucide-react";
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
import { SearchInput } from "@/components/shared/search-input";
import { ConfirmDialog } from "@/components/shared/confirm-dialog";
import { StatusBadge } from "@/components/shared/status-badge";
import { RowActions } from "@/components/shared/row-actions";
import { ListScopeTabs } from "@/components/shared/list-scope-tabs";
import type { DataTableColumn } from "@/components/shared/data-table";
import { ResponsiveDataList } from "@/components/shared/responsive-data-list";
import { ListToolbar } from "@/components/shared/list-toolbar";
import { UploadAttachmentDialog } from "@/pages/system/attachment/upload-dialog";
import { deleteAttachment, getAttachmentList } from "@/api/attachment";
import { formatDateTime, formatFileSize } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useResponsiveList } from "@/lib/use-responsive-list";
import type { ListScope } from "@/components/shared/list-scope-tabs";
import type { AttachmentRow } from "@/types/attachment";

export default function AttachmentPage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [scope, setScope] = useState<ListScope>("all");
  const [uploadOpen, setUploadOpen] = useState(false);

  // 全量看 attachment:list，只看自己看 attachment:myList，两个独立权限点
  const canViewAll = usePermission("attachment:list");
  const canViewMine = usePermission("attachment:myList");
  const canUpload = usePermission("attachment:upload");
  const canDelete = usePermission("attachment:delete");

  const effectiveScope: ListScope = canViewAll ? scope : "mine";

  // scope 切换会改变接口，必须把 scope 拼进 key，避免「全部/我的」共用缓存
  const { items, total, loading, refreshing, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<AttachmentRow>({
      queryKey: [...queryKeys.attachment.all, effectiveScope],
      fetcher: (params) => getAttachmentList(effectiveScope, params),
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
      enabled: canViewAll || canViewMine,
    });

  const removeMutation = useMutation({
    mutationFn: deleteAttachment,
    onSuccess: () => {
      toast.success("附件已删除");
      void queryClient.invalidateQueries({ queryKey: queryKeys.attachment.all });
    },
  });

  /** 文件名 + 外链图标，表格与卡片共用 */
  const renderFileName = (row: AttachmentRow) => (
    <div className="flex items-center gap-2">
      <span className="max-w-64 truncate font-medium">{row.file_name}</span>
      {row.url ? (
        <a
          href={row.url}
          target="_blank"
          rel="noreferrer"
          className="text-muted-foreground hover:text-foreground"
          title="打开文件"
        >
          <ExternalLink className="size-3.5" />
        </a>
      ) : null}
    </div>
  );

  /** 行操作在表格与卡片里共用 */
  const renderActions = (row: AttachmentRow) => (
    <RowActions>
      {canDelete ? (
        <ConfirmDialog
          title="删除附件"
          description={`确定删除附件「${row.file_name}」吗？数据库记录会被删除，存储文件不受影响。`}
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

  const columns: DataTableColumn<AttachmentRow>[] = [
    {
      key: "file_name",
      header: "文件名",
      mobile: "title",
      cell: renderFileName,
    },
    { key: "file_type", header: "类型", cell: (row) => <Badge variant="outline">{row.file_type}</Badge> },
    {
      key: "file_size",
      header: "大小",
      align: "right",
      cell: (row) => formatFileSize(row.file_size),
    },
    {
      key: "mime_type",
      header: "MIME",
      cell: (row) => (
        <span className="text-xs text-muted-foreground">{row.mime_type || "-"}</span>
      ),
    },
    { key: "storage_type", header: "存储", cell: (row) => row.storage_type || "-" },
    {
      key: "status",
      header: "状态",
      mobile: "badge",
      cell: (row) => <StatusBadge status={row.status} />,
    },
    { key: "create_time", header: "上传时间", cell: (row) => formatDateTime(row.create_time) },
    {
      key: "actions",
      header: "操作",
      align: "right",
      cell: renderActions,
    },
  ];

  // file_type 是自由文本（上传表单里手填），用带防抖的搜索框做精确匹配筛选
  const filters = (
    <>
      <SearchInput
        value={String(listState.filters.file_type ?? "")}
        onChange={(value) => listState.setFilter("file_type", value || undefined)}
        placeholder="文件类型，如 document"
        className="w-full sm:w-48"
      />
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
    </>
  );

  const createButton = canUpload ? (
    <Button onClick={() => setUploadOpen(true)}>
      <Plus />
      登记附件
    </Button>
  ) : null;

  const hasActiveFilters =
    listState.filters.file_type !== undefined || listState.filters.status !== undefined;

  return (
    <div className="space-y-4">
      <PageHeader
        title="附件管理"
        description="后端当前只接收文件元数据，上传不会真正保存文件流。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索文件名 / MIME"
        createButton={createButton}
        filters={filters}
        onReset={listState.reset}
        hasActiveFilters={hasActiveFilters}
        summary={<Badge variant="secondary">共 {total} 个附件</Badge>}
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
        emptyText="还没有附件，点上方登记"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />

      <UploadAttachmentDialog open={uploadOpen} onOpenChange={setUploadOpen} />
    </div>
  );
}
