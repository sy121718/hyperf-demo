import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation } from "@tanstack/react-query";
import { z } from "zod";
import { toast } from "sonner";

import { Button } from "@rbac/ui/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@rbac/ui/components/ui/dialog";
import { Input } from "@rbac/ui/components/ui/input";
import { FormField } from "@/components/shared/form-field";
import { resetAdminPassword } from "@/api/admin";

const schema = z.object({
  new_password: z.string().min(6, "密码至少 6 位").max(100),
});

type FormValues = z.infer<typeof schema>;

export interface ResetPasswordDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  adminId: number;
  username: string;
}

/** 重置指定管理员的密码，后端会校验操作者是否有权操作该账号 */
export function ResetPasswordDialog({
  open,
  onOpenChange,
  adminId,
  username,
}: ResetPasswordDialogProps) {
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { new_password: "" },
  });

  useEffect(() => {
    if (open) {
      reset({ new_password: "" });
    }
  }, [open, reset]);

  const mutation = useMutation({
    mutationFn: (values: FormValues) => resetAdminPassword(adminId, values.new_password),
    onSuccess: () => {
      toast.success("密码已重置");
      onOpenChange(false);
    },
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>重置密码</DialogTitle>
          <DialogDescription>
            为管理员「{username}」设置新密码，对方的在线会话不会被强制下线。
          </DialogDescription>
        </DialogHeader>

        <form
          className="space-y-3"
          onSubmit={handleSubmit((values) => mutation.mutate(values))}
        >
          <FormField label="新密码" htmlFor="new_password" required error={errors.new_password?.message}>
            <Input
              id="new_password"
              type="password"
              autoComplete="new-password"
              {...register("new_password")}
            />
          </FormField>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              取消
            </Button>
            <Button type="submit" disabled={mutation.isPending}>
              {mutation.isPending ? "提交中…" : "确认重置"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
