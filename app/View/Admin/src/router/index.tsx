import { useQuery } from "@tanstack/react-query";
import { BrowserRouter, Route, Routes } from "react-router-dom";

import { getMenus } from "@/api/auth";
import AdminLayout from "@/layouts/admin-layout";
import LoginPage from "@/pages/login";
import ForbiddenPage from "@/pages/forbidden";
import NotFoundPage from "@/pages/not-found";
import { isLoggedIn } from "@/stores/auth";
import { buildDynamicRoutes } from "@/router/dynamic-routes";

/**
 * 后台路由。
 *
 * 动态路由依赖登录后的菜单树，因此在组件内生成：
 * 未登录只渲染登录页，登录后拉菜单并挂载页面路由。
 */
export function AppRouter() {
  const loggedIn = isLoggedIn();

  const { data: menus } = useQuery({
    queryKey: ["admin", "menu"],
    queryFn: getMenus,
    enabled: loggedIn,
    staleTime: 5 * 60_000,
  });

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/admin/login" element={<LoginPage />} />
        <Route path="/admin/403" element={<ForbiddenPage />} />
        <Route
          path="/admin"
          element={loggedIn ? <AdminLayout menus={menus ?? []} /> : <LoginPage />}
        >
          {loggedIn &&
            buildDynamicRoutes(menus ?? []).map((route) => (
              <Route key={route.path} path={route.path} element={route.element} />
            ))}
          <Route index element={<NotFoundPage />} />
          <Route path="*" element={<NotFoundPage />} />
        </Route>
        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </BrowserRouter>
  );
}
