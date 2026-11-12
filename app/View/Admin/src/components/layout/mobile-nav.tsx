import { useState } from "react";
import { NavLink } from "react-router-dom";
import { Menu } from "lucide-react";

import { Button } from "@rbac/ui/components/ui/button";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@rbac/ui/components/ui/sheet";
import { cn } from "@rbac/ui/lib/utils";
import { groupLabel, type MenuGroup } from "@/components/layout/sidebar-nav";
import { MENU_TYPE, type MenuNode } from "@/types/menu";

/**
 * 手机端导航。
 *
 * 屏幕窄时左侧双栏侧边栏会挤掉内容区，所以改成顶部汉堡按钮 + 抽屉：
 * 抽屉里按一级分组分段列出全部二级链接，点链接即关闭。
 */
export function MobileNav({ menus }: { menus: MenuNode[] }) {
  const [open, setOpen] = useState(false);

  const groups: MenuGroup[] = menus.filter((node) => node.is_hidden !== 1);

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>
        <Button variant="ghost" size="icon-sm" title="打开导航">
          <Menu />
        </Button>
      </SheetTrigger>
      <SheetContent side="left" className="w-72 overflow-y-auto p-0">
        <SheetHeader className="border-b">
          <SheetTitle className="flex items-center gap-2">
            <span className="flex size-7 items-center justify-center rounded-lg bg-primary text-xs font-semibold text-primary-foreground">
              RB
            </span>
            管理后台
          </SheetTitle>
        </SheetHeader>

        <nav className="space-y-4 p-3">
          {groups.map((group) => (
            <section key={group.id} className="space-y-1">
              <p className="px-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {groupLabel(group)}
              </p>
              <MobileMenu nodes={group.children ?? []} onNavigate={() => setOpen(false)} />
              {group.type === MENU_TYPE.MENU && group.path ? (
                <NavLink
                  to={group.path}
                  onClick={() => setOpen(false)}
                  className={({ isActive }) =>
                    cn(
                      "block rounded-md px-2 py-2 text-sm",
                      isActive ? "bg-accent font-medium" : "hover:bg-muted",
                    )
                  }
                >
                  {groupLabel(group)}
                </NavLink>
              ) : null}
            </section>
          ))}
        </nav>
      </SheetContent>
    </Sheet>
  );
}

function MobileMenu({ nodes, onNavigate }: { nodes: MenuNode[]; onNavigate: () => void }) {
  const visible = nodes.filter((node) => node.is_hidden !== 1);
  if (visible.length === 0) {
    return null;
  }

  return (
    <ul className="space-y-0.5">
      {visible.map((node) => (
        <li key={node.id}>
          {node.path && node.type !== MENU_TYPE.DIRECTORY ? (
            <NavLink
              to={node.path}
              onClick={onNavigate}
              className={({ isActive }) =>
                cn(
                  "block truncate rounded-md px-2 py-2 text-sm",
                  isActive ? "bg-accent font-medium" : "hover:bg-muted",
                )
              }
            >
              {groupLabel(node)}
            </NavLink>
          ) : (
            <p className="px-2 pt-2 text-xs text-muted-foreground">{groupLabel(node)}</p>
          )}
          {node.children?.length ? (
            <div className="pl-2">
              <MobileMenu nodes={node.children} onNavigate={onNavigate} />
            </div>
          ) : null}
        </li>
      ))}
    </ul>
  );
}
