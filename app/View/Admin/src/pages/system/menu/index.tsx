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
import { MenuFormDialog } from "@/pages/system/menu/menu-form-dialog";
import { deleteMenu, getMenuTree } from "@/api/menu";
import { useListState } from "@/lib/list";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";
import { useIsMobile } from "@/lib/use-responsive-list";
import { MENU_TYPE_LABELS, type MenuTreeNode } from "@/types/menu";

/** 收集所有有子节点的 id，用于默认展开 */
function collectParentIds(nodes: MenuTreeNode[]): number[] {
  return nodes.flatMap((node) =>
    node.children?.length ? [node.id, ...collectParentIds(node.children)] : [],
  );
}

export default function MenuPage() {
  const queryClient = useQueryClient();
  const listState = useListState();
  const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | undefined>(undefined);
  const [defaultParentId, setDefaultParentId] = useState<number | undefined>(undefined);

  const isMobile = useIsMobile();

  const canCreate = usePermission("menu:create");
  const canUpdate = usePermission("menu:update");
  const canDelete = usePermission("menu:delete");

  const { data, isLoading } = useQuery({
    queryKey: queryKeys.menu.tree(listState.params),
    queryFn: () => getMenuTree(listState.params),
  });

  // 首次加载后展开全部目录，便于一眼看到层级
  useEffect(() => {
    if (!data) {
      return;
    }
    setExpandedIds(new Set(collectParentIds(data)));
  }, [data]);

  const removeMutation = useMutation({
    mutationFn: deleteMenu,
    onSuccess: () => {
      toast.success("菜单已删除");
      void queryClient.invalidateQueries({ queryKey: queryKeys.menu.all });
      void queryClient.invalidateQueries({ queryKey: ["admin", "menu"] });
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
  const renderActions = (row: MenuTreeNode) => (
    <RowActions>
      {canCreate ? (
        <Button
          variant="ghost"
          size="icon-sm"
          title="新增子菜单"
          onClick={() => {
            setEditingId(undefined);
            setDefaultParentId(row.id);
            setFormOpen(true);
          }}
        >
          <Plus />
        </Button>
      ) : null}
      {canUpdate ? (
        <Button
          variant="ghost"
          size="icon-sm"
          title="编辑"
          onClick={() => {
            setEditingId(row.id);
            setDefaultParentId(undefined);
            setFormOpen(true);
          }}
        >
          <Pencil />
        </Button>
      ) : null}
      {canDelete ? (
        <ConfirmDialog
          title="删除菜单"
          description={`确定删除「${row.title ?? row.menu_name}」吗？其子菜单不会自动删除。`}
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

  const columns: TreeTableColumn<MenuTreeNode>[] = [
    {
      key: "title",
      header: "菜单",
      cell: (row) => (
        <div className="flex items-center gap-2">
          <span className="font-medium">{row.title ?? row.menu_name}</span>
          <code className="text-xs text-muted-foreground">{row.menu_code}</code>
        </div>
      ),
    },
    {
      key: "type",
      header: "类型",
      cell: (row) => <Badge variant="outline">{MENU_TYPE_LABELS[row.type] ?? row.type}</Badge>,
    },
    {
      key: "path",
      header: "路由 / 组件",
      cell: (row) => (
        <div className="space-y-0.5 text-xs">
          <div>{row.path || "-"}</div>
          <div className="text-muted-foreground">{row.component || "-"}</div>
        </div>
      ),
    },
    {
      key: "permission_codes",
      header: "权限码",
      cell: (row) =>
        row.permission_codes.length === 0 ? (
          <span className="text-muted-foreground">-</span>
        ) : (
          <div className="flex flex-wrap gap-1">
            {row.permission_codes.map((code) => (
              <Badge key={code} variant="secondary">
                {code}
              </Badge>
            ))}
          </div>
        ),
    },
    { key: "sort_order", header: "排序", align: "right", cell: (row) => row.sort_order },
    { key: "status", header: "状态", cell: (row) => <StatusBadge status={row.status} /> },
    {
      key: "is_hidden",
      header: "侧边栏",
      cell: (row) =>
        row.is_hidden === 1 ? (
          <Badge variant="ghost">隐藏</Badge>
        ) : (
          <Badge variant="outline">显示</Badge>
        ),
    },
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
        title="菜单管理"
        description="菜单决定后台侧边栏结构与动态路由，组件路径需与前端 pages 目录一致。"
        actions={
          canCreate ? (
            <Button
              onClick={() => {
                setEditingId(undefined);
                setDefaultParentId(undefined);
                setFormOpen(true);
              }}
            >
              <Plus />
              新增菜单
            </Button>
          ) : null
        }
      />

      <div className="flex flex-wrap items-center gap-2">
        <SearchInput
          value={listState.search}
          onChange={listState.setSearch}
          placeholder="搜索菜单名称 / 标识 / 路径"
        />
        <Button variant="outline" onClick={listState.reset}>
          重置
        </Button>
        <Button
          variant="outline"
          onClick={() =>
            setExpandedIds(allExpanded ? new Set() : new Set(allParentIds))
          }
        >
          {allExpanded ? <ChevronsDownUp /> : <ChevronsUpDown />}
          {allExpanded ? "全部收起" : "全部展开"}
        </Button>
      </div>

      {isMobile ? (
        <TreeMobileList
          roots={data ?? []}
          title={(row) => row.title ?? row.menu_name}
          badge={(row) => <StatusBadge status={row.status} />}
          fields={[
            { label: "标识", render: (row) => <code className="text-xs">{row.menu_code}</code> },
            { label: "类型", render: (row) => MENU_TYPE_LABELS[row.type] ?? row.type },
            { label: "路由", render: (row) => row.path || "-" },
            { label: "组件", render: (row) => row.component || "-" },
            { label: "排序", render: (row) => row.sort_order },
            { label: "侧边栏", render: (row) => (row.is_hidden === 1 ? "隐藏" : "显示") },
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

      <MenuFormDialog
        open={formOpen}
        onOpenChange={setFormOpen}
        menuId={editingId}
        defaultParentId={defaultParentId}
      />
    </div>
  );
}
