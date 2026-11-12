import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { RefreshCcw } from "lucide-react";
import { toast } from "sonner";

import { Badge } from "@rbac/ui/components/ui/badge";
import { Button } from "@rbac/ui/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@rbac/ui/components/ui/card";
import { Checkbox } from "@rbac/ui/components/ui/checkbox";
import { PageHeader } from "@/components/shared/page-header";
import { ConfirmDialog } from "@/components/shared/confirm-dialog";
import { clearCache, getCacheModules } from "@/api/cache";
import { queryKeys } from "@/lib/query-keys";
import { useIsSuperAdmin, usePermission } from "@/lib/permission";

/**
 * 缓存管理。
 *
 * 后端只有超管能清除缓存（控制器里直接判 is_admin），
 * 非超管即使有 cache:clear 权限码也会被 403 拒绝，
 * 所以按钮额外按 is_admin 判断，避免一点就报错。
 */
export default function CachePage() {
  const queryClient = useQueryClient();
  const canClear = usePermission("cache:clear");
  const isSuperAdmin = useIsSuperAdmin();

  const [selected, setSelected] = useState<string[]>([]);

  const { data: modules, isLoading } = useQuery({
    queryKey: queryKeys.cache.modules,
    queryFn: getCacheModules,
  });

  const clearMutation = useMutation({
    mutationFn: (targets?: string[]) => clearCache(targets),
    onSuccess: (result) => {
      toast.success(
        result.is_full_clear
          ? `全部缓存已清除，共删除 ${result.total_deleted} 个键`
          : `已清除 ${result.cleared.length} 个模块，共 ${result.total_deleted} 个键`,
      );
      void queryClient.invalidateQueries();
    },
  });

  const toggle = (value: string) => {
    setSelected((prev) =>
      prev.includes(value) ? prev.filter((item) => item !== value) : [...prev, value],
    );
  };

  return (
    <div className="space-y-4">
      <PageHeader
        title="缓存管理"
        description="清除后相关数据会在下次请求时重新从数据库加载。"
        actions={
          <ConfirmDialog
            title="清除全部缓存"
            description="会清空所有业务缓存，高并发下可能造成一次瞬时回源压力。"
            destructive
            confirmText="全部清除"
            onConfirm={() => clearMutation.mutateAsync(undefined).then(() => undefined)}
            trigger={
              <Button
                variant="destructive"
                disabled={!canClear || !isSuperAdmin || clearMutation.isPending}
              >
                <RefreshCcw />
                清除全部
              </Button>
            }
          />
        }
      />

      {!isSuperAdmin ? (
        <p className="rounded-md border border-dashed p-3 text-sm text-muted-foreground">
          仅超级管理员可以执行清除操作。
        </p>
      ) : null}

      <Card>
        <CardHeader>
          <CardTitle>按模块清除</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {isLoading ? (
            <p className="text-sm text-muted-foreground">加载中…</p>
          ) : (
            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
              {(modules ?? []).map((module) => (
                <label
                  key={module.value}
                  className="flex cursor-pointer items-center gap-2 rounded-md border p-3 text-sm hover:bg-muted/50"
                >
                  <Checkbox
                    checked={selected.includes(module.value)}
                    onCheckedChange={() => toggle(module.value)}
                  />
                  <span>{module.label}</span>
                  <code className="ml-auto text-xs text-muted-foreground">{module.value}</code>
                </label>
              ))}
            </div>
          )}

          <div className="flex items-center gap-2">
            <ConfirmDialog
              title="清除选中缓存"
              description={
                selected.length === 0
                  ? "未选择模块时将清除全部缓存。"
                  : `将清除 ${selected.length} 个模块的缓存。`
              }
              destructive
              confirmText="清除"
              onConfirm={() =>
                clearMutation
                  .mutateAsync(selected.length === 0 ? undefined : selected)
                  .then(() => setSelected([]))
              }
              trigger={
                <Button
                  variant="outline"
                  disabled={!canClear || !isSuperAdmin || clearMutation.isPending}
                >
                  清除选中
                </Button>
              }
            />
            {selected.length > 0 ? (
              <Badge variant="secondary">已选 {selected.length} 项</Badge>
            ) : (
              <span className="text-sm text-muted-foreground">未选择表示全部</span>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
