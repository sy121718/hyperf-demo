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

/**
 * 懒加载路由的挂起占位。
 *
 * 之前这里渲染整页骨架，切标签时旧页面瞬间被骨架替换、加载完再换回内容，
 * 观感上是「闪一下白」。改成一条不占位的进度条：内容区域保持当前高度，
 * 用户的注意力只被顶部那条线吸引，不再整块抖动。
 *
 * 需要骨架屏的场景（首次进入、数据未返回）由页面自己控制，
 * 不要用路由级 fallback 代替。
 */
export function RouteFallback() {
  return (
    <div className="pointer-events-none fixed inset-x-0 top-14 z-50 h-0.5 overflow-hidden bg-transparent">
      <div className="progress-indeterminate h-full w-1/3 bg-primary" />
    </div>
  );
}
