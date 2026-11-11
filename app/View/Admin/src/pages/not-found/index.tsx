import { Link } from "react-router-dom";

export default function NotFoundPage() {
  return (
    <div className="flex min-h-96 flex-col items-center justify-center gap-4">
      <p className="text-2xl font-semibold">404</p>
      <p className="text-muted-foreground">页面不存在或已被移除</p>
      <Link className="text-sm text-primary underline underline-offset-4" to="/admin">
        返回首页
      </Link>
    </div>
  );
}
