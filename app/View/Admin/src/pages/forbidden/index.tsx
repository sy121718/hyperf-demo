import { Link } from "react-router-dom";

export default function ForbiddenPage() {
  return (
    <div className="flex min-h-svh flex-col items-center justify-center gap-4">
      <p className="text-2xl font-semibold">403</p>
      <p className="text-muted-foreground">权限不足，无法访问该页面</p>
      <Link className="text-sm text-primary underline underline-offset-4" to="/admin">
        返回首页
      </Link>
    </div>
  );
}
