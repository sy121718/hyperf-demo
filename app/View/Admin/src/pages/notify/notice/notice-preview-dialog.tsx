import { useQuery } from "@tanstack/react-query";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@rbac/ui/components/ui/dialog";
import { Badge } from "@rbac/ui/components/ui/badge";
import { Skeleton } from "@rbac/ui/components/ui/skeleton";
import { getNoticeInfo } from "@/api/notice";
import { queryKeys } from "@/lib/query-keys";
import { formatDateTime } from "@/lib/format";
import { NOTICE_STATUS_LABELS } from "@/types/notice";

/**
 * 公告预览。
 *
 * 列表行不含正文，所以预览也要单独拉详情；noticeId 为 null 时关闭。
 */
export function NoticePreviewDialog({
  noticeId,
  onClose,
}: {
  noticeId: number | null;
  onClose: () => void;
}) {
  const { data, isLoading } = useQuery({
    queryKey: [...queryKeys.notice.all, "detail", noticeId ?? 0],
    queryFn: () => getNoticeInfo(noticeId as number),
    enabled: noticeId !== null,
  });

  return (
    <Dialog open={noticeId !== null} onOpenChange={(open) => (!open ? onClose() : undefined)}>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{data?.title ?? "公告预览"}</DialogTitle>
          <DialogDescription>
            {data ? (
              <span className="flex flex-wrap items-center gap-2">
                <Badge variant="outline">{NOTICE_STATUS_LABELS[data.status] ?? data.status}</Badge>
                <span>发布于 {formatDateTime(data.publish_time)}</span>
                <span>已读 {data.read_count}</span>
              </span>
            ) : null}
          </DialogDescription>
        </DialogHeader>

        {isLoading ? (
          <div className="space-y-2">
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-5/6" />
            <Skeleton className="h-4 w-4/6" />
          </div>
        ) : (
          <div className="text-sm leading-relaxed whitespace-pre-wrap">
            {data?.content || "（无正文）"}
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
