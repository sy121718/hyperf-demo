import { Tabs, TabsList, TabsTrigger } from "@rbac/ui/components/ui/tabs";

/**
 * 「全部 / 我的」视图切换。
 *
 * 两个视图对应后端的两个独立接口与权限码。只有一个可用时隐藏切换，
 * 避免出现点了没反应的空视图。
 */
export type ListScope = "all" | "mine";

export interface ListScopeTabsProps {
  scope: ListScope;
  onScopeChange: (scope: ListScope) => void;
  canViewAll: boolean;
  canViewMine: boolean;
}

export function ListScopeTabs({
  scope,
  onScopeChange,
  canViewAll,
  canViewMine,
}: ListScopeTabsProps) {
  if (!canViewAll && !canViewMine) {
    return null;
  }

  // 只有「我的」权限时无需切换
  if (!canViewAll || !canViewMine) {
    return null;
  }

  return (
    <Tabs value={scope} onValueChange={(value) => onScopeChange(value as ListScope)}>
      <TabsList>
        <TabsTrigger value="all">全部</TabsTrigger>
        <TabsTrigger value="mine">我的</TabsTrigger>
      </TabsList>
    </Tabs>
  );
}
