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
import { createPermission, getPermissionInfo, getPermissionTree, updatePermission } from "@/api/permission";
import { queryKeys } from "@/lib/query-keys";
import type { PermissionTreeNode } from "@/types/permission";

const schema = z.object({
  perm_code: z.string().min(1, "请输入权限码").max(100),
  perm_name: z.string().min(1, "请输入权限名称").max(100),
  parent_id: z.number().int().min(0),
  api_path: z.string().max(255),
  http_method: z.string().max(10),
  controller_method: z.string().max(255),
  is_public: z.union([z.literal(0), z.literal(1)]),
  rate_limit: z.number().int().min(0),
  route_priority: z.number().int().min(0),
  remark: z.string().max(500),
  status: z.union([z.literal(0), z.literal(1)]),
});

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  perm_code: "",
  perm_name: "",
  parent_id: 0,
  api_path: "",
  http_method: "GET",
  controller_method: "",
  is_public: 0,
  rate_limit: 0,
  route_priority: 0,
  remark: "",
  status: 1,
};

const HTTP_METHODS = ["GET", "POST", "PUT", "DELETE", "PATCH"] as const;

function flattenTree(
  nodes: PermissionTreeNode[],
  depth = 0,
): Array<{ value: string; label: string }> {
  return nodes.flatMap((node) => [
    { value: String(node.id), label: `${"　".repeat(depth)}${node.perm_name}` },
    ...flattenTree(node.children ?? [], depth + 1),
  ]);
}

export interface PermissionFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  permissionId?: number;
}

export function PermissionFormDialog({
  open,
  onOpenChange,
  permissionId,
}: PermissionFormDialogProps) {
  const queryClient = useQueryClient();
  const isEdit = permissionId !== undefined;

  const { data: tree } = useQuery({
    queryKey: queryKeys.permission.tree({}),
    queryFn: () => getPermissionTree({}),
    staleTime: 60_000,
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
    void getPermissionInfo(permissionId).then((detail) => {
      if (cancelled) {
        return;
      }
      reset({
        perm_code: detail.perm_code,
        perm_name: detail.perm_name,
        parent_id: detail.parent_id,
        api_path: detail.api_path ?? "",
        http_method: detail.http_method ?? "GET",
        controller_method: detail.controller_method ?? "",
        is_public: detail.is_public,
        rate_limit: detail.rate_limit,
        route_priority: detail.route_priority,
        remark: detail.remark ?? "",
        status: detail.status,
      });
    });
    return () => {
      cancelled = true;
    };
  }, [open, isEdit, permissionId, reset]);

  const mutation = useMutation({
    mutationFn: (values: FormValues) => {
      const payload = {
        ...values,
        api_path: values.api_path || undefined,
        controller_method: values.controller_method || undefined,
        remark: values.remark || undefined,
      };
      return isEdit ? updatePermission(permissionId, payload) : createPermission(payload);
    },
    onSuccess: () => {
      toast.success(isEdit ? "权限已更新" : "权限已创建");
      void queryClient.invalidateQueries({ queryKey: queryKeys.permission.all });
      onOpenChange(false);
    },
  });

  const parentId = watch("parent_id");
  const httpMethod = watch("http_method");
  const isPublic = watch("is_public");
  const status = watch("status");

  const parentOptions = [
    { value: "0", label: "顶级权限" },
    ...flattenTree(tree ?? []).filter((option) => String(permissionId) !== option.value),
  ];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{isEdit ? "编辑权限" : "新增权限"}</DialogTitle>
          <DialogDescription>
            接口路径与请求方法决定 PermissionMiddleware 的匹配规则，改动会直接影响鉴权。
          </DialogDescription>
        </DialogHeader>

        <form className="space-y-3" onSubmit={handleSubmit((values) => mutation.mutate(values))}>
          <div className="grid grid-cols-2 gap-3">
            <FormField label="权限码" htmlFor="perm_code" required error={errors.perm_code?.message}>
              <Input id="perm_code" placeholder="如 admin:create" {...register("perm_code")} />
            </FormField>
            <FormField label="权限名称" htmlFor="perm_name" required error={errors.perm_name?.message}>
              <Input id="perm_name" placeholder="如 创建管理员" {...register("perm_name")} />
            </FormField>
          </div>

          <FormField label="父级权限">
            <Select
              value={String(parentId)}
              onValueChange={(value) => setValue("parent_id", Number(value))}
            >
              <SelectTrigger className="w-full">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {parentOptions.map((option) => (
                  <SelectItem key={option.value} value={option.value}>
                    {option.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </FormField>

          <div className="grid grid-cols-[1fr_120px] gap-3">
            <FormField label="接口路径" htmlFor="api_path" hint="支持 {id} 占位，如 /api/admin/info/{id}">
              <Input id="api_path" {...register("api_path")} />
            </FormField>
            <FormField label="请求方法">
              <Select
                value={httpMethod}
                onValueChange={(value) => setValue("http_method", value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {HTTP_METHODS.map((method) => (
                    <SelectItem key={method} value={method}>
                      {method}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </FormField>
          </div>

          <FormField
            label="控制器方法"
            htmlFor="controller_method"
            error={errors.controller_method?.message}
          >
            <Input
              id="controller_method"
              placeholder="App\Controller\System\AdminController@create"
              {...register("controller_method")}
            />
          </FormField>

          <div className="grid grid-cols-3 gap-3">
            <FormField label="是否公开">
              <Select
                value={String(isPublic)}
                onValueChange={(value) => setValue("is_public", Number(value) === 1 ? 1 : 0)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="0">需鉴权</SelectItem>
                  <SelectItem value="1">公开</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="优先级">
              <Input
                type="number"
                {...register("route_priority", { valueAsNumber: true })}
              />
            </FormField>
            <FormField label="限流（次/分）">
              <Input type="number" {...register("rate_limit", { valueAsNumber: true })} />
            </FormField>
          </div>

          <FormField label="备注" htmlFor="remark" error={errors.remark?.message}>
            <Textarea id="remark" rows={2} {...register("remark")} />
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
