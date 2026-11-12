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
import { FormField } from "@/components/shared/form-field";
import { uploadAttachment } from "@/api/attachment";
import { queryKeys } from "@/lib/query-keys";

/**
 * 登记附件。
 *
 * 后端 upload 接口收的是文件元数据 JSON，不是 multipart 文件流
 * （见 app/View/CLAUDE.md 6.8）。所以这里只让用户填元信息，
 * 等后端支持真实上传后再换成文件选择框，改动只落在 api/attachment.ts。
 */
const schema = z.object({
  file_name: z.string().min(1, "请输入文件名").max(255),
  file_path: z.string().min(1, "请输入存储路径").max(500),
  file_size: z.number().int().min(0),
  file_type: z.string().min(1, "请输入文件类型").max(50),
  mime_type: z.string().max(100),
  storage_type: z.string().max(50),
  url: z.string().max(500),
  category_id: z.number().int().min(0),
});

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  file_name: "",
  file_path: "",
  file_size: 0,
  file_type: "document",
  mime_type: "",
  storage_type: "local",
  url: "",
  category_id: 0,
};

export function UploadAttachmentDialog({
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
    watch,
    setValue,
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
      uploadAttachment({
        file_name: values.file_name,
        file_path: values.file_path,
        file_size: values.file_size,
        file_type: values.file_type,
        mime_type: values.mime_type || undefined,
        storage_type: values.storage_type || undefined,
        url: values.url || undefined,
        category_id: values.category_id > 0 ? values.category_id : undefined,
      }),
    onSuccess: () => {
      toast.success("附件已登记");
      void queryClient.invalidateQueries({ queryKey: queryKeys.attachment.all });
      onOpenChange(false);
    },
  });

  const storageType = watch("storage_type");

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>登记附件</DialogTitle>
          <DialogDescription>
            当前接口只记录文件元数据，不会保存文件本身。
          </DialogDescription>
        </DialogHeader>

        <form className="space-y-3" onSubmit={handleSubmit((values) => mutation.mutate(values))}>
          <div className="grid grid-cols-2 gap-3">
            <FormField label="文件名" htmlFor="file_name" required error={errors.file_name?.message}>
              <Input id="file_name" placeholder="合同_2026.pdf" {...register("file_name")} />
            </FormField>
            <FormField label="文件类型" htmlFor="file_type" required error={errors.file_type?.message}>
              <Input id="file_type" placeholder="document" {...register("file_type")} />
            </FormField>
          </div>

          <FormField label="存储路径" htmlFor="file_path" required error={errors.file_path?.message}>
            <Input id="file_path" placeholder="/uploads/2026/11/xxx.pdf" {...register("file_path")} />
          </FormField>

          <div className="grid grid-cols-3 gap-3">
            <FormField label="大小（字节）" error={errors.file_size?.message}>
              <Input type="number" {...register("file_size", { valueAsNumber: true })} />
            </FormField>
            <FormField label="分类 ID" hint="0 表示不分类">
              <Input type="number" {...register("category_id", { valueAsNumber: true })} />
            </FormField>
            <FormField label="存储方式">
              <Select
                value={storageType}
                onValueChange={(value) => setValue("storage_type", value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="local">本地</SelectItem>
                  <SelectItem value="oss">对象存储</SelectItem>
                  <SelectItem value="cos">COS</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <FormField label="MIME" htmlFor="mime_type" error={errors.mime_type?.message}>
              <Input id="mime_type" placeholder="application/pdf" {...register("mime_type")} />
            </FormField>
            <FormField label="访问地址" htmlFor="url" error={errors.url?.message}>
              <Input id="url" placeholder="https://..." {...register("url")} />
            </FormField>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              取消
            </Button>
            <Button type="submit" disabled={mutation.isPending}>
              {mutation.isPending ? "提交中…" : "登记"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
