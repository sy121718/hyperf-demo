import { useEffect, useMemo } from "react";
import { Link, Outlet, useLocation, useNavigate } from "react-router-dom";
import { ChevronDown, LogOut, UserRound } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@rbac/ui/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@rbac/ui/components/ui/dropdown-menu";
import { MobileNav } from "@/components/layout/mobile-nav";
import { PageTabs } from "@/components/layout/page-tabs";
import { TopProgress } from "@/components/shared/top-progress";
import { SidebarNav } from "@/components/layout/sidebar-nav";
import { logout } from "@/api/auth";
import { getProfile } from "@/api/profile";
import { buildPathTitles, HOME_TAB } from "@/lib/page-tabs";
import { usePermissionSync } from "@/lib/permission";
import { useAuthStore } from "@/stores/auth";
import { usePageTabsStore } from "@/stores/page-tabs";
import type { MenuNode } from "@/types/menu";

export default function AdminLayout({ menus }: { menus: MenuNode[] }) {
  const navigate = useNavigate();
  const location = useLocation();
  const admin = useAuthStore((state) => state.admin);
  const setAdmin = useAuthStore((state) => state.setAdmin);
  const reset = useAuthStore((state) => state.reset);
  const openTab = usePageTabsStore((state) => state.openTab);
  const resetTabs = usePageTabsStore((state) => state.reset);

  // 权限码随登录态拉取，供按钮显隐与「全部/我的」视图切换
  usePermissionSync();

  const pathTitles = useMemo(() => buildPathTitles(menus), [menus]);

  /**
   * 路由变化即登记标签：菜单跳转、快捷卡片跳转、浏览器前进后退
   * 都走这一条路径，不需要各处单独打开标签。
   */
  useEffect(() => {
    openTab(HOME_TAB);

    if (location.pathname === HOME_TAB.path) {
      return;
    }

    const title = pathTitles.get(location.pathname);
    // 菜单里没有的路径（如 403）不登记，避免出现打不开的标签
    if (title) {
      openTab({ path: location.pathname, title, closable: true });
    }
  }, [location.pathname, pathTitles, openTab]);

  // 退出登录时清空标签，下次登录不残留上一个账号的页面
  useEffect(() => {
    if (!admin) {
      resetTabs();
    }
  }, [admin, resetTabs]);

  /**
   * admin 信息只存在内存里，刷新后会丢。
   * 这里用 profile 接口补回来，否则顶栏头像、权限判断都会落空。
   */
  useEffect(() => {
    if (admin) {
      return;
    }
    let cancelled = false;
    void getProfile()
      .then((profile) => {
        if (!cancelled) {
          setAdmin(profile);
        }
      })
      .catch(() => {
        // 401 由 http 拦截器统一跳登录，这里不重复处理
      });
    return () => {
      cancelled = true;
    };
  }, [admin, setAdmin]);

  const handleLogout = async () => {
    try {
      await logout();
    } finally {
      // 即使后端登出失败也要清本地状态，避免卡在无效会话
      reset();
      toast.success("已退出登录");
      navigate("/login", { replace: true });
    }
  };

  return (
    // 固定视口高度，让 main 成为唯一滚动容器；
    // 这样列表页的 sticky 工具栏才有可吸附的滚动上下文
    <div className="flex h-svh overflow-hidden">
      <TopProgress />
      <SidebarNav menus={menus} />

      <div className="flex min-w-0 flex-1 flex-col overflow-hidden">
        <header className="flex h-14 shrink-0 items-center justify-between gap-2 border-b bg-background/80 px-4 backdrop-blur md:px-6">
          {/* 窄屏侧边栏收起，导航改走抽屉 */}
          <div className="flex items-center gap-2">
            <div className="md:hidden">
              <MobileNav menus={menus} />
            </div>
            <span className="text-sm font-medium text-muted-foreground">管理控制台</span>
          </div>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="sm">
                <UserRound />
                {admin ? (admin.name ?? admin.username) : "未登录"}
                <ChevronDown />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuLabel>
                {admin ? `@${admin.username}` : "未知账号"}
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem asChild>
                <Link to="/system/profile">
                  <UserRound />
                  个人中心
                </Link>
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem variant="destructive" onSelect={() => void handleLogout()}>
                <LogOut />
                退出登录
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </header>

        <PageTabs />

        <main className="flex-1 overflow-y-auto bg-muted/20 p-4 md:p-6 lg:p-8">
          <div className="mx-auto w-full max-w-[1400px] space-y-5">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
