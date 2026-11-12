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
import { SubmitButton } from "@/components/shared/submit-button";
import { FormField } from "@/components/shared/form-field";
import { SearchableMultiSelect } from "@/components/shared/searchable-multi-select";
import { createMenu, getMenuInfo, getMenuTree, updateMenu } from "@/api/menu";
import { getPermissionOptions } from "@/api/permission";
import { queryKeys } from "@/lib/query-keys";
import { MENU_TYPE, MENU_TYPE_LABELS, type MenuTreeNode } from "@/types/menu";

/**
 * 菜单表单。
 *
 * 类型决定其余字段的可见性：目录只有分组信息，菜单/页面才需要路由与组件，
 * 而目录不能带权限码（后端 Menu\Create 会直接拒绝）。
 */
const schema = z.object({
  menu_code: z.string().min(1, "请输入菜单标识").max(50),
  menu_name: z.string().min(1, "请输入菜单名称").max(50),
  parent_id: z.number().int().min(0),
  type: z.number().int(),
  title: z.string().max(50),
  path: z.string().max(255),
  component: z.string().max(255),
  icon: z.string().max(50),
  status: z.union([z.literal(0), z.literal(1)]),
  is_hidden: z.union([z.literal(0), z.literal(1)]),
  is_public: z.union([z.literal(0), z.literal(1)]),
  sort_order: z.number().int().min(0),
  permission_codes: z.array(z.string()),
});

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  menu_code: "",
  menu_name: "",
  parent_id: 0,
  type: MENU_TYPE.MENU,
  title: "",
  path: "",
  component: "",
  icon: "",
  status: 1,
  is_hidden: 0,
  is_public: 0,
  sort_order: 0,
  permission_codes: [],
};

export interface MenuFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  menuId?: number;
  /** 新增子菜单时预置的父级 */
  defaultParentId?: number;
}

/** 把树摊平成下拉项，目录缩进显示层级 */
function flattenTree(
  nodes: MenuTreeNode[],
  depth = 0,
): Array<{ value: string; label: string }> {
  return nodes.flatMap((node) => [
    {
      value: String(node.id),
      label: `${"　".repeat(depth)}${node.title ?? node.menu_name}`,
    },
    ...flattenTree(node.children ?? [], depth + 1),
  ]);
}

export function MenuFormDialog({ open, onOpenChange, menuId, defaultParentId }: MenuFormDialogProps) {
  const queryClient = useQueryClient();
  const isEdit = menuId !== undefined;

  const { data: tree } = useQuery({
    queryKey: queryKeys.menu.tree({}),
    queryFn: () => getMenuTree({}),
    staleTime: 60_000,
  });

  const { data: permissionOptions } = useQuery({
    queryKey: queryKeys.permission.options,
    queryFn: getPermissionOptions,
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
      reset({ ...DEFAULT_VALUES, parent_id: defaultParentId ?? 0 });
      return;
    }

    let cancelled = false;
    void getMenuInfo(menuId).then((detail) => {
      if (cancelled) {
        return;
      }
      reset({
        menu_code: detail.menu_code,
        menu_name: detail.menu_name,
        parent_id: detail.parent_id,
        type: detail.type,
        title: detail.title ?? "",
        path: detail.path ?? "",
        component: detail.component ?? "",
        icon: detail.icon ?? "",
        status: detail.status,
        is_hidden: detail.is_hidden,
        is_public: detail.is_public,
        sort_order: detail.sort_order,
        permission_codes: detail.permission_codes ?? [],
      });
    });
    return () => {
      cancelled = true;
    };
  }, [open, isEdit, menuId, defaultParentId, reset]);

  const mutation = useMutation({
    mutationFn: (values: FormValues) => {
      const payload = {
        ...values,
        title: values.title || undefined,
        path: values.path || undefined,
        component: values.component || undefined,
        icon: values.icon || undefined,
        // 目录类型不能带权限码，后端会直接拒绝
        permission_codes:
          values.type === MENU_TYPE.DIRECTORY ? [] : values.permission_codes,
      };
      return isEdit ? updateMenu(menuId, payload) : createMenu(payload);
    },
    onSuccess: () => {
      toast.success(isEdit ? "菜单已更新" : "菜单已创建");
      void queryClient.invalidateQueries({ queryKey: queryKeys.menu.all });
      void queryClient.invalidateQueries({ queryKey: ["admin", "menu"] });
      onOpenChange(false);
    },
  });

  const type = watch("type");
  const status = watch("status");
  const parentId = watch("parent_id");
  const permissionCodes = watch("permission_codes");
  const isDirectory = type === MENU_TYPE.DIRECTORY;

  const parentOptions = [
    { value: "0", label: "顶级菜单" },
    ...flattenTree(tree ?? []).filter((option) => String(menuId) !== option.value),
  ];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{isEdit ? "编辑菜单" : "新增菜单"}</DialogTitle>
          <DialogDescription>
            组件路径相对 src/pages，例如 system/admin/index。
          </DialogDescription>
        </DialogHeader>

        <form className="space-y-3" onSubmit={handleSubmit((values) => mutation.mutate(values))}>
          <div className="grid grid-cols-2 gap-3">
            <FormField label="菜单标识" htmlFor="menu_code" required error={errors.menu_code?.message}>
              <Input id="menu_code" placeholder="如 admin_list" {...register("menu_code")} />
            </FormField>
            <FormField label="菜单名称" htmlFor="menu_name" required error={errors.menu_name?.message}>
              <Input id="menu_name" placeholder="如 管理员列表" {...register("menu_name")} />
            </FormField>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <FormField label="父级菜单">
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
            <FormField label="类型">
              <Select value={String(type)} onValueChange={(value) => setValue("type", Number(value))}>
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {[MENU_TYPE.DIRECTORY, MENU_TYPE.MENU, MENU_TYPE.PAGE].map((item) => (
                    <SelectItem key={item} value={String(item)}>
                      {MENU_TYPE_LABELS[item]}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </FormField>
          </div>

          {!isDirectory ? (
            <>
              <div className="grid grid-cols-2 gap-3">
                <FormField label="路由路径" htmlFor="path" error={errors.path?.message}>
                  <Input id="path" placeholder="/system/admin" {...register("path")} />
                </FormField>
                <FormField label="组件路径" htmlFor="component" error={errors.component?.message}>
                  <Input
                    id="component"
                    placeholder="system/admin/index"
                    {...register("component")}
                  />
                </FormField>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <FormField label="显示标题" htmlFor="title" error={errors.title?.message}>
                  <Input id="title" placeholder="留空则用菜单名称" {...register("title")} />
                </FormField>
                <FormField label="图标" htmlFor="icon" hint="lucide 图标名，如 user-round">
                  <Input id="icon" {...register("icon")} />
                </FormField>
              </div>

              <FormField label="权限码" hint="管理员需要拥有该权限码才能看到此菜单">
                <SearchableMultiSelect
                  options={(permissionOptions ?? []).map((item) => ({
                    value: item.perm_code,
                    label: item.perm_name,
                    hint: item.perm_code,
                  }))}
                  value={permissionCodes}
                  onChange={(next) => setValue("permission_codes", next)}
                  placeholder="选择权限码"
                />
              </FormField>
            </>
          ) : null}

          <div className="grid grid-cols-3 gap-3">
            <FormField label="排序">
              <Input type="number" {...register("sort_order", { valueAsNumber: true })} />
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
            <FormField label="隐藏">
              <Select
                value={String(watch("is_hidden"))}
                onValueChange={(value) =>
                  setValue("is_hidden", Number(value) === 1 ? 1 : 0)
                }
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="0">显示</SelectItem>
                  <SelectItem value="1">隐藏</SelectItem>
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
