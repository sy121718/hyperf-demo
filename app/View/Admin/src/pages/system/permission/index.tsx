import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { ChevronsDownUp, ChevronsUpDown, Pencil, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@rbac/ui/components/ui/button";
import { Badge } from "@rbac/ui/components/ui/badge";
import { PageHeader } from "@/components/shared/page-header";
import { SearchInput } from "@/components/shared/search-input";
import { ConfirmDialog } from "@/components/shared/confirm-dialog";
import { StatusBadge } from "@/components/shared/status-badge";
import { RowActions } from "@/components/shared/row-actions";
import { TreeTable, type TreeTableColumn } from "@/components/shared/tree-table";
import { TreeMobileList } from "@/components/shared/tree-mobile-list";
import { PermissionFormDialog } from "@/pages/system/permission/permission-form-dialog";
import { deletePermission, getPermissionTree } from "@/api/permission";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useIsMobile } from "@/lib/use-responsive-list";
import type { PermissionTreeNode } from "@/types/permission";

function collectParentIds(nodes: PermissionTreeNode[]): number[] {
  return nodes.flatMap((node) =>
    node.children?.length ? [node.id, ...collectParentIds(node.children)] : [],
  );
}

/** 统计树里全部节点数，用于顶部徽标 */
function countNodes(nodes: PermissionTreeNode[]): number {
  return nodes.reduce((sum, node) => sum + 1 + countNodes(node.children ?? []), 0);
}

export default function PermissionPage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | undefined>(undefined);

  const isMobile = useIsMobile();

  const canCreate = usePermission("permission:create");
  const canUpdate = usePermission("permission:update");
  const canDelete = usePermission("permission:delete");

  const { data, isLoading } = useQuery({
    queryKey: queryKeys.permission.tree(listState.params),
    queryFn: () => getPermissionTree(listState.params),
  });

  useEffect(() => {
    if (data) {
      setExpandedIds(new Set(collectParentIds(data)));
    }
  }, [data]);

  const removeMutation = useMutation({
    mutationFn: deletePermission,
    onSuccess: () => {
      toast.success("权限已删除");
      void queryClient.invalidateQueries({ queryKey: queryKeys.permission.all });
    },
  });

  const toggle = (id: number) => {
    setExpandedIds((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  const allParentIds = useMemo(() => (data ? collectParentIds(data) : []), [data]);
  const allExpanded = allParentIds.length > 0 && allParentIds.every((id) => expandedIds.has(id));

  /** 行操作在树表与手机卡片里共用 */
  const renderActions = (row: PermissionTreeNode) => (
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
      {canDelete ? (
        <ConfirmDialog
          title="删除权限"
          description={`确定删除权限「${row.perm_name}」吗？已引用该权限码的角色会失去对应能力。`}
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

  const columns: TreeTableColumn<PermissionTreeNode>[] = [
    {
      key: "perm_name",
      header: "权限名称",
      cell: (row) => (
        <div className="flex items-center gap-2">
          <span className="font-medium">{row.perm_name}</span>
          <code className="text-xs text-muted-foreground">{row.perm_code}</code>
        </div>
      ),
    },
    {
      key: "api",
      header: "接口",
      cell: (row) =>
        row.api_path ? (
          <div className="flex items-center gap-1.5 text-xs">
            <Badge variant="outline">{row.http_method ?? "ANY"}</Badge>
            <span className="text-muted-foreground">{row.api_path}</span>
          </div>
        ) : (
          <span className="text-muted-foreground">-</span>
        ),
    },
    {
      key: "is_public",
      header: "公开",
      cell: (row) =>
        row.is_public === 1 ? <Badge variant="secondary">公开</Badge> : <span>-</span>,
    },
    { key: "route_priority", header: "优先级", align: "right", cell: (row) => row.route_priority },
    { key: "status", header: "状态", cell: (row) => <StatusBadge status={row.status} /> },
    {
      key: "actions",
      header: "操作",
      align: "right",
      cell: renderActions,
    },
  ];

  return (
    <div className="space-y-4">
      <PageHeader
        title="权限字典"
        description="权限码把接口路径与业务动作绑定，是菜单与角色权限的最终来源。"
        actions={
          canCreate ? (
            <Button
              onClick={() => {
                setEditingId(undefined);
                setFormOpen(true);
              }}
            >
              <Plus />
              新增权限
            </Button>
          ) : null
        }
      />

      <div className="flex flex-wrap items-center gap-2">
        <SearchInput
          value={listState.search}
          onChange={listState.setSearch}
          placeholder="搜索权限码 / 名称 / 接口路径"
        />
        <Button variant="outline" onClick={listState.reset}>
          重置
        </Button>
        <Button
          variant="outline"
          onClick={() => setExpandedIds(allExpanded ? new Set() : new Set(allParentIds))}
        >
          {allExpanded ? <ChevronsDownUp /> : <ChevronsUpDown />}
          {allExpanded ? "全部收起" : "全部展开"}
        </Button>
        <Badge variant="secondary">共 {data ? countNodes(data) : 0} 个权限点</Badge>
      </div>

      {isMobile ? (
        <TreeMobileList
          roots={data ?? []}
          title={(row) => row.perm_name}
          badge={(row) => <StatusBadge status={row.status} />}
          fields={[
            { label: "权限码", render: (row) => <code className="text-xs">{row.perm_code}</code> },
            { label: "接口", render: (row) => row.api_path || "-" },
            { label: "方法", render: (row) => row.http_method || "ANY" },
            { label: "优先级", render: (row) => row.route_priority },
          ]}
          actions={renderActions}
          expandedIds={expandedIds}
          onToggle={toggle}
          loading={isLoading}
        />
      ) : (
        <TreeTable
          columns={columns}
          roots={data ?? []}
          rowKey={(row) => row.id}
          childrenOf={(row) => row.children}
          expandedIds={expandedIds}
          onToggle={toggle}
          loading={isLoading}
        />
      )}

      <PermissionFormDialog
        open={formOpen}
        onOpenChange={setFormOpen}
        permissionId={editingId}
      />
    </div>
  );
}
