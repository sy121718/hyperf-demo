import { useQuery } from "@tanstack/react-query";
import {
  FileText,
  KeyRound,
  ListChecks,
  Megaphone,
  MonitorSmartphone,
  ShieldCheck,
  TriangleAlert,
  Users,
  type LucideIcon,
} from "lucide-react";
import { Link } from "react-router-dom";

import { Card, CardContent, CardHeader, CardTitle } from "@rbac/ui/components/ui/card";
import { Skeleton } from "@rbac/ui/components/ui/skeleton";
import { PageHeader } from "@/components/shared/page-header";
import { getDashboardOverview } from "@/api/dashboard";
import { useAuthStore } from "@/stores/auth";
import type { DashboardCard, DashboardCardKey } from "@/types/dashboard";

/**
 * 卡片图标表。
 *
 * 后端只下发 key，图标由前端决定——图标是纯展示，不该占用接口字段。
 * 新增指标时这里没配也不会白屏，走兜底图标。
 */
const CARD_ICONS: Record<DashboardCardKey, LucideIcon> = {
  admin_total: Users,
  role_total: ShieldCheck,
  session_total: MonitorSmartphone,
  notice_unread: Megaphone,
  menu_total: ListChecks,
  permission_total: KeyRound,
  operation_today: FileText,
  login_failed_today: TriangleAlert,
};

const FALLBACK_ICON = FileText;

/** 需要提醒的卡片：非零时用警示色，避免安全问题被淹没在数字里 */
const ALERT_KEYS: ReadonlySet<DashboardCardKey> = new Set(["login_failed_today"]);

interface StatCardProps {
  card: DashboardCard;
  loading: boolean;
}

function StatCard({ card, loading }: StatCardProps) {
  const Icon = CARD_ICONS[card.key] ?? FALLBACK_ICON;
  const alert = ALERT_KEYS.has(card.key) && card.value > 0;

  return (
    <Link
      to={card.path}
      className="block rounded-xl transition-transform hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
      aria-label={`${card.title}：${card.value}，前往详情`}
    >
      <Card className="h-full transition-shadow hover:shadow-md">
        <CardHeader className="flex-row items-center justify-between pb-2">
          <CardTitle className="text-sm font-medium text-muted-foreground">{card.title}</CardTitle>
          <span
            className={
              alert
                ? "flex size-8 items-center justify-center rounded-lg bg-destructive/10"
                : "flex size-8 items-center justify-center rounded-lg bg-muted"
            }
          >
            <Icon className={alert ? "size-4 text-destructive" : "size-4 text-muted-foreground"} />
          </span>
        </CardHeader>
        <CardContent>
          {loading ? (
            <Skeleton className="h-8 w-16" />
          ) : (
            <p
              className={
                alert
                  ? "text-3xl font-semibold tracking-tight text-destructive"
                  : "text-3xl font-semibold tracking-tight"
              }
            >
              {card.value}
            </p>
          )}
        </CardContent>
      </Card>
    </Link>
  );
}

export default function DashboardPage() {
  const admin = useAuthStore((state) => state.admin);

  const { data, isLoading } = useQuery({
    queryKey: ["dashboard", "overview"],
    queryFn: getDashboardOverview,
  });

  const cards = data?.cards ?? [];

  return (
    <div className="space-y-4">
      <PageHeader
        title={`欢迎回来${admin?.name ? `，${admin.name}` : ""}`}
        description="这里汇总了当前账号有权查看的模块概况，点击卡片可直接进入对应页面。"
      />

      {isLoading ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {Array.from({ length: 4 }).map((_, index) => (
            <Card key={index}>
              <CardHeader className="flex-row items-center justify-between pb-2">
                <Skeleton className="h-4 w-16" />
                <Skeleton className="size-8 rounded-lg" />
              </CardHeader>
              <CardContent>
                <Skeleton className="h-8 w-16" />
              </CardContent>
            </Card>
          ))}
        </div>
      ) : cards.length === 0 ? (
        <Card>
          <CardContent className="py-10 text-center text-sm text-muted-foreground">
            当前账号还没有任何模块的查看权限，请联系超级管理员分配角色。
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {cards.map((card) => (
            <StatCard key={card.key} card={card} loading={false} />
          ))}
        </div>
      )}
    </div>
  );
}
