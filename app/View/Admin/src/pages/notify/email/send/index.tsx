import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery } from "@tanstack/react-query";
import { z } from "zod";
import { Send } from "lucide-react";
import { toast } from "sonner";

import { Button } from "@rbac/ui/components/ui/button";
import { Badge } from "@rbac/ui/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@rbac/ui/components/ui/card";
import { Input } from "@rbac/ui/components/ui/input";
import { Textarea } from "@rbac/ui/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@rbac/ui/components/ui/select";
import { PageHeader } from "@/components/shared/page-header";
import { FormField } from "@/components/shared/form-field";
import { getEmailTemplates, sendEmail, testSendEmail } from "@/api/email";
import { queryKeys } from "@/lib/query-keys";
import { usePermission } from "@/lib/permission";

/**
 * 发送邮件。
 *
 * 后端只负责落一条发送记录与收件人明细，真正投递由队列消费者完成，
 * 所以这里成功后提示「已提交」，不说「已发送」。
 */
const schema = z.object({
  subject: z.string().min(1, "请输入主题").max(200),
  content: z.string().min(1, "请输入正文"),
  recipients: z.string().min(1, "请至少填写一个收件邮箱"),
  template_id: z.number().int().min(0),
});

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  subject: "",
  content: "",
  recipients: "",
  template_id: 0,
};

/** 逗号 / 分号 / 换行都当分隔符，去空去重 */
function parseRecipients(raw: string): string[] {
  return Array.from(
    new Set(
      raw
        .split(/[\s,;]+/)
        .map((item) => item.trim())
        .filter(Boolean),
    ),
  );
}

export default function EmailSendPage() {
  const canSend = usePermission("email:send");
  const canTest = usePermission("email:testSend");

  const { data: templates } = useQuery({
    queryKey: queryKeys.email.templates({}),
    queryFn: () => getEmailTemplates({ limit: 100 }),
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

  const [testAddress, setTestAddress] = useState("");

  const sendMutation = useMutation({
    mutationFn: (values: FormValues) =>
      sendEmail({
        subject: values.subject,
        content: values.content,
        to_list: parseRecipients(values.recipients),
        template_id: values.template_id > 0 ? values.template_id : undefined,
      }),
    onSuccess: (result) => {
      toast.success(`已提交，共 ${result.recipient_count} 个收件人`);
      reset(DEFAULT_VALUES);
    },
  });

  const testMutation = useMutation({
    mutationFn: (to: string) => testSendEmail(to),
    onSuccess: () => toast.success("测试邮件已发送"),
  });

  const templateId = watch("template_id");
  const templateOptions = templates?.list ?? [];

  // 选中模板后把主题与正文带过来，用户可继续改
  useEffect(() => {
    if (templateId === 0) {
      return;
    }
    const template = templateOptions.find((item) => item.id === templateId);
    if (!template) {
      return;
    }
    setValue("subject", template.subject ?? "");
  }, [templateId, templateOptions, setValue]);

  return (
    <div className="space-y-4">
      <PageHeader
        title="发送邮件"
        description="提交后进入发送队列，实际投递进度可在「发送记录」里查看。"
      />

      <div className="grid gap-4 lg:grid-cols-[2fr_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>邮件内容</CardTitle>
          </CardHeader>
          <CardContent>
            <form
              className="space-y-3"
              onSubmit={handleSubmit((values) => sendMutation.mutate(values))}
            >
              <div className="grid grid-cols-2 gap-3">
                <FormField label="使用模板" hint="只用来预填主题">
                  <Select
                    value={String(templateId)}
                    onValueChange={(value) => setValue("template_id", Number(value))}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="不使用模板" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="0">不使用模板</SelectItem>
                      {templateOptions.map((template) => (
                        <SelectItem key={template.id} value={String(template.id)}>
                          {template.template_name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </FormField>
                <FormField label="主题" htmlFor="subject" required error={errors.subject?.message}>
                  <Input id="subject" {...register("subject")} />
                </FormField>
              </div>

              <FormField
                label="收件人"
                htmlFor="recipients"
                required
                hint="多个邮箱用逗号、分号或换行分隔"
                error={errors.recipients?.message}
              >
                <Textarea
                  id="recipients"
                  rows={2}
                  placeholder="a@example.com, b@example.com"
                  {...register("recipients")}
                />
              </FormField>

              <FormField label="正文" htmlFor="content" required error={errors.content?.message}>
                <Textarea id="content" rows={10} className="font-mono text-xs" {...register("content")} />
              </FormField>

              <Button type="submit" disabled={!canSend || sendMutation.isPending}>
                <Send />
                {sendMutation.isPending ? "提交中…" : "提交发送"}
              </Button>
            </form>
          </CardContent>
        </Card>

        <Card className="h-fit">
          <CardHeader>
            <CardTitle>测试发送</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <p className="text-sm text-muted-foreground">
              只发送一封空白测试邮件，用于验证 SMTP 配置是否可用。
            </p>
            <Input
              value={testAddress}
              placeholder="you@example.com"
              onChange={(event) => setTestAddress(event.target.value)}
            />
            <Button
              variant="outline"
              className="w-full"
              disabled={!canTest || !testAddress || testMutation.isPending}
              onClick={() => testMutation.mutate(testAddress)}
            >
              {testMutation.isPending ? "发送中…" : "发送测试邮件"}
            </Button>
            <div className="flex flex-wrap gap-1">
              {parseRecipients(watch("recipients")).slice(0, 6).map((item) => (
                <Badge key={item} variant="secondary" className="text-xs">
                  {item}
                </Badge>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
