/**
 * 页面组件映射表。
 *
 * 后端菜单的 component 字段是相对 src/pages 的路径（如 system/admin/index），
 * 这里用 import.meta.glob 静态展开成映射表，避免运行时字符串拼接 import
 * 导致构建期无法分析、并留下路径注入面。
 */
const modules = import.meta.glob([
  "../pages/**/index.tsx",
  // 登录/403/404 是固定入口页，会被静态 import，排除掉避免重复打包
  "!../pages/{login,forbidden,not-found}/index.tsx",
]);

/** 把 "system/admin/index" 转成 glob 映射表的 key */
function toModuleKey(component: string): string {
  return `../pages/${component.trim().replace(/^\/+/, "")}.tsx`;
}

export function resolvePage(component: string): (() => Promise<unknown>) | null {
  const key = toModuleKey(component);
  return modules[key] ?? null;
}
