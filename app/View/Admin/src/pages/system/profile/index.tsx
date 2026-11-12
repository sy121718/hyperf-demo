import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { z } from "zod";
import { toast } from "sonner";

import { Badge } from "@rbac/ui/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@rbac/ui/components/ui/card";
import { Input } from "@rbac/ui/components/ui/input";
import { Separator } from "@rbac/ui/components/ui/separator";
import { PageHeader } from "@/components/shared/page-header";
import { FormField } from "@/components/shared/form-field";
import { SubmitButton } from "@/components/shared/submit-button";
import { changePassword, getProfile, updateProfile } from "@/api/profile";
import { formatDateTime } from "@/lib/format";
import { useAuthStore } from "@/stores/auth";

const profileSchema = z.object({
  name: z.string().max(50),
  email: z.string().max(100),
  phone: z.string().max(20),
  avatar: z.string().max(255),
});

type ProfileValues = z.infer<typeof profileSchema>;

const passwordSchema = z
  .object({
    old_password: z.string().min(1, "请输入原密码").max(100),
    new_password: z.string().min(6, "新密码至少 6 位").max(100),
    confirm_password: z.string().min(6, "请再次输入新密码").max(100),
  })
  .refine((values) => values.new_password === values.confirm_password, {
    path: ["confirm_password"],
    message: "两次输入的密码不一致",
  });

type PasswordValues = z.infer<typeof passwordSchema>;

/** 个人中心：改资料与改密，两个表单互不影响 */
export default function ProfilePage() {
  const queryClient = useQueryClient();
  const setAdmin = useAuthStore((state) => state.setAdmin);

  const { data: profile } = useQuery({
    queryKey: ["auth", "profile"],
    queryFn: getProfile,
  });

  const profileForm = useForm<ProfileValues>({
    resolver: zodResolver(profileSchema),
    defaultValues: { name: "", email: "", phone: "", avatar: "" },
  });

  const passwordForm = useForm<PasswordValues>({
    resolver: zodResolver(passwordSchema),
    defaultValues: { old_password: "", new_password: "", confirm_password: "" },
  });

  useEffect(() => {
    if (!profile) {
      return;
    }
    profileForm.reset({
      name: profile.name ?? "",
      email: profile.email ?? "",
      phone: profile.phone ?? "",
      avatar: profile.avatar ?? "",
    });
  }, [profile, profileForm]);

  const profileMutation = useMutation({
    mutationFn: (values: ProfileValues) =>
      updateProfile({
        name: values.name || undefined,
        email: values.email || undefined,
        phone: values.phone || undefined,
        avatar: values.avatar || undefined,
      }),
    onSuccess: async () => {
      toast.success("资料已更新");
      // 顶栏显示的是 store 里的 admin，重新拉一次同步过去
      const latest = await getProfile();
      setAdmin(latest);
      void queryClient.invalidateQueries({ queryKey: ["auth", "profile"] });
    },
  });

  const passwordMutation = useMutation({
    mutationFn: (values: PasswordValues) =>
      changePassword(values.old_password, values.new_password),
    onSuccess: () => {
      toast.success("密码已修改");
      passwordForm.reset({ old_password: "", new_password: "", confirm_password: "" });
    },
  });

  return (
    <div className="space-y-4">
      <PageHeader title="个人中心" description="修改自己的资料与登录密码，不影响角色与权限。" />

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>账号信息</CardTitle>
            <CardDescription>账号与角色由管理员分配，不能在这里修改。</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3 text-sm">
            <div className="flex justify-between">
              <span className="text-muted-foreground">用户名</span>
              <span>{profile?.username ?? "-"}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">管理员标识</span>
              <code className="text-xs">{profile?.admin_code ?? "-"}</code>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">账号类型</span>
              {profile?.is_admin === 1 ? (
                <Badge>超级管理员</Badge>
              ) : (
                <Badge variant="outline">普通管理员</Badge>
              )}
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">最后登录</span>
              <span>{formatDateTime(profile?.last_login_time)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-muted-foreground">登录 IP</span>
              <code className="text-xs">{profile?.last_login_ip ?? "-"}</code>
            </div>
            <Separator />
            <div className="space-y-1.5">
              <span className="text-muted-foreground">角色</span>
              <div className="flex flex-wrap gap-1">
                {(profile?.roles ?? []).length === 0 ? (
                  <span className="text-muted-foreground">未分配角色</span>
                ) : (
                  (profile?.roles ?? []).map((role) => (
                    <Badge key={role.id} variant="secondary">
                      {role.role_name}
                    </Badge>
                  ))
                )}
              </div>
            </div>
          </CardContent>
        </Card>

        <div className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>基本资料</CardTitle>
            </CardHeader>
            <CardContent>
              <form
                className="space-y-3"
                onSubmit={profileForm.handleSubmit((values) => profileMutation.mutate(values))}
              >
                <div className="grid grid-cols-2 gap-3">
                  <FormField label="姓名" htmlFor="name" error={profileForm.formState.errors.name?.message}>
                    <Input id="name" {...profileForm.register("name")} />
                  </FormField>
                  <FormField label="手机号" htmlFor="phone" error={profileForm.formState.errors.phone?.message}>
                    <Input id="phone" {...profileForm.register("phone")} />
                  </FormField>
                </div>
                <FormField label="邮箱" htmlFor="email" error={profileForm.formState.errors.email?.message}>
                  <Input id="email" type="email" {...profileForm.register("email")} />
                </FormField>
                <FormField
                  label="头像地址"
                  htmlFor="avatar"
                  hint="填写可直接访问的图片 URL"
                  error={profileForm.formState.errors.avatar?.message}
                >
                  <Input id="avatar" {...profileForm.register("avatar")} />
                </FormField>
                <SubmitButton
                  type="submit"
                  pending={profileMutation.isPending}
                  pendingText="保存中…"
                >
                  保存资料
                </SubmitButton>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>修改密码</CardTitle>
              <CardDescription>改密后当前会话仍然有效，其他设备需重新登录。</CardDescription>
            </CardHeader>
            <CardContent>
              <form
                className="space-y-3"
                onSubmit={passwordForm.handleSubmit((values) => passwordMutation.mutate(values))}
              >
                <FormField
                  label="原密码"
                  htmlFor="old_password"
                  error={passwordForm.formState.errors.old_password?.message}
                >
                  <Input
                    id="old_password"
                    type="password"
                    autoComplete="current-password"
                    {...passwordForm.register("old_password")}
                  />
                </FormField>
                <div className="grid grid-cols-2 gap-3">
                  <FormField
                    label="新密码"
                    htmlFor="new_password"
                    error={passwordForm.formState.errors.new_password?.message}
                  >
                    <Input
                      id="new_password"
                      type="password"
                      autoComplete="new-password"
                      {...passwordForm.register("new_password")}
                    />
                  </FormField>
                  <FormField
                    label="确认新密码"
                    htmlFor="confirm_password"
                    error={passwordForm.formState.errors.confirm_password?.message}
                  >
                    <Input
                      id="confirm_password"
                      type="password"
                      autoComplete="new-password"
                      {...passwordForm.register("confirm_password")}
                    />
                  </FormField>
                </div>
                <SubmitButton
                  type="submit"
                  variant="outline"
                  pending={passwordMutation.isPending}
                  pendingText="提交中…"
                >
                  修改密码
                </SubmitButton>
              </form>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
