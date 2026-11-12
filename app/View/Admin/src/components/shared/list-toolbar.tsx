import type { ReactNode } from "react";
import { SlidersHorizontal } from "lucide-react";

import { Button } from "@rbac/ui/components/ui/button";
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@rbac/ui/components/ui/sheet";
import { SearchInput } from "@/components/shared/search-input";
import { useIsMobile } from "@/lib/use-responsive-list";

/**
 * 列表工具条。
 *
 * 桌面端平铺显示全部筛选项；手机端只保留搜索框 + 新增按钮，
 * 其余筛选条件收进底部抽屉，避免横向挤成一团。
 */
export interface ListToolbarProps {
  search: string;
  onSearchChange: (value: string) => void;
  searchPlaceholder?: string;
  /** 新增按钮，页面按权限自行决定是否传 */
  createButton?: ReactNode;
  /** 筛选控件（下拉、日期等）。桌面端直接铺开，手机端放进抽屉 */
  filters?: ReactNode;
  /** 抽屉里是否显示重置 */
  onReset?: () => void;
  /** 顶部右侧的汇总信息，如「共 12 条」 */
  summary?: ReactNode;
  /** 作用域切换（全部/我的）等，桌面端与筛选并排 */
  leading?: ReactNode;
  /** 手机端抽屉的筛选是否生效，用于提示当前有筛选 */
  hasActiveFilters?: boolean;
}

export function ListToolbar({
  search,
  onSearchChange,
  searchPlaceholder = "搜索",
  createButton,
  filters,
  onReset,
  summary,
  leading,
  hasActiveFilters = false,
}: ListToolbarProps) {
  const isMobile = useIsMobile();

  if (isMobile) {
    return (
      <div className="space-y-3">
        <div className="flex items-center gap-2">
          {leading}
          <SearchInput
            value={search}
            onChange={onSearchChange}
            placeholder={searchPlaceholder}
            className="min-w-0 flex-1"
          />
        </div>

        <div className="flex items-center gap-2">
          {createButton}
          {filters ? (
            <Sheet>
              <SheetTrigger asChild>
                <Button variant="outline" className="gap-1.5">
                  <SlidersHorizontal />
                  筛选
                  {hasActiveFilters ? (
                    <span className="size-1.5 rounded-full bg-primary" />
                  ) : null}
                </Button>
              </SheetTrigger>
              <SheetContent side="bottom" className="max-h-[85vh]">
                <SheetHeader>
                  <SheetTitle>筛选条件</SheetTitle>
                  <SheetDescription>选择后立即生效，关闭抽屉即可查看结果。</SheetDescription>
                </SheetHeader>
                {/* 窄屏下筛选控件一列排开，Select 的 w-full 才能生效 */}
                <div className="grid gap-4 overflow-y-auto px-4 pb-4 [&_[data-slot=select-trigger]]:w-full">
                  {filters}
                </div>
                <SheetFooter>
                  {onReset ? (
                    <Button variant="outline" className="w-full" onClick={onReset}>
                      重置全部条件
                    </Button>
                  ) : null}
                </SheetFooter>
              </SheetContent>
            </Sheet>
          ) : null}
          <div className="ml-auto">{summary}</div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex flex-wrap items-center gap-2">
      {leading}
      <SearchInput value={search} onChange={onSearchChange} placeholder={searchPlaceholder} />
      {filters}
      {onReset ? (
        <Button variant="outline" onClick={onReset}>
          重置
        </Button>
      ) : null}
      <div className="ml-auto flex items-center gap-2">
        {summary}
        {createButton}
      </div>
    </div>
  );
}

/** 页面新增按钮的统一外观，供各页面复用 */
export function ToolbarCreateButton({
  children,
  onClick,
}: {
  children: ReactNode;
  onClick: () => void;
}) {
  return <Button onClick={onClick}>{children}</Button>;
}
