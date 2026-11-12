import { lazy, Suspense, type ComponentType, type LazyExoticComponent, type ReactElement } from "react";

import NotFoundPage from "@/pages/not-found";
import { RouteFallback } from "@/components/shared/page-loading";
import { isRoutableMenu, type MenuNode } from "@/types/menu";
import { resolvePage } from "@/router/pages";

/** 把后端菜单树摊平成可路由节点（只保留菜单/页面且配了 path、component 的） */
function flatten(menus: MenuNode[]): MenuNode[] {
  const result: MenuNode[] = [];
  const walk = (nodes: MenuNode[]): void => {
    for (const node of nodes) {
      if (isRoutableMenu(node) && node.path && node.component) {
        result.push(node);
      }
      if (node.children?.length) {
        walk(node.children);
      }
    }
  };
  walk(menus);
  return result;
}

/**
 * 懒加载组件缓存。
 *
 * `lazy()` 每次调用都产生一个新的组件类型，若在渲染期间调用，
 * 父组件每次重渲染都会让 React 认为换了一个组件而重新挂载，
 * 表现就是点菜单后一直停在「加载中」不跳页。按 component 字符串缓存，
 * 同一个页面始终复用同一个懒组件。
 */
const lazyCache = new Map<string, LazyExoticComponent<ComponentType>>();

function getLazyPage(component: string): LazyExoticComponent<ComponentType> | null {
  const loader = resolvePage(component);
  if (!loader) {
    return null;
  }

  const cached = lazyCache.get(component);
  if (cached) {
    return cached;
  }

  const created = lazy(loader as () => Promise<{ default: ComponentType }>);
  lazyCache.set(component, created);
  return created;
}

function LazyPage({ component }: { component: string }) {
  const Component = getLazyPage(component);

  if (!Component) {
    // 后端配了组件但前端没有对应文件：明确提示，不白屏
    console.warn(`[router] 菜单组件未找到：${component}`);
    return <NotFoundPage />;
  }

  return (
    <Suspense fallback={<RouteFallback />}>
      <Component />
    </Suspense>
  );
}

export interface DynamicRoute {
  /** 相对 basename 的路径，如 "system/admin" */
  path: string;
  element: ReactElement;
}

/** 生成挂在 AdminLayout 下的子路由 */
export function buildDynamicRoutes(menus: MenuNode[]): DynamicRoute[] {
  return flatten(menus).map((node) => ({
    // 后端 path 以 / 开头，去掉后作为根路由下的相对路径；
    // /admin 前缀由 BrowserRouter basename 统一处理
    path: (node.path as string).replace(/^\/+/, ""),
    element: <LazyPage component={node.component as string} />,
  }));
}
