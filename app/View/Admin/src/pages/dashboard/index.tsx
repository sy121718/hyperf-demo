import { useQuery } from "@tanstack/react-query";
import { FileText, Megaphone, MonitorSmartphone, ShieldCheck, Users } from "lucide-react";

import { Badge } from "@rbac/ui/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@rbac/ui/components/ui/card";
import { Skeleton } from "@rbac/ui/components/ui/skeleton";
import { PageHeader } from "@/components/shared/page-header";
import { getDashboardOverview } from "@/api/dashboard";
import { useAuthStore } from "@/stores/auth";
import { NOTICE_STATUS_LABELS } from "@/types/notice";

/** 概览卡片：一个指标 + 图标 */
function StatCard({
  title,
  value,
  icon: Icon,
  loading,
}: {
  title: string;
  value: number;
  icon: typeof Users;
  loading: boolean;
}) {
  return (
    <Card className="transition-shadow hover:shadow-md">
      <CardHeader className="flex-row items-center justify-between pb-2">
        <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
        <span className="flex size-8 items-center justify-center rounded-lg bg-muted">
          <Icon className="size-4 text-muted-foreground" />
        </span>
      </CardHeader>
      <CardContent>
        {loading ? (
          <Skeleton className="h-8 w-16" />
        ) : (
          <p className="text-3xl font-semibold tracking-tight">{value}</p>
        )}
      </CardContent>
    </Card>
  );
}

export default function DashboardPage() {
  const admin = useAuthStore((state) => state.admin);

  const { data, isLoading } = useQuery({
    queryKey: ["dashboard", "overview"],
    queryFn: getDashboardOverview,
  });

  const statistics = data?.noticeStatistics;

  return (
    <div className="space-y-4">
      <PageHeader
        title={`欢迎回来${admin?.name ? `，${admin.name}` : ""}`}
        description="这里汇总了系统中与权限、公告相关的关键数据。"
      />

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard title="管理员" value={data?.adminTotal ?? 0} icon={Users} loading={isLoading} />
        <StatCard title="角色" value={data?.roleTotal ?? 0} icon={ShieldCheck} loading={isLoading} />
        <StatCard
          title="在线会话"
          value={data?.sessionTotal ?? 0}
          icon={MonitorSmartphone}
          loading={isLoading}
        />
        <StatCard title="未读公告" value={data?.unreadTotal ?? 0} icon={Megaphone} loading={isLoading} />
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <FileText className="size-4" />
            公告统计
            {statistics ? (
              <span className="text-xs font-normal text-muted-foreground">
                {statistics.date_range.start_date} 至 {statistics.date_range.end_date}
              </span>
            ) : null}
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {isLoading ? (
            <Skeleton className="h-20 w-full" />
          ) : (
            <>
              <div>
                <p className="mb-2 text-sm text-muted-foreground">按状态</p>
                <div className="flex flex-wrap gap-2">
                  {(statistics?.status_stats ?? []).length === 0 ? (
                    <span className="text-sm text-muted-foreground">近 30 天没有公告</span>
                  ) : (
                    (statistics?.status_stats ?? []).map((item) => (
                      <Badge key={item.status} variant="secondary">
                        {NOTICE_STATUS_LABELS[item.status] ?? item.status}：{item.count}
                      </Badge>
                    ))
                  )}
                </div>
              </div>

              <div>
                <p className="mb-2 text-sm text-muted-foreground">按类型</p>
                <div className="flex flex-wrap gap-2">
                  {(statistics?.type_stats ?? []).length === 0 ? (
                    <span className="text-sm text-muted-foreground">-</span>
                  ) : (
                    (statistics?.type_stats ?? []).map((item) => (
                      <Badge key={item.notice_type} variant="outline">
                        类型 {item.notice_type}：{item.count}
                      </Badge>
                    ))
                  )}
                </div>
              </div>

              <p className="text-sm text-muted-foreground">
                累计阅读 <span className="font-medium text-foreground">{statistics?.total_read_count ?? 0}</span> 次
              </p>
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
