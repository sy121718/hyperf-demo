import type { ReactNode } from "react";

/** 表格行内的操作按钮组，统一间距与对齐 */
export function RowActions({ children }: { children: ReactNode }) {
  return <div className="flex items-center justify-end gap-1">{children}</div>;
}
