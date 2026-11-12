import { useEffect, useMemo, useRef, useState } from "react";
import { NavLink, useLocation } from "react-router-dom";
import { Activity, Bell, Circle, Pin, PinOff, Settings, type LucideIcon } from "lucide-react";

import { Button } from "@rbac/ui/components/ui/button";
import { cn } from "@rbac/ui/lib/utils";
import { MENU_TYPE, type MenuNode } from "@/types/menu";

/**
 * 一级图标栏 + 二级面板。
 *
 * 交互约定：
 * - 点一级项展开对应二级面板，再点同一个则收起；
 * - 点二级链接后除非已固定，否则面板自动收起；
 * - 图钉可固定面板，固定后切换页面不收起；
 * - 自动选中只在首次进入（含深链接刷新）时发生一次，
 *   否则点完菜单收起后会被路径变化重新展开。
 */

/**
 * 一级分组图标。
 *
 * 不用 `import * as icons from "lucide-react"` 做动态查表——
 * 那会把上千个图标全打进产物。分组只有几个，显式映射即可。
 */
const GROUP_ICONS: Record<string, LucideIcon> = {
  system_group: Settings,
  ops_group: Activity,
  notify_group: Bell,
};

function resolveIcon(menuCode: string): LucideIcon {
  return GROUP_ICONS[menuCode] ?? Circle;
}

/** 一级分组类型，手机端导航也复用 */
export type MenuGroup = MenuNode;

export interface SidebarNavProps {
  menus: MenuNode[];
}

/** 菜单节点取显示名，title 优先于 menu_name */
export function groupLabel(node: MenuNode): string {
  return node.title ?? node.menu_name;
}

export function SidebarNav({ menus }: SidebarNavProps) {
  const location = useLocation();

  const groups = useMemo(() => menus.filter((node) => node.is_hidden !== 1), [menus]);

  const [activeId, setActiveId] = useState<number | null>(null);
  const [pinned, setPinned] = useState(false);
  /** 首次自动选中只做一次，之后的路径变化不再干预面板开合 */
  const autoSelected = useRef(false);

  useEffect(() => {
    if (autoSelected.current || groups.length === 0) {
      return;
    }

    const matched = groups.find((group) => {
      if (group.path && location.pathname.startsWith(group.path)) {
        return true;
      }
      const walk = (nodes: MenuNode[] | undefined): boolean =>
        (nodes ?? []).some(
          (child) =>
            (child.path ? location.pathname.startsWith(child.path) : false) || walk(child.children),
        );
      return walk(group.children);
    });

    autoSelected.current = true;
    if (matched) {
      setActiveId(matched.id);
    }
  }, [groups, location.pathname]);

  /** 点在面板外时收起，固定状态除外 */
  useEffect(() => {
    if (pinned || activeId === null) {
      return;
    }

    const handlePointerDown = (event: MouseEvent) => {
      const target = event.target as HTMLElement | null;
      if (target?.closest("[data-sidebar-root]")) {
        return;
      }
      setActiveId(null);
    };

    document.addEventListener("mousedown", handlePointerDown);
    return () => document.removeEventListener("mousedown", handlePointerDown);
  }, [pinned, activeId]);

  const activeGroup = groups.find((group) => group.id === activeId) ?? null;

  const handleGroupClick = (id: number) => {
    if (activeId === id) {
      // 已展开：固定时只取消固定，未固定则收起
      if (pinned) {
        setPinned(false);
      } else {
        setActiveId(null);
      }
      return;
    }
    setActiveId(id);
  };

  /** 点二级链接后收起面板（固定时保留） */
  const closeIfNotPinned = () => {
    if (!pinned) {
      setActiveId(null);
    }
  };

  return (
    <div className="hidden h-svh md:flex" data-sidebar-root>
      <nav className="flex w-[76px] shrink-0 flex-col items-center gap-1 border-r border-sidebar-border bg-sidebar py-3">
        <div className="mb-3 flex size-10 items-center justify-center rounded-xl bg-primary text-sm font-semibold tracking-tight text-primary-foreground shadow-sm">
          RB
        </div>

        {groups.map((group) => {
          const Icon = resolveIcon(group.menu_code);
          const selected = group.id === activeId;
          return (
            <button
              key={group.id}
              type="button"
              title={groupLabel(group)}
              onClick={() => handleGroupClick(group.id)}
              className={cn(
                "relative flex w-[62px] flex-col items-center gap-1 rounded-lg px-1 py-2.5 text-[11px] leading-tight transition-all duration-150",
                selected
                  ? "bg-sidebar-accent font-medium text-sidebar-accent-foreground"
                  : "text-muted-foreground hover:bg-sidebar-accent/60 hover:text-foreground",
              )}
            >
              {/* 选中态左侧的强调条，替代整块高亮的笨重感 */}
              <span
                className={cn(
                  "absolute top-1/2 left-0 h-6 w-0.5 -translate-y-1/2 rounded-full bg-primary transition-opacity",
                  selected ? "opacity-100" : "opacity-0",
                )}
              />
              <Icon className={cn("size-5 transition-colors", selected && "text-primary")} />
              <span className="line-clamp-2 text-center">{groupLabel(group)}</span>
            </button>
          );
        })}
      </nav>

      {activeGroup ? (
        <aside className="flex w-56 shrink-0 flex-col border-r border-sidebar-border bg-sidebar">
          <div className="flex h-14 items-center justify-between gap-2 border-b border-sidebar-border px-4">
            <span className="truncate text-sm font-semibold tracking-tight">
              {groupLabel(activeGroup)}
            </span>
            <Button
              variant="ghost"
              size="icon-xs"
              title={pinned ? "取消固定" : "固定展开"}
              className={cn(pinned && "text-primary")}
              onClick={() => setPinned((prev) => !prev)}
            >
              {pinned ? <Pin /> : <PinOff />}
            </Button>
          </div>

          <div className="flex-1 overflow-y-auto px-2 py-3">
            {activeGroup.type === MENU_TYPE.MENU && activeGroup.path ? (
              <NavLink
                to={activeGroup.path}
                onClick={closeIfNotPinned}
                className={({ isActive }) =>
                  cn(
                    "mb-1 block truncate rounded-md px-2.5 py-2 text-sm transition-colors",
                    isActive
                      ? "bg-sidebar-accent font-medium text-sidebar-accent-foreground"
                      : "text-sidebar-foreground/80 hover:bg-sidebar-accent/60",
                  )
                }
              >
                {groupLabel(activeGroup)}
              </NavLink>
            ) : null}

            <SubMenu nodes={activeGroup.children ?? []} onNavigate={closeIfNotPinned} />
          </div>

          <div className="border-t border-sidebar-border px-4 py-3 text-[11px] text-muted-foreground">
            {pinned ? "面板已固定" : "点击空白处收起"}
          </div>
        </aside>
      ) : null}
    </div>
  );
}

/** 二级及更深层菜单。目录作为分组标题，不生成链接 */
function SubMenu({ nodes, onNavigate }: { nodes: MenuNode[]; onNavigate: () => void }) {
  const visible = nodes.filter((node) => node.is_hidden !== 1);
  if (visible.length === 0) {
    return null;
  }

  return (
    <ul className="space-y-0.5">
      {visible.map((node) => {
        const isDirectory = node.type === MENU_TYPE.DIRECTORY || !node.path;
        return (
          <li key={node.id}>
            {isDirectory ? (
              <p className="px-2.5 pt-3 pb-1 text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
                {groupLabel(node)}
              </p>
            ) : (
              <NavLink
                to={node.path as string}
                onClick={onNavigate}
                className={({ isActive }) =>
                  cn(
                    "block truncate rounded-md px-2.5 py-2 text-sm transition-colors",
                    isActive
                      ? "bg-sidebar-accent font-medium text-sidebar-accent-foreground"
                      : "text-sidebar-foreground/80 hover:bg-sidebar-accent/60 hover:text-foreground",
                  )
                }
              >
                {groupLabel(node)}
              </NavLink>
            )}
            {node.children?.length ? (
              <div className="pl-2">
                <SubMenu nodes={node.children} onNavigate={onNavigate} />
              </div>
            ) : null}
          </li>
        );
      })}
    </ul>
  );
}
