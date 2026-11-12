import { Skeleton } from "@rbac/ui/components/ui/skeleton";

/**
 * 页面级骨架。
 *
 * 用于懒加载路由与首屏数据还没回来时的占位，比一行「加载中…」更能说明
 * 页面结构，用户在等待时也知道内容大概长什么样。
 */
export function PageLoading({ rows = 5 }: { rows?: number }) {
  return (
    <div className="space-y-4">
      <div className="space-y-2">
        <Skeleton className="h-6 w-40" />
        <Skeleton className="h-4 w-72" />
      </div>

      <div className="flex items-center gap-2">
        <Skeleton className="h-8 w-60" />
        <Skeleton className="h-8 w-20" />
      </div>

      <div className="space-y-2 rounded-xl border bg-card p-4">
        <Skeleton className="h-4 w-full" />
        {Array.from({ length: rows }, (_, index) => (
          <Skeleton key={`row-${index}`} className="h-10 w-full" />
        ))}
      </div>
    </div>
  );
}

/** 路由切换时盖在内容区的过渡层，避免旧页面停留造成「点了没反应」的错觉 */
export function RouteFallback() {
  return (
    <div className="animate-in fade-in-0 duration-150">
      <PageLoading />
    </div>
  );
}
