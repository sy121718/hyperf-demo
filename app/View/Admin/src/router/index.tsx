import { useQuery } from "@tanstack/react-query";
import { BrowserRouter, Navigate, Route, Routes } from "react-router-dom";

import { getMenus } from "@/api/auth";
import AdminLayout from "@/layouts/admin-layout";
import LoginPage from "@/pages/login";
import DashboardPage from "@/pages/dashboard";
import ForbiddenPage from "@/pages/forbidden";
import NotFoundPage from "@/pages/not-found";
import { useAuthStore } from "@/stores/auth";
import { buildDynamicRoutes } from "@/router/dynamic-routes";

/**
 * 后台路由。
 *
 * 动态路由依赖登录后的菜单树，因此在组件内生成：
 * 未登录只渲染登录页，登录后拉菜单并挂载页面路由。
 */
export function AppRouter() {
  // 订阅 token 而不是渲染时读 localStorage，否则登录后路由不会重新挂载
  const loggedIn = useAuthStore((state) => state.token !== null);

  const { data: menus } = useQuery({
    queryKey: ["admin", "menu"],
    queryFn: getMenus,
    enabled: loggedIn,
    staleTime: 5 * 60_000,
  });

  return (
    <BrowserRouter basename="/admin">
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/403" element={<ForbiddenPage />} />
        <Route
          path="/"
          element={loggedIn ? <AdminLayout menus={menus ?? []} /> : <Navigate to="/login" replace />}
        >
          {/* 首页不依赖后端菜单，登录后始终可访问 */}
          <Route index element={<DashboardPage />} />
          {loggedIn &&
            buildDynamicRoutes(menus ?? []).map((route) => (
              <Route key={route.path} path={route.path} element={route.element} />
            ))}
          <Route path="*" element={<NotFoundPage />} />
        </Route>
        <Route path="*" element={<NotFoundPage />} />
      </Routes>
    </BrowserRouter>
  );
}
