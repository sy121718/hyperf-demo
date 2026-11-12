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
import { SubmitButton } from "@/components/shared/submit-button";
import { FormField } from "@/components/shared/form-field";
import { SearchableMultiSelect } from "@/components/shared/searchable-multi-select";
import { Input } from "@rbac/ui/components/ui/input";
import { Textarea } from "@rbac/ui/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@rbac/ui/components/ui/select";
import { createRole, getRoleInfo, updateRole } from "@/api/role";
import { queryKeys } from "@/lib/query-keys";

const schema = z.object({
  role_code: z.string().min(1, "请输入角色标识").max(50),
  role_name: z.string().min(1, "请输入角色名称").max(50),
  role_type: z.string().max(20).optional(),
  sort_order: z.number().int().min(0).optional(),
  status: z.union([z.literal(0), z.literal(1)]),
  remark: z.string().max(200).optional(),
  permission_codes: z.array(z.string()),
});

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  role_code: "",
  role_name: "",
  role_type: "1",
  sort_order: 0,
  status: 1,
  remark: "",
  permission_codes: [],
};

export interface RoleFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  /** 传 id 为编辑，不传为新增 */
  roleId?: number;
  /** 权限码可选项，由页面从权限字典拉取后传入 */
  permissionOptions: Array<{ value: string; label: string }>;
}

export function RoleFormDialog({
  open,
  onOpenChange,
  roleId,
  permissionOptions,
}: RoleFormDialogProps) {
  const queryClient = useQueryClient();
  const isEdit = roleId !== undefined;

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

  // 编辑时拉详情回填；新增时重置成默认值
  useEffect(() => {
    if (!open) {
      return;
    }
    if (!isEdit) {
      reset(DEFAULT_VALUES);
      return;
    }

    let cancelled = false;
    void getRoleInfo(roleId).then((detail) => {
      if (cancelled) {
        return;
      }
      reset({
        role_code: detail.role_code,
        role_name: detail.role_name,
        role_type: detail.role_type ?? "1",
        sort_order: detail.sort_order ?? 0,
        status: detail.status,
        remark: detail.remark ?? "",
        permission_codes: detail.permission_codes ?? [],
      });
    });
    return () => {
      cancelled = true;
    };
  }, [open, isEdit, roleId, reset]);

  const mutation = useMutation({
    mutationFn: (values: FormValues) =>
      isEdit ? updateRole(roleId, values) : createRole(values),
    onSuccess: () => {
      toast.success(isEdit ? "角色已更新" : "角色已创建");
      void queryClient.invalidateQueries({ queryKey: queryKeys.role.all });
      onOpenChange(false);
    },
  });

  const onSubmit = handleSubmit((values) => mutation.mutate(values));

  const status = watch("status");
  const permissionCodes = watch("permission_codes");

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{isEdit ? "编辑角色" : "新增角色"}</DialogTitle>
          <DialogDescription>
            角色标识用于后端鉴权关联，创建后不建议修改。
          </DialogDescription>
        </DialogHeader>

        <form className="space-y-3" onSubmit={onSubmit}>
          <div className="grid grid-cols-2 gap-3">
            <FormField label="角色标识" htmlFor="role_code" required error={errors.role_code?.message}>
              <Input id="role_code" placeholder="如 editor" {...register("role_code")} />
            </FormField>
            <FormField label="角色名称" htmlFor="role_name" required error={errors.role_name?.message}>
              <Input id="role_name" placeholder="如 编辑" {...register("role_name")} />
            </FormField>
          </div>

          <div className="grid grid-cols-3 gap-3">
            <FormField label="排序" htmlFor="sort_order" error={errors.sort_order?.message}>
              <Input
                id="sort_order"
                type="number"
                {...register("sort_order", { valueAsNumber: true })}
              />
            </FormField>
            <FormField label="状态" error={errors.status?.message}>
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
            <FormField label="类型" htmlFor="role_type" error={errors.role_type?.message}>
              <Input id="role_type" placeholder="1" {...register("role_type")} />
            </FormField>
          </div>

          <FormField label="权限码" hint="勾选的权限码会覆盖角色原有权限">
            <SearchableMultiSelect
              options={permissionOptions}
              value={permissionCodes}
              onChange={(next) => setValue("permission_codes", next)}
              placeholder="搜索权限码或名称"
            />
          </FormField>

          <FormField label="备注" htmlFor="remark" error={errors.remark?.message}>
            <Textarea id="remark" rows={2} {...register("remark")} />
          </FormField>

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
