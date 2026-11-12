import { useEffect } from "react";
import { Link, Outlet, useNavigate } from "react-router-dom";
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
import { TopProgress } from "@/components/shared/top-progress";
import { SidebarNav } from "@/components/layout/sidebar-nav";
import { logout } from "@/api/auth";
import { getProfile } from "@/api/profile";
import { usePermissionSync } from "@/lib/permission";
import { useAuthStore } from "@/stores/auth";
import type { MenuNode } from "@/types/menu";

export default function AdminLayout({ menus }: { menus: MenuNode[] }) {
  const navigate = useNavigate();
  const admin = useAuthStore((state) => state.admin);
  const setAdmin = useAuthStore((state) => state.setAdmin);
  const reset = useAuthStore((state) => state.reset);

  // 权限码随登录态拉取，供按钮显隐与「全部/我的」视图切换
  usePermissionSync();

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

        <main className="flex-1 overflow-y-auto bg-muted/20 p-4 md:p-6 lg:p-8">
          <div className="mx-auto w-full max-w-[1400px] space-y-5">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
