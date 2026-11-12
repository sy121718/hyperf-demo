import { create } from "zustand";

/** 一个打开着的页面标签 */
export interface PageTab {
  /** 路由路径，作为标签的唯一标识 */
  path: string;
  title: string;
  /** 首页固定在第一位，不允许关闭 */
  closable: boolean;
}

interface PageTabsState {
  tabs: PageTab[];
  /** 打开或激活标签；已存在则只更新标题 */
  openTab: (tab: PageTab) => void;
  /** 关闭标签，返回关闭后应跳转的路径（关闭的是当前页时用） */
  closeTab: (path: string, activePath: string) => string | null;
  /** 关闭当前标签之外的其它标签 */
  closeOthers: (path: string) => void;
  reset: () => void;
}

/**
 * 页面标签栏状态。
 *
 * 只保存「打开了哪些页面」，当前激活的是哪个由路由决定——
 * 两处各存一份会不同步，浏览器前进/后退时尤其明显。
 */
export const usePageTabsStore = create<PageTabsState>((set, get) => ({
  tabs: [],

  openTab: (tab) =>
    set((state) => {
      const index = state.tabs.findIndex((item) => item.path === tab.path);
      if (index === -1) {
        return { tabs: [...state.tabs, tab] };
      }
      // 已存在：只更新标题（菜单改了名时能同步），不重排
      const existing = state.tabs[index];
      if (!existing || existing.title === tab.title) {
        return state;
      }
      const next = [...state.tabs];
      next[index] = { ...existing, title: tab.title };
      return { tabs: next };
    }),

  closeTab: (path, activePath) => {
    const { tabs } = get();
    const index = tabs.findIndex((item) => item.path === path);
    const target = tabs[index];
    if (!target || !target.closable) {
      return null;
    }

    const next = tabs.filter((item) => item.path !== path);
    set({ tabs: next });

    if (path !== activePath) {
      return null;
    }

    // 关掉的是当前页：优先落到右侧邻居，没有就落到左侧，再没有就回首页
    const neighbor = next[index] ?? next[index - 1];
    return neighbor ? neighbor.path : "/";
  },

  closeOthers: (path) =>
    set((state) => ({
      tabs: state.tabs.filter((item) => item.path === path || !item.closable),
    })),

  reset: () => set({ tabs: [] }),
}));
