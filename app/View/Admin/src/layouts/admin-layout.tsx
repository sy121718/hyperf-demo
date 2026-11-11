import { Link, Outlet, useLocation, useNavigate } from "react-router-dom";
import { LogOut } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@rbac/ui/components/ui/button";
import { logout } from "@/api/auth";
import { useAuthStore } from "@/stores/auth";
import { MENU_TYPE, type MenuNode } from "@/types/menu";

/** 菜单树 -> 侧边栏。目录只做分组，不生成链接 */
function SidebarMenu({
  nodes,
  currentPath,
  depth = 0,
}: {
  nodes: MenuNode[];
  currentPath: string;
  depth?: number;
}) {
  return (
    <ul className={depth === 0 ? "space-y-1" : "mt-1 space-y-1 pl-3"}>
      {nodes
        .filter((node) => node.is_hidden !== 1)
        .map((node) => {
          const isDirectory = node.type === MENU_TYPE.DIRECTORY;
          const target = node.path ? `/admin${node.path}` : null;
          const active = target !== null && currentPath.startsWith(target);

          return (
            <li key={node.id}>
              {isDirectory || !target ? (
                <p className="px-2 py-1.5 text-xs font-medium text-muted-foreground">
                  {node.title ?? node.menu_name}
                </p>
              ) : (
                <Link
                  to={target}
                  className={`block rounded-md px-2 py-1.5 text-sm transition-colors ${
                    active
                      ? "bg-accent text-accent-foreground"
                      : "text-foreground/80 hover:bg-muted"
                  }`}
                >
                  {node.title ?? node.menu_name}
                </Link>
              )}
              {node.children?.length ? (
                <SidebarMenu
                  nodes={node.children}
                  currentPath={currentPath}
                  depth={depth + 1}
                />
              ) : null}
            </li>
          );
        })}
    </ul>
  );
}

export default function AdminLayout({ menus }: { menus: MenuNode[] }) {
  const location = useLocation();
  const navigate = useNavigate();
  const admin = useAuthStore((state) => state.admin);
  const reset = useAuthStore((state) => state.reset);

  const handleLogout = async () => {
    try {
      await logout();
    } finally {
      // 即使后端登出失败也要清本地状态，避免卡在无效会话
      reset();
      toast.success("已退出登录");
      navigate("/admin/login", { replace: true });
    }
  };

  return (
    <div className="flex min-h-svh">
      <aside className="w-60 shrink-0 border-r bg-sidebar text-sidebar-foreground">
        <div className="flex h-14 items-center border-b px-4 font-semibold">
          RBAC 后台
        </div>
        <nav className="p-2">
          <SidebarMenu nodes={menus} currentPath={location.pathname} />
        </nav>
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="flex h-14 items-center justify-between border-b px-4">
          <span className="text-sm text-muted-foreground">
            {admin ? `你好，${admin.name ?? admin.username}` : ""}
          </span>
          <Button variant="ghost" size="sm" onClick={handleLogout}>
            <LogOut />
            退出
          </Button>
        </header>

        <main className="flex-1 p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
