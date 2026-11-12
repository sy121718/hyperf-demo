import { useEffect, useRef, useState } from "react";
import { useIsFetching, useIsMutating } from "@tanstack/react-query";
import { cn } from "@rbac/ui/lib/utils";

/**
 * 全局请求进度条。
 *
 * 线上接口慢时，点击后界面毫无反应会让用户以为没生效。这里监听
 * react-query 的全部查询与写操作，有请求在飞就在顶部亮一条进度条。
 *
 * 两个细节：
 * - 延迟 120ms 才出现，本地/内网快请求不会造成闪烁；
 * - 一旦出现就至少保留 320ms，否则快请求会让进度条闪一下就消失，
 *   反而让人分不清是加载还是渲染抖动。
 */
const SHOW_DELAY = 120;
const MIN_VISIBLE = 320;

export function TopProgress() {
  const fetching = useIsFetching();
  const mutating = useIsMutating();
  const active = fetching + mutating > 0;

  const [visible, setVisible] = useState(false);
  const shownAt = useRef(0);

  useEffect(() => {
    if (active) {
      if (visible) {
        return;
      }
      const timer = window.setTimeout(() => {
        shownAt.current = Date.now();
        setVisible(true);
      }, SHOW_DELAY);
      return () => window.clearTimeout(timer);
    }

    if (!visible) {
      return;
    }
    // 已显示时补足最短可见时长，避免一闪而过
    const elapsed = Date.now() - shownAt.current;
    const timer = window.setTimeout(
      () => setVisible(false),
      Math.max(0, MIN_VISIBLE - elapsed),
    );
    return () => window.clearTimeout(timer);
  }, [active, visible]);

  return (
    <div
      className={cn(
        "pointer-events-none fixed inset-x-0 top-0 z-100 h-0.5 overflow-hidden transition-opacity duration-200",
        visible ? "opacity-100" : "opacity-0",
      )}
      aria-hidden={!visible}
    >
      <div
        className={cn(
          "h-full w-full origin-left bg-primary",
          visible ? "progress-indeterminate" : "scale-x-0",
        )}
      />
    </div>
  );
}
