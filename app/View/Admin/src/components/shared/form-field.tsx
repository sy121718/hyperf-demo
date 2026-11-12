import type { ReactNode } from "react";

import { Label } from "@rbac/ui/components/ui/label";

/**
 * 表单字段壳：标签 + 控件 + 错误提示。
 *
 * 用原生 label + htmlFor 关联，不引入 shadcn 的 Form 组件——
 * 后者依赖 Radix Form，对只用 react-hook-form 的页面是多余一层。
 */
export interface FormFieldProps {
  label: string;
  htmlFor?: string;
  required?: boolean;
  error?: string;
  hint?: ReactNode;
  children: ReactNode;
}

export function FormField({
  label,
  htmlFor,
  required = false,
  error,
  hint,
  children,
}: FormFieldProps) {
  return (
    <div className="space-y-1.5">
      <Label htmlFor={htmlFor}>
        {label}
        {required ? <span className="text-destructive">*</span> : null}
      </Label>
      {children}
      {hint && !error ? <p className="text-xs text-muted-foreground">{hint}</p> : null}
      {error ? <p className="text-xs text-destructive">{error}</p> : null}
    </div>
  );
}
