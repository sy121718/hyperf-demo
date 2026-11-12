import { useEffect, useState } from "react";
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
import { FormField } from "@/components/shared/form-field";
import { createConfig, getConfigInfo, updateConfig } from "@/api/config";
import { queryKeys } from "@/lib/query-keys";

/**
 * 配置表单。
 *
 * config_data 是 json 列，后端只校验「是数组」，这里用 textarea 收原始 JSON
 * 并在提交前本地解析一次，避免把语法错误发到后端再弹一次通用提示。
 */
const schema = z.object({
  group_key: z.string().min(1, "请输入分组键").max(50),
  group_name: z.string().min(1, "请输入分组名称").max(100),
  config_data: z.string(),
  remark: z.string().max(500),
  status: z.union([z.literal(0), z.literal(1)]),
});

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  group_key: "",
  group_name: "",
  config_data: "{}",
  remark: "",
  status: 1,
};

export interface ConfigFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  configId?: number;
}

export function ConfigFormDialog({ open, onOpenChange, configId }: ConfigFormDialogProps) {
  const queryClient = useQueryClient();
  const isEdit = configId !== undefined;
  const [jsonError, setJsonError] = useState<string | null>(null);

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
    setJsonError(null);
    if (!isEdit) {
      reset(DEFAULT_VALUES);
      return;
    }

    let cancelled = false;
    void getConfigInfo(configId).then((detail) => {
      if (cancelled) {
        return;
      }
      reset({
        group_key: detail.group_key,
        group_name: detail.group_name,
        config_data: JSON.stringify(detail.config_data ?? {}, null, 2),
        remark: detail.remark ?? "",
        status: detail.status,
      });
    });
    return () => {
      cancelled = true;
    };
  }, [open, isEdit, configId, reset]);

  const mutation = useMutation({
    mutationFn: (values: FormValues & { parsed: Record<string, unknown> }) =>
      isEdit
        ? updateConfig(configId, { ...values, config_data: values.parsed })
        : createConfig({ ...values, config_data: values.parsed }),
    onSuccess: () => {
      toast.success(isEdit ? "配置已更新" : "配置已创建");
      void queryClient.invalidateQueries({ queryKey: queryKeys.config.all });
      onOpenChange(false);
    },
  });

  const status = watch("status");

  const onSubmit = handleSubmit((values) => {
    let parsed: unknown;
    try {
      parsed = JSON.parse(values.config_data || "{}");
    } catch {
      setJsonError("JSON 格式不正确");
      return;
    }

    if (typeof parsed !== "object" || parsed === null || Array.isArray(parsed)) {
      setJsonError("配置内容必须是 JSON 对象（形如 { \"key\": \"value\" }）");
      return;
    }

    setJsonError(null);
    mutation.mutate({ ...values, parsed: parsed as Record<string, unknown> });
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{isEdit ? "编辑配置" : "新增配置"}</DialogTitle>
          <DialogDescription>
            分组键是读取配置时的唯一标识，整组内容存放在 config_data 里。
          </DialogDescription>
        </DialogHeader>

        <form className="space-y-3" onSubmit={onSubmit}>
          <div className="grid grid-cols-2 gap-3">
            <FormField label="分组键" htmlFor="group_key" required error={errors.group_key?.message}>
              <Input id="group_key" placeholder="如 site" {...register("group_key")} />
            </FormField>
            <FormField
              label="分组名称"
              htmlFor="group_name"
              required
              error={errors.group_name?.message}
            >
              <Input id="group_name" placeholder="如 站点设置" {...register("group_name")} />
            </FormField>
          </div>

          <FormField label="配置内容" htmlFor="config_data" hint="JSON 对象，键值对形式">
            <Textarea
              id="config_data"
              rows={8}
              className="font-mono text-xs"
              {...register("config_data")}
            />
            {jsonError ? <p className="text-xs text-destructive">{jsonError}</p> : null}
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
                  <SelectItem value="0">禁用</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="备注" htmlFor="remark" error={errors.remark?.message}>
              <Input id="remark" {...register("remark")} />
            </FormField>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              取消
            </Button>
            <Button type="submit" disabled={mutation.isPending}>
              {mutation.isPending ? "保存中…" : "保存"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
