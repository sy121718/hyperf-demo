# 前端工程规范（app/View）

本目录存放前端页面工程，包含两个独立站点：

| 站点 | 目录 | 部署路径 | 定位 |
|------|------|----------|------|
| 后台 | `Admin/` | `/admin/` | RBAC 管理系统，管理员使用 |
| 前台 | `Index/` | `/` | 面向普通用户（通知公告、个人中心等） |

两个站点共用一套 shadcn/ui 组件，但**依赖、构建、部署产物完全隔离**。

> 本规范优先于根目录 `CLAUDE.md` 中的技术栈描述。根文档写的是 Vue，实际以本文件的 React 为准。

---

## 一、技术栈

| 项 | 选型 | 说明 |
|----|------|------|
| 框架 | React 19 | 函数组件 + Hooks，不写 class 组件 |
| 构建 | Vite 8 | 两个 app 各自一份 `vite.config.ts` |
| 语言 | TypeScript 7 | `strict: true`，禁止 `any` |
| 样式 | Tailwind CSS 4 | 无 `tailwind.config.js`，配置写在 CSS 里 |
| 组件 | shadcn/ui（CLI 4.x） | 代码复制进仓库，不是 npm 依赖 |
| 路由 | react-router-dom 7 | 浏览器 History 模式 |
| 数据请求 | @tanstack/react-query 5 | 服务端状态唯一入口 |
| 表单 | react-hook-form 7 + zod 4 | 配合 shadcn Form |
| 表格 | @tanstack/react-table 8 | 服务端分页 |
| HTTP | axios | 仅在 `lib/http.ts` 中使用 |
| 图标 | lucide-react | 与 shadcn 默认一致 |
| 提示 | sonner | 禁止 `alert` / `window.confirm` |

具体小版本以 `pnpm-lock.yaml` 为准。当前时间点查到的版本：react 19.3、vite 8.3、typescript 7.0、tailwindcss 4.3、shadcn 4.23、react-router-dom 7.18。

---

## 二、目录结构

```
app/View/
├── pnpm-workspace.yaml        # packages: [Index, Admin, Shared]
├── package.json               # 私有根包，只放脚本与共享开发依赖
├── pnpm-lock.yaml
├── tsconfig.base.json         # 两个 app 继承的编译配置
├── .npmrc
├── .gitignore
│
├── Shared/                    # @rbac/ui —— 跨站点共享包
│   ├── package.json
│   ├── components.json
│   ├── tsconfig.json
│   └── src/
│       ├── components/
│       │   └── ui/            # shadcn 原子组件（button、dialog、table…）
│       ├── hooks/             # 跨站点 hooks
│       ├── lib/
│       │   └── utils.ts       # cn() 等无状态工具
│       └── types/             # 跨站点公共类型
│
├── Admin/                     # @rbac/admin —— 后台
│   ├── index.html
│   ├── package.json
│   ├── components.json
│   ├── vite.config.ts
│   ├── tsconfig.json
│   └── src/
│       ├── main.tsx
│       ├── router/
│       │   ├── index.tsx      # 路由装配
│       │   ├── static-routes.tsx  # 登录、403、404、首页等固定路由
│       │   ├── dynamic-routes.tsx # 后端菜单生成的路由
│       │   └── guard.tsx      # 登录态守卫
│       ├── layouts/
│       │   ├── admin-layout.tsx   # 侧边栏 + 顶栏 + 面包屑
│       │   └── auth-layout.tsx    # 登录页布局
│       ├── pages/             # 与后端菜单 component 字段一一对应
│       │   ├── login/index.tsx
│       │   ├── dashboard/index.tsx
│       │   ├── system/        # 管理员、角色、菜单、权限、字典、配置
│       │   ├── ops/           # 会话、日志、缓存、定时任务
│       │   └── notify/        # 通知公告、邮件
│       ├── api/               # 按后端模块划分的接口函数
│       │   ├── auth.ts
│       │   ├── admin.ts
│       │   ├── role.ts
│       │   └── ...
│       ├── components/        # 业务组件（非 shadcn 原子组件）
│       │   ├── layout/
│       │   └── shared/
│       ├── hooks/
│       ├── lib/
│       │   ├── http.ts        # axios 实例与拦截器（唯一出处）
│       │   ├── auth.ts        # token 存取
│       │   ├── permission.ts  # 权限判断
│       │   └── format.ts      # 时间/枚举格式化
│       ├── styles/
│       │   └── globals.css
│       └── types/
│
└── Index/                     # @rbac/index —— 前台
    ├── index.html
    ├── package.json
    ├── components.json
    ├── vite.config.ts
    ├── tsconfig.json
    └── src/
        ├── main.tsx
        ├── router/
        ├── layouts/
        ├── pages/
        ├── api/
        ├── components/
        ├── hooks/
        ├── lib/
        ├── styles/
        └── types/
```

约定：

- 只有 `Shared/src/components/ui` 放 shadcn 原子组件；业务组件不许放进去。
- 页面文件必须是 `pages/<模块>/<功能>/index.tsx`，目录路径即路由标识。
- 类型定义跟随模块：接口入参/出参类型放 `src/types/<module>.ts`，不散落在组件文件里。

---

## 三、workspace 与依赖规则

`pnpm-workspace.yaml`：

```yaml
packages:
  - Index
  - Admin
  - Shared
```

- 依赖必须显式声明在各自 `package.json`，禁止依赖幽灵依赖（不能因为根目录装了就直接用）。
- `Shared` 的 `package.json` 用 `exports` 暴露入口，至少包含 `./components/*`、`./lib/*`、`./hooks/*`、`./types/*`。
- 两个 app 通过 `"@rbac/ui": "workspace:*"` 引用 `Shared`。
- 新增第三方库时，先在 `Admin` 或 `Index` 本地装；确认两边都要才提升到根，且必须说明原因。
- 禁止在 `app/View` 以外的目录放前端源码。

常用脚本（根 `package.json`）：

```json
{
  "scripts": {
    "dev": "pnpm -r --parallel dev",
    "dev:admin": "pnpm --filter @rbac/admin dev",
    "dev:index": "pnpm --filter @rbac/index dev",
    "build": "pnpm --filter @rbac/index build && pnpm --filter @rbac/admin build",
    "lint": "eslint .",
    "typecheck": "pnpm -r typecheck"
  }
}
```

构建顺序固定：先 `Index` 再 `Admin`（见第十一节）。

---

## 四、shadcn 使用规则

### 4.1 三个 components.json

`Shared/components.json`（本包内安装组件时用）：

```json
{
  "$schema": "https://ui.shadcn.com/schema.json",
  "style": "base-nova",
  "rsc": false,
  "tsx": true,
  "tailwind": {
    "config": "",
    "css": "",
    "baseColor": "neutral",
    "cssVariables": true
  },
  "iconLibrary": "lucide",
  "aliases": {
    "components": "#components",
    "ui": "#components/ui",
    "lib": "#lib",
    "hooks": "#hooks",
    "utils": "#lib/utils"
  }
}
```

`Admin/components.json`（`Index` 同理）：

```json
{
  "$schema": "https://ui.shadcn.com/schema.json",
  "style": "base-nova",
  "rsc": false,
  "tsx": true,
  "tailwind": {
    "config": "",
    "css": "src/styles/globals.css",
    "baseColor": "neutral",
    "cssVariables": true
  },
  "iconLibrary": "lucide",
  "aliases": {
    "components": "@/components",
    "ui": "@rbac/ui/components/ui",
    "lib": "@/lib",
    "hooks": "@/hooks",
    "utils": "@rbac/ui/lib/utils"
  }
}
```

硬性要求：

- 三个文件的 `style`、`baseColor`、`iconLibrary` 必须完全一致，否则组件类名与主题变量会错位。
- Tailwind 4 场景下 `tailwind.config` 留空字符串，不要填 `tailwind.config.js`。
- `ui` 别名必须指向 `@rbac/ui/components/ui`，这样在任意 app 目录执行 `pnpm dlx shadcn@latest add xxx` 都会装到 `Shared`，不会出现两份。
- 安装命令统一在 `Shared/` 目录执行，避免 CLI 写入路径歧义。

### 4.2 组件修改边界

- `Shared/src/components/ui/*` 视为第三方源码，允许改样式细节，但不允许改对外 props 语义。
- 需要定制行为时，优先在 app 内包一层业务组件，不要直接改原子组件。
- 修改原子组件后必须两个 app 都跑一遍（`pnpm dev` + 构建），确认没有视觉回归。

### 4.3 样式来源

Tailwind 4 默认忽略 `node_modules`，`Shared` 是通过 workspace 软链进入 `node_modules` 的，**必须在每个 app 的 `globals.css` 中显式声明来源**：

```css
@import "tailwindcss";
@import "tw-animate-css";
@custom-variant dark (&:is(.dark *));

/* 让 Tailwind 扫描共享包，否则 Shared 里的组件类名会被摇掉 */
@source "../../../Shared/src";

@theme inline {
  /* ...shadcn 生成的 token 映射... */
}
```

漏写 `@source` 的典型症状：后台页面里的按钮、弹窗完全没有样式。

---

## 五、样式与主题规范

- 颜色一律用语义 token：`bg-background`、`text-muted-foreground`、`border-border`、`bg-destructive`。
- **禁止**硬编码颜色值（`#fff`、`rgb(0,0,0)`、`text-[#333]`）。
- 禁止内联 `style`，除非值在运行时才确定（如进度条宽度）。
- 间距用 Tailwind 刻度（`p-4`、`gap-2`），禁止 `margin-left: 13px` 这类魔数。
- 深色模式采用 class 策略：`<html class="dark">`，用户选择存 `localStorage`，默认跟随 `prefers-color-scheme`。
- 两个 app 各自维护 `src/styles/globals.css`（含 `:root` / `.dark` 变量）。当前不抽公共主题；若后续要求两边完全一致，再抽到 `Shared`，不要在未确认前预置抽象。
- 后台可用不同 `--primary` 体现管理端气质，但 `baseColor` 必须与前台一致。

---

## 六、与后端的接口契约

### 6.1 统一响应体

```ts
interface ApiResponse<T> {
  success: boolean;
  code: number;
  message: string;
  data: T;
}
```

**判定成功只看 `body.code === 200`，不能用 HTTP 状态码判断**：

| 场景 | HTTP 状态 | body.code | body.data |
|------|-----------|-----------|-----------|
| 成功 | 200 | 200 | 业务数据 |
| 业务异常（参数错误/未登录/无权限） | **200** | 400/401/403/404 | null |
| 参数校验失败 | 422 | 422 | `{ 字段名: [错误信息] }` |
| 路由不存在 / 方法不允许 | 404 / 405 | 同状态码 | null |
| 未捕获异常 | 200 | 500 | null |

### 6.2 认证

- 接口前缀 `/api`，JWT 放在请求头：`Authorization: Bearer <token>`。
- 后端也接受查询参数 `?token=`，仅用于无法设置请求头的场景（下载、iframe 预览）。
- 登录返回：`{ token, token_type: "bearer", expires_in, admin_info }`。
- 滑动续期：后端在响应头下发 `New-Token`、`New-Token-Expires-In`（CORS 已暴露）。前端拦截器收到 `New-Token` 时必须替换本地 token，否则用户会在长时间操作中被踢出。
- token 存储 key：后台 `rbac.admin.token`，前台 `rbac.index.token`，互不覆盖。

401 的 `error_code` 取值：

| error_code | 前端处理 |
|------------|----------|
| `TOKEN_MISSING` / `TOKEN_INVALID` / `TOKEN_EXPIRED` | 清 token，跳登录页并带 `redirect` |
| `SESSION_NOT_FOUND` / `USER_NOT_FOUND` | 清 token，跳登录页，提示「登录状态已失效」 |
| `AUTH_ERROR` | 同上 |

403 只提示「权限不足」，**不跳登录**。

### 6.3 列表查询参数

请求（GET query）：

| 参数 | 说明 |
|------|------|
| `page` | 页码，从 1 开始 |
| `limit` | 每页条数。多数模块默认 10（IP 黑名单默认 20）；`BaseRequestDto` 校验上限 100 |
| `search` | 模糊搜索，后端按模型白名单字段做 OR 匹配 |
| `sort[field]` | 排序字段，必须在后端白名单内，否则忽略 |
| `sort[direction]` | `asc` / `desc` |
| 其他 | 精确筛选字段，字段名必须是后端 `whereableFields` 白名单 |

响应：`data = { total: number, list: T[] }`。**列表一律服务端分页，禁止前端分页。**

### 6.4 字段命名

- 后端接口出参、入参**全部是 snake_case**，前端不做驼峰转换。
- 类型定义直接写 snake_case：

```ts
export interface AdminRow {
  id: number;
  username: string;
  admin_code: string;
  last_login_time: string | null;
  create_time: string;
}
```

- 组件内部局部变量可以用 camelCase，但跨接口边界的对象必须保持原始字段名。禁止引入 mapper 层做字段重命名。

### 6.5 时间

- 后端统一按 UTC 写入，返回格式 `Y-m-d H:i:s.v`（例：`2026-11-11 05:53:43.389`）。
- 前端只允许在 `lib/format.ts` 里做一次解析与本地时区转换，组件禁止各自 `new Date(xxx)` 后再拼字符串。

### 6.6 状态字段

`status`、`is_admin`、`is_hidden` 等是数字 `0 / 1`，不是布尔。类型写 `0 | 1`，不要转成 boolean 再转回来。

### 6.7 权限码

- 格式 `模块:动作`，例：`admin:create`、`role:assignMenus`、`notice:unreadCount`。
- 权限码是后端鉴权的唯一凭据，前端只做展示层隐藏，不做安全边界。

### 6.8 文件上传（现状缺口）

`POST /api/attachment/upload` 当前只接收文件元数据 JSON（`file_name`、`file_path`、`file_size`、`file_type`、`mime_type`、`md5` 等），**不接收 multipart 文件流**。

前端上传封装 `uploadFile()` 必须集中在一个地方，等后端补齐真实文件上传后只改这一处。在此之前不要在各页面自行拼接 FormData。

---

## 七、请求层规范

- 唯一 axios 实例在 `src/lib/http.ts`，**组件、页面、hooks 里禁止直接 import axios**。
- baseURL 取 `import.meta.env.VITE_API_BASE_URL`，开发环境默认 `/api`，由 Vite proxy 转发到 `http://127.0.0.1:9501`。
- 响应拦截器职责固定：
  1. `body.code === 200` → 返回 `body.data`
  2. `body.code` 为 401 族 → 清 token、跳登录（同 6.2）
  3. `body.code === 422` → 抛出带字段错误的异常，交给表单处理，**不弹全局 toast**
  4. 其他 → sonner 提示 `body.message`，并抛出异常
  5. 捕获到 `New-Token` 响应头 → 更新本地 token
- 网络异常统一提示「网络异常，请稍后重试」，不暴露原始错误。
- 接口函数按后端模块组织，一个 `api/<module>.ts` 对应一个后端模块：

```ts
import { http } from "@/lib/http";
import type { AdminRow } from "@/types/admin";

export interface AdminListParams {
  page?: number;
  limit?: number;
  search?: string;
  status?: 0 | 1;
}

export function getAdminList(params: AdminListParams) {
  return http.get<{ total: number; list: AdminRow[] }>("/admin/list", { params });
}
```

- 所有请求走 react-query；`queryKey` 约定 `[模块, 动作, 参数对象]`，例：`["admin", "list", params]`。
- 写操作成功后必须 `invalidateQueries` 对应列表 key，禁止手动改本地缓存数组。

---

## 八、鉴权与权限

### 8.1 登录态

- 后台除 `/admin/login` 外全部需要登录；路由守卫在 `router/guard.tsx`。
- 未登录访问受保护路由：重定向到 `/admin/login?redirect=<encodeURIComponent(当前路径)>`。
- 登录成功后跳 `redirect` 参数指定的路径，缺省跳 `/admin/dashboard`。

### 8.2 权限数据来源（现状缺口，必须先确认）

**目前后端没有「获取当前登录用户权限码」的接口**：

- 登录返回的 `admin_info` 不含权限码；
- `GET /api/auth/profile` 返回的 `roles` 只有 `id / role_name / role_code`，不含 `permission_codes`；
- `GET /api/admin/permissions/{id}` 返回 `{ admin_id, admin_code, roles, permission_codes }`，但需要传 id 且需要 `admin:getPermissions` 权限。

在前端落地按钮级权限前，需后端补一个当前用户权限接口（建议 `GET /api/auth/permissions`，复用 `App\Service\Admin\Permission\GetPermissions` 的聚合逻辑）。前端约定只从这一个接口取权限码，封装为：

```ts
// src/lib/permission.ts
export function hasPermission(code: string): boolean;
```

在接口补齐前，允许的临时降级：

- `is_admin === 1` 视为拥有全部权限；
- 普通用户以「菜单树 + 接口 403 兜底」为准，不做按钮级隐藏。

### 8.3 组件用法

```tsx
<Can code="admin:create">
  <Button>新增管理员</Button>
</Can>
```

- `Can` 组件与 `usePermission()` 只读同一份权限码集合。
- 超管恒为 true。
- 前端隐藏按钮**不等于**接口安全，后端 `PermissionMiddleware` 仍是最终判定。

---

## 九、路由规范

### 9.1 前台

- 全部静态路由，不依赖后端菜单。
- 路径约定：`/`、`/login`、`/profile`、`/notice`、`/notice/:id`。

### 9.2 后台

- 固定路由（`static-routes.tsx`）：`/admin/login`、`/admin/403`、`/admin/404`、`/admin/dashboard`。
- 动态路由（`dynamic-routes.tsx`）来自 `GET /api/admin/menu`，返回树形结构。
- **该接口绕过了 `MenuResponse`，返回模型原始字段**，所以权限码字段是逗号分隔的 `permission_code: string | null`，不是数组。只有 `GET /api/menu/tree` 才返回 `permission_codes: string[]`。前端取当前用户权限码时不要依赖这个接口，见 8.2。

```ts
// GET /api/admin/menu 实际返回
interface MenuNode {
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
  status: 0 | 1;
  is_hidden: 0 | 1;
  is_public: 0 | 1;
  permission_code: string | null; // 逗号分隔，非数组
  sort_order: number;
  children?: MenuNode[];
}
```

`type` 含义（取自 `sys_menus.type` 建表注释）：

| 值 | 含义 | 是否生成路由 | 是否显示在侧边栏 |
|----|------|--------------|------------------|
| 0 | 目录 | 否 | 是（仅作分组） |
| 1 | 菜单 | 是 | 是 |
| 2 | 页面 | 是 | 是 |
| 3 | 按钮 | 否 | 否（仅权限点） |

后端校验规则：`type === 0` 不能设权限码，其余类型必须设权限码。因此按钮类权限点一定带 `permission_code`，前端应把它并入权限码集合，而不是当成路由。

**注意现状不一致**：`sys_menus.type` 的建表注释有 `3=按钮`，但 `App\Dto\Menu\MenuRequest` 的校验规则是 `in:0,1,2`，接口层面创建不出 `type=3` 的记录；`Service\Menu\Create` 的默认值也是 `2`。前端按「0 目录 / 1、2 页面」处理即可，遇到未知 `type` 一律不生成路由、不显示菜单项。

- `component` 约定为相对 `src/pages` 的路径，例：`system/admin/index`。
- 前端用 `import.meta.glob("../pages/**/index.tsx")` 建立静态映射表，按 `component` 查表取懒加载组件。**禁止用动态 import 拼接任意字符串**（构建期无法分析，且存在注入风险）。
- `component` 在映射表中缺失时：渲染 404 页并 `console.warn`，不要白屏。

- 映射表的 glob 路径要相对于调用文件写（在 `src/router/` 下即 `../pages/**/index.tsx`），构建时静态展开；若改为运行时字符串拼接，Vite 无法预打包，且构成注入面。
- 后端现有的种子菜单 `path`/`component` 多为空（当前只有目录与一个 `type=1` 的探针菜单），接入前需补齐真实菜单数据。
- `icon` 是 lucide 图标名（kebab-case，如 `user-round`），映射不到时回退默认图标。
- `is_hidden === 1` 的路由仍要注册，只是不进侧边栏。
- `path` 直接用后端给的值（`/system/admin` 这类），`/admin` 前缀由 Vite `base` 与 `BrowserRouter basename` 统一处理，不要在菜单里重复拼。

### 9.3 兜底

- 未匹配路由渲染 404 页。
- 数据请求 403 时渲染 403 页，不跳转。

### 9.4 列表页：一份 columns，桌面表格 + 手机卡片自动派生

列表页**只写一份 `columns`**，手机端卡片由 `ResponsiveDataList` 自动推导，不要手写第二套 UI。
新增列表页只需三步：

```tsx
// 1. 数据源：桌面服务端分页 / 手机无限滚动，同一套参数
const { items, total, loading, loadingMore, hasNextPage, sentinelRef, isMobile } =
  useResponsiveList<RoleRow>({
    queryKey: queryKeys.role.all,
    fetcher: getRoleList,
    params: listState.params,
    page: listState.page,
    limit: listState.limit,
  });

// 2. 列定义里用 mobile 标记手机端角色
const columns: DataTableColumn<RoleRow>[] = [
  { key: "role_name", header: "角色名称", mobile: "title" },   // 卡片标题
  { key: "status", header: "状态", mobile: "badge", cell: ... }, // 标题右侧徽标
  { key: "id", header: "ID", mobile: "hidden" },               // 手机端不显示
  { key: "role_code", header: "角色标识", cell: ... },          // 自动成为字段
  { key: "actions", header: "操作", cell: renderActions },      // 卡片底部操作区
];

// 3. 渲染
<ResponsiveDataList
  columns={columns} items={items} total={total} loading={loading}
  loadingMore={loadingMore} hasNextPage={hasNextPage}
  sentinelRef={sentinelRef} isMobile={isMobile}
  rowKey={(row) => row.id}
  page={listState.page} limit={listState.limit}
  onPageChange={listState.setPage} onLimitChange={listState.setLimit}
/>
```

约定：

- `mobile` 取值 `title` / `badge` / `hidden`，不填默认作为卡片字段；`mobileLabel` 可覆盖字段名。
- 手机上无限滚动，**后端仍是普通分页**（`page` + `limit`），`useResponsiveList` 把多页结果拼起来；剩余不足 2 页时自动预取。
- 工具条统一用 `ListToolbar`：桌面平铺筛选控件，手机只留搜索 + 新增，其余筛选收进底部抽屉。页面只传 `filters` 与 `hasActiveFilters`，不要自己判断断点。
- 树形列表用 `TreeTable`（桌面）+ `TreeMobileList`（手机），二者共用同一份节点数据。
- 新增页面时**不要**再引入 `MobileCardList` 手写字段数组，那是底层组件，由 `ResponsiveDataList` 使用。

---

## 十、命名与代码规范

| 对象 | 规则 | 示例 |
|------|------|------|
| 目录 / 文件 | kebab-case | `admin-layout.tsx`、`use-permission.ts` |
| 组件 | PascalCase 导出 | `AdminLayout` |
| hook | `use` 前缀 | `useAdminList` |
| 类型 | PascalCase，接口数据加 `Row` / `Params` / `Payload` 后缀 | `AdminRow`、`AdminListParams` |
| 常量 | UPPER_SNAKE_CASE | `DEFAULT_PAGE_SIZE` |
| CSS 类 | Tailwind 原子类，不做自定义 BEM | - |

- 注释使用中文，解释「为什么」，不解释「做了什么」。
- 组件超过 200 行必须拆分。
- 一个文件只导出一个主组件（同名默认导出或具名导出均可，模块内保持一致）。
- 禁止 `any`；外部数据用 `unknown` 收窄。禁止 `as` 断言绕过类型（`as const` 除外）。
- 枚举值集中定义在 `src/types/<module>.ts`，不要在页面里散写 `0` / `1` 字面量。

---

## 十一、构建与部署

### 11.1 Vite 配置

`Admin/vite.config.ts`：

```ts
export default defineConfig({
  plugins: [react(), tailwindcss()],
  base: "/admin/",
  resolve: { alias: { "@": path.resolve(__dirname, "./src") } },
  build: {
    outDir: "../../../public/admin",
    emptyOutDir: true, // 独立目录，可以安全清空
  },
  server: {
    port: 5173,
    proxy: { "/api": { target: "http://127.0.0.1:9501", changeOrigin: true } },
  },
});
```

`Index/vite.config.ts`：

```ts
export default defineConfig({
  plugins: [react(), tailwindcss()],
  base: "/",
  resolve: { alias: { "@": path.resolve(__dirname, "./src") } },
  build: {
    outDir: "../../../public",
    // 与 Admin 共用 public 根，必须关闭清空，否则会删掉 public/admin
    emptyOutDir: false,
  },
  server: {
    port: 5174,
    proxy: { "/api": { target: "http://127.0.0.1:9501", changeOrigin: true } },
  },
});
```

要点：

- 后台构建到 `public/admin`，前台构建到 `public` 根，两者资源路径因 `base` 不同（`/assets/*` 与 `/admin/assets/*`）不会冲突。
- 前台必须 `emptyOutDir: false`，且构建顺序固定为先前台后台（根脚本已保证）。
- 构建产物不入库，`public/` 下的前端产物加入 `.gitignore`（上传文件目录除外）。

### 11.2 Hyperf 托管

后端 `config/autoload/server.php` 需要开启 Swoole 静态文件处理：

```php
'settings' => [
    // ...
    'document_root' => BASE_PATH . '/public',
    'enable_static_handler' => true,
],
```

注意：

- Swoole 静态处理优先于 PHP 路由，因此 `public/` 下不要出现与 API 前缀同名的目录。
- Swoole 只处理真实存在的文件，SPA 深链接（如 `/admin/system/admin`、`/notice/12`）会落到 404。需要后端加一个 GET 兜底：`/api/*` 之外的请求若文件不存在，则分别返回 `public/index.html` 或 `public/admin/index.html`（保留真实 HTTP 200）。此兜底当前尚未实现，属于后端待办。
- 现有的 `Router::get('/', fn () => [...])` 与前台占用根路径冲突，前台接入时需移除。
- Swoole 静态响应不带缓存头。`/assets/*`（带 hash）希望长缓存、`index.html` 必须不缓存，需要在网关层配置或用中间件补 `Cache-Control`；仅靠 Swoole 默认行为不可行。

### 11.3 环境变量

| 变量 | 开发 | 生产 |
|------|------|------|
| `VITE_API_BASE_URL` | `/api` | `/api` |

- 只允许 `VITE_` 前缀变量进入前端代码，且不得放任何密钥。
- 环境文件：`.env.development`、`.env.production`，提交仓库；个人覆盖用 `.env.local`（忽略）。

---

## 十二、一次性配置改动清单（首次落地前完成）

| 文件 | 改动 | 原因 |
|------|------|------|
| `.gitignore` | 增加 `node_modules/`、`app/View/**/dist/`、`public/assets/`、`public/admin/`、`public/index.html` | 依赖与构建产物不入库 |
| `.php-cs-fixer.php` | Finder 增加 `->exclude('node_modules')`、`->exclude('app/View')` | 现有 Finder 只默认排除 `vendor`，会进入 `app/View/node_modules` 找 `*.php`。pnpm 的包目录是软链、Finder 默认不跟随，所以不会失控，但每次 `composer cs-fix` 仍会多遍历一层，且一旦有依赖自带 `.php` 文件就会被纳入格式化 |
| `config/autoload/server.php` | 增加 `document_root` 与 `enable_static_handler` | 托管前端产物 |
| `config/routes.php` | 移除根路径 `/` 返回 JSON 的路由 | 让位给前台 |
| `CLAUDE.md`（根） | 技术栈 Vue 改为 React | 消除文档冲突 |

`app/` 在 Composer PSR-4 下是 `App\` 命名空间根，前端源码不含 `.php`，不影响自动加载。Hyperf 注解扫描会对 `app` 目录做 `*.php` 遍历，但 pnpm 的布局使其成本可控：`.pnpm` 虚拟仓库位于 `app/View/node_modules/.pnpm`（点目录，Symfony Finder 默认忽略），各包自己的 `node_modules` 里只有符号链接（Finder 默认不跟随），因此不会把依赖树扫一遍。前提是**不要把 `node-linker` 改成 `hoisted`**——那会把真实依赖摊平进 `node_modules`，扫描成本立刻上升。

---

## 十三、禁止事项

- 禁止在 `app/View` 下写 PHP 业务代码。
- 禁止两个 app 各自安装一份 shadcn 组件（必须共享 `Shared/src/components/ui`）。
- 禁止组件内直接使用 axios / fetch。
- 禁止用 HTTP 状态码判断业务成功。
- 禁止把接口字段驼峰化后再定义类型。
- 禁止前端分页。
- 禁止 `window.confirm` / `alert` / 原生 `prompt`。
- 禁止硬编码颜色、魔数间距、内联样式。
- 禁止用动态 import 字符串渲染后端下发的组件路径。
- 禁止把 `is_admin` 之外的权限判断散落在页面里，统一走 `hasPermission()`。
- 禁止提交 `node_modules`、`dist`、`public` 下的构建产物。
- 禁止在未确认后端契约前假设接口存在（例如当前的文件上传、当前用户权限码）。

---

## 十四、开发检查清单

新增一个后台页面时：

- [ ] 页面路径为 `Admin/src/pages/<模块>/<功能>/index.tsx`
- [ ] 后端菜单 `component` 字段与目录路径一致
- [ ] 接口函数写在 `api/<module>.ts`，类型写在 `types/<module>.ts`
- [ ] 列表使用服务端分页，参数为 `page / limit / search / sort`
- [ ] 列表用 `useResponsiveList` + `ResponsiveDataList`，columns 标好 `mobile` 语义，未手写手机端卡片
- [ ] 工具条用 `ListToolbar`，筛选控件走 `filters`，未自行判断断点
- [ ] 写操作成功后 `invalidateQueries`
- [ ] 按钮按权限码用 `<Can>` 包裹
- [ ] 表单使用 react-hook-form + zod，错误按字段回填
- [ ] 颜色只用语义 token，无内联样式
- [ ] 深色模式下列表、表单、弹窗均验证过
- [ ] `pnpm typecheck` 与 `pnpm build` 通过

新增一个共享组件时：

- [ ] 确认两个站点都要用，否则放各自 app
- [ ] 在 `Shared/` 目录执行 `pnpm dlx shadcn@latest add <name>`
- [ ] 两个 app 分别 `pnpm dev` 验证样式未丢失（确认 `@source` 生效）
