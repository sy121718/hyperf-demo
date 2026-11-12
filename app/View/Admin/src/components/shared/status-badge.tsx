import { Badge } from "@rbac/ui/components/ui/badge";

/** 0 / 1 状态标签 */
export function StatusBadge({ status }: { status: number | null | undefined }) {
  if (status === 1) {
    return <Badge variant="secondary">启用</Badge>;
  }
  if (status === 0) {
    return <Badge variant="outline">禁用</Badge>;
  }
  return <Badge variant="ghost">-</Badge>;
}
