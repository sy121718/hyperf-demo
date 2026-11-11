import type { Switch } from "@/types/api";

/**
 * GET /api/admin/menu 返回的菜单节点。
 *
 * 该接口绕过了 MenuResponse，直接返回模型字段，
 * 所以权限码是逗号分隔的字符串而不是数组。
 */
export interface MenuNode {
  id: number;
  menu_code: string;
  menu_name: string;
  parent_id: number;
  type: number;
  title: string | null;
  path: string | null;
  component: string | null;
  external_url: string | null;
  icon: string | null;
  status: Switch;
  is_hidden: Switch;
  is_public: Switch;
  permission_code: string | null;
  sort_order: number;
  children?: MenuNode[];
}

/** 菜单类型，见 sys_menus.type 注释；接口校验目前只允许 0/1/2 */
export const MENU_TYPE = {
  DIRECTORY: 0,
  MENU: 1,
  PAGE: 2,
  BUTTON: 3,
} as const;

/** 只有 1、2 会生成路由，0 是分组、3 是纯权限点 */
export function isRoutableMenu(node: MenuNode): boolean {
  return node.type === MENU_TYPE.MENU || node.type === MENU_TYPE.PAGE;
}
