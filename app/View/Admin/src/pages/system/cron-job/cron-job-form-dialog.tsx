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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@rbac/ui/components/ui/select";
import { SubmitButton } from "@/components/shared/submit-button";
import { FormField } from "@/components/shared/form-field";
import { createCronJob, getCronJobInfo, updateCronJob } from "@/api/cron-job";
import { queryKeys } from "@/lib/query-keys";

const schema = z.object({
  job_name: z.string().min(1, "请输入任务名称").max(100),
  cron_expression: z.string().min(1, "请输入 cron 表达式").max(100),
  command: z.string().min(1, "请输入执行命令").max(500),
  job_category: z.string().max(50),
  description: z.string(),
  status: z.union([z.literal(0), z.literal(1)]),
  sort_order: z.number().int().min(0),
});

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  job_name: "",
  cron_expression: "*/5 * * * *",
  command: "",
  job_category: "default",
  description: "",
  status: 1,
  sort_order: 0,
};

export interface CronJobFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  jobId?: number;
}

export function CronJobFormDialog({ open, onOpenChange, jobId }: CronJobFormDialogProps) {
  const queryClient = useQueryClient();
  const isEdit = jobId !== undefined;

  const {
    register,
    handleSubmit,
    reset,
    watch,
    setValue,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: DEFAULT_VALUES,
  });

  useEffect(() => {
    if (!open) {
      return;
    }
    if (!isEdit) {
      reset(DEFAULT_VALUES);
      return;
    }

    let cancelled = false;
    void getCronJobInfo(jobId).then((detail) => {
      if (cancelled) {
        return;
      }
      reset({
        job_name: detail.job_name,
        cron_expression: detail.cron_expression,
        command: detail.command,
        job_category: detail.job_category,
        description: detail.description ?? "",
        status: detail.status,
        sort_order: detail.sort_order ?? 0,
      });
    });
    return () => {
      cancelled = true;
    };
  }, [open, isEdit, jobId, reset]);

  const mutation = useMutation({
    mutationFn: (values: FormValues) => {
      const payload = {
        ...values,
        job_category: values.job_category || "default",
        description: values.description || undefined,
      };
      return isEdit ? updateCronJob(jobId, payload) : createCronJob(payload);
    },
    onSuccess: () => {
      toast.success(isEdit ? "任务已更新" : "任务已创建");
      void queryClient.invalidateQueries({ queryKey: queryKeys.cronJob.all });
      onOpenChange(false);
    },
  });

  const status = watch("status");

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{isEdit ? "编辑任务" : "新增任务"}</DialogTitle>
          <DialogDescription>
            命令由后端用 Symfony Process 直接执行，请只填写受信任的可执行命令。
          </DialogDescription>
        </DialogHeader>

        <form className="space-y-3" onSubmit={handleSubmit((values) => mutation.mutate(values))}>
          <div className="grid grid-cols-2 gap-3">
            <FormField label="任务名称" htmlFor="job_name" required error={errors.job_name?.message}>
              <Input id="job_name" {...register("job_name")} />
            </FormField>
            <FormField label="分类" htmlFor="job_category" error={errors.job_category?.message}>
              <Input id="job_category" placeholder="default" {...register("job_category")} />
            </FormField>
          </div>

          <FormField
            label="cron 表达式"
            htmlFor="cron_expression"
            required
            hint="五段式，如 */5 * * * * 表示每 5 分钟"
            error={errors.cron_expression?.message}
          >
            <Input id="cron_expression" className="font-mono" {...register("cron_expression")} />
          </FormField>

          <FormField label="执行命令" htmlFor="command" required error={errors.command?.message}>
            <Textarea id="command" rows={2} className="font-mono text-xs" {...register("command")} />
          </FormField>

          <FormField label="说明" htmlFor="description" error={errors.description?.message}>
            <Textarea id="description" rows={2} {...register("description")} />
          </FormField>

          <div className="grid grid-cols-2 gap-3">
            <FormField label="状态">
              <Select
                value={String(status)}
                onValueChange={(value) => setValue("status", Number(value) === 1 ? 1 : 0)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1">启用</SelectItem>
                  <SelectItem value="0">停用</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="排序">
              <Input type="number" {...register("sort_order", { valueAsNumber: true })} />
            </FormField>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              取消
            </Button>
            <SubmitButton type="submit" pending={mutation.isPending}>
              {"保存"}
            </SubmitButton>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
