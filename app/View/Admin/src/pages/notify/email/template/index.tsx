import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Pencil, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";

import { Badge } from "@rbac/ui/components/ui/badge";
import { Button } from "@rbac/ui/components/ui/button";
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
import { TemplateFormDialog } from "@/pages/notify/email/template/template-form-dialog";
import { deleteEmailTemplate, getEmailTemplates } from "@/api/email";
import { formatDateTime } from "@/lib/format";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useResponsiveList } from "@/lib/use-responsive-list";
import type { EmailTemplateRow } from "@/types/email";

export default function EmailTemplatePage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [formOpen, setFormOpen] = useState(false);
  const [editingRow, setEditingRow] = useState<EmailTemplateRow | undefined>(undefined);

  const canCreate = usePermission("email:template-create");
  const canUpdate = usePermission("email:template-update");
  const canDelete = usePermission("email:template-delete");

  const { items, total, loading, refreshing, loadingMore, hasNextPage, sentinelRef, isMobile } =
    useResponsiveList<EmailTemplateRow>({
      queryKey: [...queryKeys.email.all, "templates"],
      fetcher: getEmailTemplates,
      params: listState.params,
      page: listState.page,
      limit: listState.limit,
    });

  const removeMutation = useMutation({
    mutationFn: deleteEmailTemplate,
    onSuccess: () => {
      toast.success("模板已删除");
      void queryClient.invalidateQueries({ queryKey: queryKeys.email.all });
    },
  });

  /** 变量名徽标，表格与卡片共用；超出 max 个折叠成计数 */
  const renderVariables = (row: EmailTemplateRow, max: number) => {
    const keys = row.variables ? Object.keys(row.variables) : [];
    if (keys.length === 0) {
      return <span className="text-muted-foreground">无</span>;
    }
    return (
      <div className="flex flex-wrap gap-1">
        {keys.slice(0, max).map((key) => (
          <Badge key={key} variant="secondary" className="text-xs">
            {key}
          </Badge>
        ))}
        {keys.length > max ? <Badge variant="outline">+{keys.length - max}</Badge> : null}
      </div>
    );
  };

  /** 行操作在表格与卡片里共用 */
  const renderActions = (row: EmailTemplateRow) => (
    <RowActions>
      {canUpdate ? (
        <Button
          variant="ghost"
          size="icon-sm"
          title="编辑"
          onClick={() => {
            setEditingRow(row);
            setFormOpen(true);
          }}
        >
          <Pencil />
        </Button>
      ) : null}
      {canDelete ? (
        <ConfirmDialog
          title="删除模板"
          description={`确定删除模板「${row.template_name}」吗？引用该模板的发送记录不受影响。`}
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

  const columns: DataTableColumn<EmailTemplateRow>[] = [
    { key: "template_name", header: "模板名称", mobile: "title" },
    {
      key: "template_code",
      header: "编码",
      cell: (row) => <code className="text-xs text-muted-foreground">{row.template_code}</code>,
    },
    { key: "template_type", header: "类型", cell: (row) => <Badge variant="outline">{row.template_type}</Badge> },
    {
      key: "variables",
      header: "变量",
      cell: (row) => renderVariables(row, 3),
    },
    {
      key: "status",
      header: "状态",
      mobile: "badge",
      cell: (row) => <StatusBadge status={row.status ?? 1} />,
    },
    { key: "create_time", header: "创建时间", cell: (row) => formatDateTime(row.create_time) },
    {
      key: "actions",
      header: "操作",
      align: "right",
      cell: renderActions,
    },
  ];

  // template_type 目前只有 html / text 两种，用下拉固定选项，避免自由输入拼错
  const filters = (
    <>
      <Select
        value={String(listState.filters.template_type ?? "all")}
        onValueChange={(value) =>
          listState.setFilter("template_type", value === "all" ? undefined : value)
        }
      >
        <SelectTrigger className="w-full sm:w-32">
          <SelectValue placeholder="类型" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">全部类型</SelectItem>
          <SelectItem value="html">HTML</SelectItem>
          <SelectItem value="text">纯文本</SelectItem>
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
          <SelectItem value="1">启用</SelectItem>
          <SelectItem value="0">禁用</SelectItem>
        </SelectContent>
      </Select>
    </>
  );

  const createButton = canCreate ? (
    <Button
      onClick={() => {
        setEditingRow(undefined);
        setFormOpen(true);
      }}
    >
      <Plus />
      新增模板
    </Button>
  ) : null;

  const hasActiveFilters =
    listState.filters.template_type !== undefined || listState.filters.status !== undefined;

  return (
    <div className="space-y-4">
      <PageHeader
        title="邮件模板"
        description="模板内容以 HTML 保存，变量在发送时替换。"
      />

      <ListToolbar
        search={listState.search}
        onSearchChange={listState.setSearch}
        searchPlaceholder="搜索模板名称 / 编码 / 主题"
        createButton={createButton}
        filters={filters}
        onReset={listState.reset}
        hasActiveFilters={hasActiveFilters}
        summary={<Badge variant="secondary">共 {total} 个模板</Badge>}
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
        emptyText="还没有邮件模板"
        page={listState.page}
        limit={listState.limit}
        onPageChange={listState.setPage}
        onLimitChange={listState.setLimit}
      />

      <TemplateFormDialog
        open={formOpen}
        onOpenChange={setFormOpen}
        template={editingRow}
      />
    </div>
  );
}
