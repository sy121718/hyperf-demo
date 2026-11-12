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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@rbac/ui/components/ui/select";
import { SubmitButton } from "@/components/shared/submit-button";
import { FormField } from "@/components/shared/form-field";
import { createDict, getDictInfo, updateDict } from "@/api/dict";
import { queryKeys } from "@/lib/query-keys";

const schema = z.object({
  dict_name: z.string().min(1, "请输入字典名称").max(100),
  dict_code: z.string().min(1, "请输入字典编码").max(50),
  dict_type: z.string().max(50),
  status: z.union([z.literal(0), z.literal(1)]),
});

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  dict_name: "",
  dict_code: "",
  dict_type: "default",
  status: 1,
};

export interface DictFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  dictId?: number;
}

export function DictFormDialog({ open, onOpenChange, dictId }: DictFormDialogProps) {
  const queryClient = useQueryClient();
  const isEdit = dictId !== undefined;

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
    void getDictInfo(dictId).then((detail) => {
      if (cancelled) {
        return;
      }
      reset({
        dict_name: detail.dict_name,
        dict_code: detail.dict_code,
        dict_type: detail.dict_type,
        status: detail.status,
      });
    });
    return () => {
      cancelled = true;
    };
  }, [open, isEdit, dictId, reset]);

  const mutation = useMutation({
    mutationFn: (values: FormValues) =>
      isEdit
        ? updateDict(dictId, { ...values, dict_type: values.dict_type || undefined })
        : createDict({ ...values, dict_type: values.dict_type || undefined }),
    onSuccess: () => {
      toast.success(isEdit ? "字典已更新" : "字典已创建");
      void queryClient.invalidateQueries({ queryKey: queryKeys.dict.all });
      onOpenChange(false);
    },
  });

  const status = watch("status");

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{isEdit ? "编辑字典" : "新增字典"}</DialogTitle>
          <DialogDescription>
            字典项当前只能在后端维护，此页只管理字典主表。
          </DialogDescription>
        </DialogHeader>

        <form className="space-y-3" onSubmit={handleSubmit((values) => mutation.mutate(values))}>
          <FormField label="字典名称" htmlFor="dict_name" required error={errors.dict_name?.message}>
            <Input id="dict_name" placeholder="如 公告类型" {...register("dict_name")} />
          </FormField>
          <FormField
            label="字典编码"
            htmlFor="dict_code"
            required
            hint="业务代码按编码取值，创建后不建议修改"
            error={errors.dict_code?.message}
          >
            <Input id="dict_code" placeholder="如 notice_type" {...register("dict_code")} />
          </FormField>
          <div className="grid grid-cols-2 gap-3">
            <FormField label="字典类型" htmlFor="dict_type" error={errors.dict_type?.message}>
              <Input id="dict_type" placeholder="default" {...register("dict_type")} />
            </FormField>
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
