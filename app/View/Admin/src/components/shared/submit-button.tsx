import type { ComponentProps, ReactNode } from "react";
import { Loader2 } from "lucide-react";

import { Button } from "@rbac/ui/components/ui/button";

/**
 * 带 loading 态的提交按钮。
 *
 * 写操作在线上可能要等一两秒，按钮必须有「正在处理」的反馈，
 * 否则用户会重复点击造成重复提交。pending 时同时禁用并显示转圈。
 */
export interface SubmitButtonProps extends ComponentProps<typeof Button> {
  pending?: boolean;
  /** pending 时的文案，默认沿用 children */
  pendingText?: ReactNode;
}

export function SubmitButton({
  pending = false,
  pendingText,
  children,
  disabled,
  ...props
}: SubmitButtonProps) {
  return (
    <Button {...props} disabled={disabled || pending} aria-busy={pending}>
      {pending ? <Loader2 className="animate-spin" /> : null}
      {pending ? (pendingText ?? children) : children}
    </Button>
  );
}
