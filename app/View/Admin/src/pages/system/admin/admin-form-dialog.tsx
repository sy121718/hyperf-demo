import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
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
import { FormField } from "@/components/shared/form-field";
import { SearchableMultiSelect } from "@/components/shared/searchable-multi-select";
import { createAdmin, getAdminInfo, updateAdmin } from "@/api/admin";
import { getRoleOptions } from "@/api/role";
import { queryKeys } from "@/lib/query-keys";

/**
 * 管理员表单。
 *
 * 新建必填用户名与密码；编辑时密码留空表示不修改（后端也这么处理）。
 * is_admin 不在这里改——超管只由初始化脚本产生，接口层也会拒绝。
 */
const schema = z
  .object({
    username: z.string().min(3, "用户名至少 3 个字符").max(50),
    password: z.string().max(100),
    name: z.string().max(50),
    email: z.string().max(100),
    phone: z.string().max(20),
    status: z.union([z.literal(0), z.literal(1)]),
    role_ids: z.array(z.number()),
  })
  .superRefine((values, ctx) => {
    // 只在有输入时校验格式，空串代表清空该字段
    if (values.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email)) {
      ctx.addIssue({ code: "custom", path: ["email"], message: "邮箱格式不正确" });
    }
    if (values.password && values.password.length < 6) {
      ctx.addIssue({ code: "custom", path: ["password"], message: "密码至少 6 位" });
    }
  });

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  username: "",
  password: "",
  name: "",
  email: "",
  phone: "",
  status: 1,
  role_ids: [],
};

export interface AdminFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  adminId?: number;
}

export function AdminFormDialog({ open, onOpenChange, adminId }: AdminFormDialogProps) {
  const queryClient = useQueryClient();
  const isEdit = adminId !== undefined;

  const { data: roleOptions } = useQuery({
    queryKey: queryKeys.role.options,
    queryFn: getRoleOptions,
    staleTime: 5 * 60_000,
  });

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
    void getAdminInfo(adminId).then((detail) => {
      if (cancelled) {
        return;
      }
      reset({
        username: detail.username,
        password: "",
        name: detail.name ?? "",
        email: detail.email ?? "",
        phone: detail.phone ?? "",
        status: detail.status,
        role_ids: detail.roles.map((role) => role.id),
      });
    });
    return () => {
      cancelled = true;
    };
  }, [open, isEdit, adminId, reset]);

  const mutation = useMutation({
    mutationFn: (values: FormValues) => {
      // 空串一律转 undefined，避免后端把空字符串当有效值写库
      const base = {
        username: values.username,
        name: values.name || undefined,
        email: values.email || undefined,
        phone: values.phone || undefined,
        status: values.status,
        role_ids: values.role_ids,
      };

      return isEdit
        ? updateAdmin(adminId, { ...base, password: values.password || undefined })
        : createAdmin({ ...base, password: values.password });
    },
    onSuccess: () => {
      toast.success(isEdit ? "管理员已更新" : "管理员已创建");
      void queryClient.invalidateQueries({ queryKey: queryKeys.admin.all });
      onOpenChange(false);
    },
  });

  const status = watch("status");
  const roleIds = watch("role_ids");

  const onSubmit = handleSubmit((values) => {
    if (!isEdit && values.password.length < 6) {
      toast.error("密码至少 6 位");
      return;
    }
    mutation.mutate(values);
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{isEdit ? "编辑管理员" : "新增管理员"}</DialogTitle>
          <DialogDescription>
            {isEdit ? "密码留空表示不修改。" : "新建的管理员默认为普通管理员。"}
          </DialogDescription>
        </DialogHeader>

        <form className="space-y-3" onSubmit={onSubmit}>
          <div className="grid grid-cols-2 gap-3">
            <FormField label="用户名" htmlFor="username" required error={errors.username?.message}>
              <Input id="username" autoComplete="off" {...register("username")} />
            </FormField>
            <FormField
              label={isEdit ? "新密码" : "密码"}
              htmlFor="password"
              required={!isEdit}
              error={errors.password?.message}
            >
              <Input
                id="password"
                type="password"
                autoComplete="new-password"
                placeholder={isEdit ? "留空不修改" : "至少 6 位"}
                {...register("password")}
              />
            </FormField>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <FormField label="姓名" htmlFor="name" error={errors.name?.message}>
              <Input id="name" {...register("name")} />
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
          </div>

          <div className="grid grid-cols-2 gap-3">
            <FormField label="邮箱" htmlFor="email" error={errors.email?.message}>
              <Input id="email" type="email" {...register("email")} />
            </FormField>
            <FormField label="手机号" htmlFor="phone" error={errors.phone?.message}>
              <Input id="phone" {...register("phone")} />
            </FormField>
          </div>

          <FormField label="角色" hint="不选则没有任何角色权限">
            <SearchableMultiSelect
              options={(roleOptions ?? []).map((option) => ({
                value: String(option.value),
                label: option.label,
                hint: option.code,
              }))}
              value={roleIds.map(String)}
              onChange={(next) => setValue("role_ids", next.map(Number))}
              placeholder="选择角色"
            />
          </FormField>

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
