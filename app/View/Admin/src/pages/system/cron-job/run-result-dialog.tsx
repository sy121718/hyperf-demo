import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@rbac/ui/components/ui/dialog";
import { Button } from "@rbac/ui/components/ui/button";
import { Badge } from "@rbac/ui/components/ui/badge";
import type { CronJobRunResult } from "@/types/cron-job";

/** 手动执行结果：退出码 + 标准输出 + 错误输出 */
export function RunResultDialog({
  result,
  onClose,
}: {
  result: CronJobRunResult | null;
  onClose: () => void;
}) {
  return (
    <Dialog open={result !== null} onOpenChange={(open) => (!open ? onClose() : undefined)}>
      <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>
            执行结果
            {result ? (
              <Badge variant={result.success ? "secondary" : "destructive"} className="ml-2">
                {result.success ? "成功" : "失败"}
              </Badge>
            ) : null}
          </DialogTitle>
          <DialogDescription>
            {result ? `任务：${result.job_name}，退出码 ${result.exit_code ?? "-"}` : null}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-3">
          <section className="space-y-1">
            <p className="text-sm font-medium">标准输出</p>
            <pre className="max-h-60 overflow-auto rounded-md bg-muted p-3 text-xs whitespace-pre-wrap">
              {result?.output || "（无输出）"}
            </pre>
          </section>

          <section className="space-y-1">
            <p className="text-sm font-medium">错误输出</p>
            <pre className="max-h-40 overflow-auto rounded-md bg-muted p-3 text-xs whitespace-pre-wrap">
              {result?.error_output || "（无错误输出）"}
            </pre>
          </section>
        </div>

        <DialogFooter>
          <Button onClick={onClose}>关闭</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
