import { useEffect, useState } from "react";
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
import { FormField } from "@/components/shared/form-field";
import { createNotice, getNoticeInfo, updateNotice } from "@/api/notice";
import { queryKeys } from "@/lib/query-keys";

/**
 * 公告表单。
 *
 * 列表接口不带 content，编辑前必须单独拉详情，否则会把正文清空。
 * status 只允许 0/1/2（草稿/已发布/已撤回），发布与撤回另有专门接口。
 */
const schema = z.object({
  title: z.string().min(1, "请输入标题").max(200),
  content: z.string(),
  notice_type: z.number().int().min(0),
  is_top: z.union([z.literal(0), z.literal(1)]),
  is_popup: z.union([z.literal(0), z.literal(1)]),
  status: z.union([z.literal(0), z.literal(1), z.literal(2)]),
  expire_time: z.string().max(30),
});

type FormValues = z.infer<typeof schema>;

const DEFAULT_VALUES: FormValues = {
  title: "",
  content: "",
  notice_type: 1,
  is_top: 0,
  is_popup: 0,
  status: 0,
  expire_time: "",
};

export interface NoticeFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  noticeId?: number;
}

export function NoticeFormDialog({ open, onOpenChange, noticeId }: NoticeFormDialogProps) {
  const queryClient = useQueryClient();
  const isEdit = noticeId !== undefined;
  const [detailLoading, setDetailLoading] = useState(false);

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

  // 编辑时单独拉详情补正文
  const { data: detail } = useQuery({
    queryKey: [...queryKeys.notice.all, "detail", noticeId ?? 0],
    queryFn: () => getNoticeInfo(noticeId as number),
    enabled: open && isEdit,
    staleTime: 0,
  });

  useEffect(() => {
    if (!open) {
      return;
    }
    if (!isEdit) {
      reset(DEFAULT_VALUES);
      return;
    }
    if (!detail) {
      return;
    }

    reset({
      title: detail.title,
      content: detail.content ?? "",
      notice_type: detail.notice_type,
      is_top: detail.is_top,
      is_popup: detail.is_popup,
      status: detail.status as 0 | 1 | 2,
      expire_time: detail.expire_time ?? "",
    });
  }, [open, isEdit, detail, reset]);

  useEffect(() => {
    setDetailLoading(open && isEdit && !detail);
  }, [open, isEdit, detail]);

  const mutation = useMutation({
    mutationFn: (values: FormValues) => {
      const payload = {
        ...values,
        content: values.content || undefined,
        // 后端会兼容时间戳与日期字符串，空串表示不设过期
        expire_time: values.expire_time || undefined,
      };
      return isEdit ? updateNotice(noticeId, payload) : createNotice(payload);
    },
    onSuccess: () => {
      toast.success(isEdit ? "公告已更新" : "公告已创建");
      void queryClient.invalidateQueries({ queryKey: queryKeys.notice.all });
      onOpenChange(false);
    },
  });

  const status = watch("status");
  const noticeType = watch("notice_type");
  const isTop = watch("is_top");
  const isPopup = watch("is_popup");

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{isEdit ? "编辑公告" : "新建公告"}</DialogTitle>
          <DialogDescription>
            新建后默认是草稿，需要发布才能被用户看到。
          </DialogDescription>
        </DialogHeader>

        <form
          className="space-y-3"
          onSubmit={handleSubmit((values) => mutation.mutate(values))}
        >
          <FormField label="标题" htmlFor="title" required error={errors.title?.message}>
            <Input id="title" placeholder="系统维护通知" {...register("title")} />
          </FormField>

          <FormField label="正文" htmlFor="content" hint="支持纯文本，暂不支持富文本编辑器">
            <Textarea
              id="content"
              rows={8}
              placeholder={detailLoading ? "正在加载正文…" : "填写公告内容"}
              disabled={detailLoading}
              {...register("content")}
            />
          </FormField>

          <div className="grid grid-cols-2 gap-3">
            <FormField label="类型">
              <Select
                value={String(noticeType)}
                onValueChange={(value) => setValue("notice_type", Number(value))}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1">通知</SelectItem>
                  <SelectItem value="2">公告</SelectItem>
                  <SelectItem value="3">警告</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
            <FormField
              label="过期时间"
              htmlFor="expire_time"
              hint="留空表示长期有效"
              error={errors.expire_time?.message}
            >
              <Input id="expire_time" type="datetime-local" {...register("expire_time")} />
            </FormField>
          </div>

          <div className="grid grid-cols-3 gap-3">
            <FormField label="置顶">
              <Select
                value={String(isTop)}
                onValueChange={(value) => setValue("is_top", Number(value) === 1 ? 1 : 0)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="0">否</SelectItem>
                  <SelectItem value="1">是</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="弹窗提醒">
              <Select
                value={String(isPopup)}
                onValueChange={(value) => setValue("is_popup", Number(value) === 1 ? 1 : 0)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="0">否</SelectItem>
                  <SelectItem value="1">是</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
            <FormField label="状态">
              <Select
                value={String(status)}
                onValueChange={(value) => setValue("status", Number(value) as 0 | 1 | 2)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="0">草稿</SelectItem>
                  <SelectItem value="1">已发布</SelectItem>
                  <SelectItem value="2">已撤回</SelectItem>
                </SelectContent>
              </Select>
            </FormField>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              取消
            </Button>
            <Button type="submit" disabled={mutation.isPending || detailLoading}>
              {"保存"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
