/** react-query 的 key 统一在这里拼，避免各处手写字符串导致失效缓存失效不一致 */
import type { ListQuery } from "@/lib/list";

export const queryKeys = {
  admin: {
    all: ["admin"] as const,
    list: (params: ListQuery) => ["admin", "list", params] as const,
    detail: (id: number) => ["admin", "detail", id] as const,
    roles: (id: number) => ["admin", "roles", id] as const,
    permissions: (id: number) => ["admin", "permissions", id] as const,
    menu: ["admin", "menu"] as const,
  },
  role: {
    all: ["role"] as const,
    list: (params: ListQuery) => ["role", "list", params] as const,
    detail: (id: number) => ["role", "detail", id] as const,
    options: ["role", "options"] as const,
  },
  menu: {
    all: ["menu"] as const,
    tree: (params: ListQuery) => ["menu", "tree", params] as const,
    detail: (id: number) => ["menu", "detail", id] as const,
  },
  permission: {
    all: ["permission"] as const,
    tree: (params: ListQuery) => ["permission", "tree", params] as const,
    detail: (id: number) => ["permission", "detail", id] as const,
    options: ["permission", "options"] as const,
  },
  config: {
    all: ["config"] as const,
    list: (params: ListQuery) => ["config", "list", params] as const,
    detail: (id: number) => ["config", "detail", id] as const,
  },
  dict: {
    all: ["dict"] as const,
    tree: (params: ListQuery) => ["dict", "tree", params] as const,
    detail: (id: number) => ["dict", "detail", id] as const,
    items: (id: number) => ["dict", "items", id] as const,
  },
  attachment: {
    all: ["attachment"] as const,
    list: (scope: string, params: ListQuery) => ["attachment", scope, params] as const,
  },
  cronJob: {
    all: ["cron-job"] as const,
    list: (params: ListQuery) => ["cron-job", "list", params] as const,
  },
  ipBlacklist: {
    all: ["ip-blacklist"] as const,
    list: (params: ListQuery) => ["ip-blacklist", "list", params] as const,
  },
  notice: {
    all: ["notice"] as const,
    list: (scope: string, params: ListQuery) => ["notice", scope, params] as const,
    statistics: (params: ListQuery) => ["notice", "statistics", params] as const,
  },
  email: {
    all: ["email"] as const,
    records: (scope: string, params: ListQuery) => ["email", "records", scope, params] as const,
    templates: (params: ListQuery) => ["email", "templates", params] as const,
  },
  session: {
    all: ["session"] as const,
    list: (scope: string, params: ListQuery) => ["session", scope, params] as const,
    detail: (id: number) => ["session", "detail", id] as const,
  },
  log: {
    all: ["log"] as const,
    login: (scope: string, params: ListQuery) => ["log", "login", scope, params] as const,
    operation: (scope: string, params: ListQuery) => ["log", "operation", scope, params] as const,
  },
  cache: {
    modules: ["cache", "modules"] as const,
  },
};
