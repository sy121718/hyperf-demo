import { useState, type ReactNode } from "react";

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@rbac/ui/components/ui/alert-dialog";

/**
 * 二次确认弹窗，替代 window.confirm。
 *
 * 危险操作（删除、封禁、踢下线）一律走这里，文案由调用方给出。
 * 确认按钮会在异步操作期间禁用，避免重复提交。
 * 受控/非受控都可用：传 trigger 走非受控，传 open/onOpenChange 走受控。
 */
export interface ConfirmDialogProps {
  title: string;
  description?: ReactNode;
  confirmText?: string;
  cancelText?: string;
  /** 危险操作用红色按钮 */
  destructive?: boolean;
  onConfirm: () => void | Promise<void>;
  /** 非受控用法：触发按钮 */
  trigger?: ReactNode;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
}

export function ConfirmDialog({
  title,
  description,
  confirmText = "确认",
  cancelText = "取消",
  destructive = false,
  onConfirm,
  trigger,
  open,
  onOpenChange,
}: ConfirmDialogProps) {
  const [pending, setPending] = useState(false);

  const handleConfirm = async () => {
    setPending(true);
    try {
      await onConfirm();
      onOpenChange?.(false);
    } finally {
      setPending(false);
    }
  };

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      {trigger ? <AlertDialogTrigger asChild>{trigger}</AlertDialogTrigger> : null}
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{title}</AlertDialogTitle>
          {description ? (
            <AlertDialogDescription>{description}</AlertDialogDescription>
          ) : null}
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel disabled={pending}>{cancelText}</AlertDialogCancel>
          <AlertDialogAction
            disabled={pending}
            variant={destructive ? "destructive" : "default"}
            onClick={(event) => {
              // 阻止 Radix 自动关闭，等异步操作完成后再关
              event.preventDefault();
              void handleConfirm();
            }}
          >
            {pending ? "处理中…" : confirmText}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
