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

export const MENU_TYPE_LABELS: Record<number, string> = {
  [MENU_TYPE.DIRECTORY]: "目录",
  [MENU_TYPE.MENU]: "菜单",
  [MENU_TYPE.PAGE]: "页面",
  [MENU_TYPE.BUTTON]: "按钮",
};

/** 只有 1、2 会生成路由，0 是分组、3 是纯权限点 */
export function isRoutableMenu(node: MenuNode): boolean {
  return node.type === MENU_TYPE.MENU || node.type === MENU_TYPE.PAGE;
}

/**
 * 菜单管理页用的树节点（GET /api/menu/tree 的返回）。
 *
 * 与 MenuNode 的差别：该接口经 MenuResponse 输出，权限码是数组 permission_codes。
 */
export interface MenuTreeNode {
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
  permission_codes: string[];
  sort_order: number;
  children?: MenuTreeNode[];
}

/** GET /api/menu/info/{id} 的返回 */
export interface MenuDetail {
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
  permission_codes: string[];
  sort_order: number;
  create_time: string | null;
  update_time: string | null;
}

export interface MenuSaved {
  id: number;
  menu_code: string;
  menu_name: string;
}

/** 创建/更新菜单的提交体，字段与 MenuRequest 一致 */
export interface MenuPayload {
  menu_code?: string;
  menu_name?: string;
  parent_id?: number;
  type?: number;
  title?: string;
  path?: string;
  component?: string;
  external_url?: string;
  icon?: string;
  status?: Switch;
  is_hidden?: Switch;
  is_public?: Switch;
  sort_order?: number;
  permission_codes?: string[];
}
