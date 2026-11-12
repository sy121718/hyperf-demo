import { useEffect, useState } from "react";
import { NavLink, useLocation } from "react-router-dom";
import { ChevronDown, Menu } from "lucide-react";

import { Button } from "@rbac/ui/components/ui/button";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@rbac/ui/components/ui/sheet";
import { cn } from "@rbac/ui/lib/utils";
import { groupLabel } from "@/components/layout/sidebar-nav";
import { MENU_TYPE, type MenuNode } from "@/types/menu";

/**
 * 手机端导航。
 *
 * 与桌面端共用同一份菜单树：一级分组折叠，展开后是二级链接。
 * 初次打开抽屉时只展开当前页所在的分组，其余收起，
 * 避免几十个链接平铺下来还要长滑。
 */
export function MobileNav({ menus }: { menus: MenuNode[] }) {
  const [open, setOpen] = useState(false);
  const location = useLocation();

  const groups = menus.filter((node) => node.is_hidden !== 1);
  const [expanded, setExpanded] = useState<Set<number>>(new Set());

  /** 打开时定位到当前页所属分组，只在打开瞬间算一次 */
  useEffect(() => {
    if (!open) {
      return;
    }
    const current = groups.find((group) =>
      [group, ...(group.children ?? [])].some(
        (node) => node.path && location.pathname.startsWith(node.path),
      ),
    );
    setExpanded(current ? new Set([current.id]) : new Set());
    // groups 每次渲染都是新数组，依赖它会导致 effect 反复执行，这里按 open 触发即可
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open]);

  const toggle = (id: number) => {
    setExpanded((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  const close = () => setOpen(false);

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

        <nav className="space-y-1 p-2">
          {groups.map((group) => {
            const isOpen = expanded.has(group.id);
            const children = (group.children ?? []).filter((node) => node.is_hidden !== 1);
            // 自身就是可点击页面的分组（无子项）直接当链接
            const selfLink =
              group.type !== MENU_TYPE.DIRECTORY && group.path ? group.path : null;

            return (
              <div key={group.id}>
                <button
                  type="button"
                  onClick={() => (children.length > 0 ? toggle(group.id) : close())}
                  className={cn(
                    "flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition-colors",
                    isOpen ? "bg-muted/60" : "hover:bg-muted/40",
                  )}
                >
                  <span>{groupLabel(group)}</span>
                  {children.length > 0 ? (
                    <ChevronDown
                      className={cn(
                        "size-4 shrink-0 text-muted-foreground transition-transform",
                        isOpen && "rotate-180",
                      )}
                    />
                  ) : null}
                </button>

                {selfLink && children.length === 0 ? (
                  <NavLink
                    to={selfLink}
                    onClick={close}
                    className="block rounded-md px-3 py-2 pl-6 text-sm hover:bg-muted"
                  >
                    {groupLabel(group)}
                  </NavLink>
                ) : null}

                {isOpen ? (
                  <div className="mt-1 mb-2 space-y-0.5 pl-3">
                    <MobileMenu nodes={children} onNavigate={close} />
                  </div>
                ) : null}
              </div>
            );
          })}
        </nav>
      </SheetContent>
    </Sheet>
  );
}

/** 二级链接；目录作小标题，不再平铺 */
function MobileMenu({ nodes, onNavigate }: { nodes: MenuNode[]; onNavigate: () => void }) {
  if (nodes.length === 0) {
    return null;
  }

  return (
    <ul className="space-y-0.5">
      {nodes.map((node) => (
        <li key={node.id}>
          {node.path && node.type !== MENU_TYPE.DIRECTORY ? (
            <NavLink
              to={node.path}
              onClick={onNavigate}
              className={({ isActive }) =>
                cn(
                  "block truncate rounded-md px-3 py-2 text-sm transition-colors",
                  isActive
                    ? "bg-accent font-medium text-accent-foreground"
                    : "text-foreground/80 hover:bg-muted",
                )
              }
            >
              {groupLabel(node)}
            </NavLink>
          ) : (
            <p className="px-3 pt-2 pb-1 text-xs text-muted-foreground">{groupLabel(node)}</p>
          )}
          {node.children?.length ? (
            <div className="pl-2">
              <MobileMenu nodes={node.children.filter((child) => child.is_hidden !== 1)} onNavigate={onNavigate} />
            </div>
          ) : null}
        </li>
      ))}
    </ul>
  );
}
