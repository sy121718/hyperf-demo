import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQueryClient } from "@tanstack/react-query";
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
import { Textarea } from "@rbac/ui/components/ui/textarea";
import { FormField } from "@/components/shared/form-field";
import { banIp } from "@/api/ip-blacklist";
import { queryKeys } from "@/lib/query-keys";

/** IPv4 / IPv6 文本框，真正的格式校验交给后端 ip 规则 */
const schema = z.object({
  ip_address: z.string().min(1, "请输入 IP 地址").max(45),
  error_type: z.string().max(50),
  ban_reason: z.string().max(500),
  ban_duration: z.number().int().min(1, "至少 1 分钟").max(525_600),
  remark: z.string().max(500),
});

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  ip_address: "",
  error_type: "",
  ban_reason: "",
  ban_duration: 30,
  remark: "",
};

export function BanDialog({
  open,
  onOpenChange,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const queryClient = useQueryClient();

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: DEFAULT_VALUES,
  });

  useEffect(() => {
    if (open) {
      reset(DEFAULT_VALUES);
    }
  }, [open, reset]);

  const mutation = useMutation({
    mutationFn: (values: FormValues) =>
      banIp({
        ip_address: values.ip_address.trim(),
        error_type: values.error_type || undefined,
        ban_reason: values.ban_reason || undefined,
        ban_duration: values.ban_duration,
        remark: values.remark || undefined,
      }),
    onSuccess: () => {
      toast.success("已拉黑该 IP");
      void queryClient.invalidateQueries({ queryKey: queryKeys.ipBlacklist.all });
      onOpenChange(false);
    },
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>拉黑 IP</DialogTitle>
          <DialogDescription>
            同一 IP 重复拉黑会在原到期时间上顺延，并累加拉黑次数。
          </DialogDescription>
        </DialogHeader>

        <form className="space-y-3" onSubmit={handleSubmit((values) => mutation.mutate(values))}>
          <FormField label="IP 地址" htmlFor="ip_address" required error={errors.ip_address?.message}>
            <Input id="ip_address" placeholder="192.168.1.100" {...register("ip_address")} />
          </FormField>

          <div className="grid grid-cols-2 gap-3">
            <FormField label="时长（分钟）" hint="默认 30，最长 525600（一年）">
              <Input type="number" {...register("ban_duration", { valueAsNumber: true })} />
            </FormField>
            <FormField label="错误类型" htmlFor="error_type" error={errors.error_type?.message}>
              <Input id="error_type" placeholder="如 login_failed" {...register("error_type")} />
            </FormField>
          </div>

          <FormField label="拉黑原因" htmlFor="ban_reason" error={errors.ban_reason?.message}>
            <Textarea id="ban_reason" rows={2} {...register("ban_reason")} />
          </FormField>

          <FormField label="备注" htmlFor="remark" error={errors.remark?.message}>
            <Input id="remark" {...register("remark")} />
          </FormField>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              取消
            </Button>
            <Button type="submit" variant="destructive" disabled={mutation.isPending}>
              {mutation.isPending ? "提交中…" : "确认拉黑"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
