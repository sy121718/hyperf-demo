import { useCallback } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { X } from "lucide-react";

import { cn } from "@rbac/ui/lib/utils";
import { usePageTabsStore } from "@/stores/page-tabs";

/**
 * 多页面标签栏。
 *
 * 从路由变化自动登记标签，不做额外的「打开页面」调用——任何进入过的
 * 路由都会成为一个标签，避免菜单跳转、快捷卡片跳转、浏览器前进后退
 * 三条路径各自维护一套逻辑。
 *
 * 当前激活态由 useLocation 决定，store 只存标签列表。
 */
export function PageTabs() {
  const location = useLocation();
  const navigate = useNavigate();
  const tabs = usePageTabsStore((state) => state.tabs);
  const closeTab = usePageTabsStore((state) => state.closeTab);

  const handleClose = useCallback(
    (event: React.MouseEvent, path: string) => {
      event.preventDefault();
      event.stopPropagation();

      const nextPath = closeTab(path, location.pathname);
      if (nextPath) {
        navigate(nextPath, { replace: true });
      }
    },
    [closeTab, location.pathname, navigate],
  );

  if (tabs.length === 0) {
    return null;
  }

  return (
    <div className="flex h-10 shrink-0 items-end gap-1 overflow-x-auto border-b bg-muted/40 px-2 pt-1.5">
      {tabs.map((tab) => {
        const active = tab.path === location.pathname;
        return (
          <div
            key={tab.path}
            className={cn(
              "group flex h-8 shrink-0 items-center gap-1 rounded-t-md border border-b-0 px-2.5 text-sm transition-colors",
              active
                ? "border-border bg-background font-medium text-foreground"
                : "border-transparent text-muted-foreground hover:bg-background/60",
            )}
          >
            <Link to={tab.path} className="max-w-40 truncate">
              {tab.title}
            </Link>
            {tab.closable ? (
              <button
                type="button"
                aria-label={`关闭 ${tab.title}`}
                onClick={(event) => handleClose(event, tab.path)}
                className={cn(
                  "rounded p-0.5 transition-colors hover:bg-muted-foreground/20",
                  active ? "opacity-60" : "opacity-0 group-hover:opacity-60",
                )}
              >
                <X className="size-3" />
              </button>
            ) : null}
          </div>
        );
      })}
    </div>
  );
}
