import { useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { toast } from "sonner";

import { Button } from "@rbac/ui/components/ui/button";
import { login } from "@/api/auth";
import { useAuthStore } from "@/stores/auth";

const schema = z.object({
  username: z.string().min(1, "请输入用户名"),
  password: z.string().min(1, "请输入密码"),
});

type FormValues = z.infer<typeof schema>;

export default function LoginPage() {
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const setSession = useAuthStore((state) => state.setSession);
  const [submitting, setSubmitting] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { username: "", password: "" },
  });

  const onSubmit = handleSubmit(async (values) => {
    setSubmitting(true);
    try {
      const result = await login(values);
      setSession(result.token, result.admin_info);
      toast.success("登录成功");
      // 回跳到跳转前的页面，缺省进后台首页
      navigate(params.get("redirect") ?? "/", { replace: true });
    } finally {
      setSubmitting(false);
    }
  });

  return (
    <div className="flex min-h-svh items-center justify-center bg-sidebar p-6">
      <form
        onSubmit={onSubmit}
        className="w-full max-w-sm space-y-6 rounded-2xl border border-border/60 bg-card p-8 shadow-xl shadow-foreground/5"
      >
        <div className="space-y-2">
          <div className="flex size-10 items-center justify-center rounded-xl bg-primary text-sm font-semibold text-primary-foreground">
            RB
          </div>
          <h1 className="text-xl font-semibold tracking-tight">登录管理后台</h1>
          <p className="text-sm text-muted-foreground">使用管理员账号继续</p>
        </div>

        <div className="space-y-2">
          <label htmlFor="username" className="text-sm font-medium">
            用户名
          </label>
          <input
            id="username"
            autoComplete="username"
            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
            {...register("username")}
          />
          {errors.username && (
            <p className="text-sm text-destructive">{errors.username.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <label htmlFor="password" className="text-sm font-medium">
            密码
          </label>
          <input
            id="password"
            type="password"
            autoComplete="current-password"
            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
            {...register("password")}
          />
          {errors.password && (
            <p className="text-sm text-destructive">{errors.password.message}</p>
          )}
        </div>

        <Button type="submit" className="w-full" disabled={submitting}>
          {submitting ? "登录中…" : "登录"}
        </Button>
      </form>
    </div>
  );
}
