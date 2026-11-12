import type { MenuNode } from "@/types/menu";

/** 首页固定标签，不依赖后端菜单 */
export const HOME_TAB = { path: "/", title: "概览", closable: false } as const;

/** 菜单树摊平成「路径 → 标题」，用于路由变化时给标签取名 */
export function buildPathTitles(menus: MenuNode[]): Map<string, string> {
  const map = new Map<string, string>();
  const walk = (nodes: MenuNode[]): void => {
    for (const node of nodes) {
      if (node.path && node.is_hidden !== 1) {
        map.set(node.path, node.title ?? node.menu_name);
      }
      if (node.children?.length) {
        walk(node.children);
      }
    }
  };
  walk(menus);
  return map;
}
