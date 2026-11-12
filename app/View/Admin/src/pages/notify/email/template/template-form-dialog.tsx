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
import { SubmitButton } from "@/components/shared/submit-button";
import { FormField } from "@/components/shared/form-field";
import { createEmailTemplate, updateEmailTemplate } from "@/api/email";
import { queryKeys } from "@/lib/query-keys";
import type { EmailTemplateRow } from "@/types/email";

/**
 * 邮件模板表单。
 *
 * 后端没有「模板详情」接口，列表行也不带 content，
 * 因此编辑时沿用列表传入的整行数据（列表接口 query 不裁剪字段）。
 * variables 用 JSON 文本编辑，提交前本地解析。
 */
const schema = z.object({
  template_name: z.string().min(1, "请输入模板名称").max(100),
  template_code: z.string().min(1, "请输入模板编码").max(50),
  subject: z.string().min(1, "请输入主题").max(200),
  content: z.string().min(1, "请输入模板内容"),
  template_type: z.string().max(20),
  variables: z.string(),
  status: z.union([z.literal(0), z.literal(1)]),
});

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  template_name: "",
  template_code: "",
  subject: "",
  content: "",
  template_type: "html",
  variables: "{}",
  status: 1,
};

export interface TemplateFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  /** 编辑时传入列表行；后端无详情接口 */
  template?: EmailTemplateRow;
}

export function TemplateFormDialog({ open, onOpenChange, template }: TemplateFormDialogProps) {
  const queryClient = useQueryClient();
  const isEdit = template !== undefined;
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
    if (!template) {
      reset(DEFAULT_VALUES);
      return;
    }

    // 列表行带上 subject / content 时直接回填，缺失则留空由用户补
    const raw = template as EmailTemplateRow & { subject?: string; content?: string };
    reset({
      template_name: template.template_name,
      template_code: template.template_code,
      subject: raw.subject ?? "",
      content: raw.content ?? "",
      template_type: template.template_type || "html",
      variables: JSON.stringify(template.variables ?? {}, null, 2),
      status: template.status ?? 1,
    });
  }, [open, template, reset]);

  const mutation = useMutation({
    mutationFn: (values: FormValues & { parsedVariables: Record<string, string> }) => {
      const payload = {
        template_name: values.template_name,
        template_code: values.template_code,
        subject: values.subject,
        content: values.content,
        template_type: values.template_type,
        variables: values.parsedVariables,
        status: values.status,
      };
      return isEdit ? updateEmailTemplate(template.id, payload) : createEmailTemplate(payload);
    },
    onSuccess: () => {
      toast.success(isEdit ? "模板已更新" : "模板已创建");
      void queryClient.invalidateQueries({ queryKey: queryKeys.email.all });
      onOpenChange(false);
    },
  });

  const status = watch("status");
  const templateType = watch("template_type");

  const onSubmit = handleSubmit((values) => {
    let parsed: unknown;
    try {
      parsed = JSON.parse(values.variables || "{}");
    } catch {
      setJsonError("JSON 格式不正确");
      return;
    }

    if (typeof parsed !== "object" || parsed === null || Array.isArray(parsed)) {
      setJsonError("变量声明必须是对象，形如 {\"name\": \"用户名\"}");
      return;
    }

    setJsonError(null);
    mutation.mutate({ ...values, parsedVariables: parsed as Record<string, string> });
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{isEdit ? "编辑模板" : "新增模板"}</DialogTitle>
          <DialogDescription>
            内容以 HTML 存储，变量用 `{"{{name}}"}` 形式在发送时替换。
          </DialogDescription>
        </DialogHeader>

        <form className="space-y-3" onSubmit={onSubmit}>
          <div className="grid grid-cols-2 gap-3">
            <FormField
              label="模板名称"
              htmlFor="template_name"
              required
              error={errors.template_name?.message}
            >
              <Input id="template_name" {...register("template_name")} />
            </FormField>
            <FormField
              label="模板编码"
              htmlFor="template_code"
              required
              hint="业务代码按编码取模板"
              error={errors.template_code?.message}
            >
              <Input id="template_code" {...register("template_code")} />
            </FormField>
          </div>

          <FormField label="主题" htmlFor="subject" required error={errors.subject?.message}>
            <Input id="subject" {...register("subject")} />
          </FormField>

          <FormField label="内容" htmlFor="content" required error={errors.content?.message}>
            <Textarea id="content" rows={8} className="font-mono text-xs" {...register("content")} />
          </FormField>

          <FormField label="变量声明" hint='JSON 对象，形如 {"name": "用户名"}' error={jsonError ?? undefined}>
            <Textarea id="variables" rows={3} className="font-mono text-xs" {...register("variables")} />
          </FormField>

          <div className="grid grid-cols-2 gap-3">
            <FormField label="模板类型">
              <Select
                value={templateType}
                onValueChange={(value) => setValue("template_type", value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="html">HTML</SelectItem>
                  <SelectItem value="text">纯文本</SelectItem>
                </SelectContent>
              </Select>
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
