import { useEffect, useState } from "react";
import { BookOpen, Briefcase, Building2, CircleCheck, GraduationCap, Users } from "lucide-react";
import { api } from "@/lib/api";
import { SERIES_COLORS, TONE_COLOR } from "@/lib/status";
import { withSynthetic } from "@/lib/synthetic";
import { AdminLink } from "./AdminWorkspace";
import { SyntheticToggle } from "./SyntheticToggle";
import { Button, Card, PageHeader, Pill, formatAction, formatDateTime } from "./ui";
import {
  ActionList,
  EmptyState,
  ErrorState,
  KpiCard,
  LoadingBlock,
  SectionHeading,
  type ActionItem,
} from "./ds";
import { BarChartView, ChartCard, DonutChartView } from "./charts";

type Overview = {
  total_users: number;
  users_by_role: { role: string | null; total: number }[];
  programs: { id: number; code: string; name: string; cluster: string; is_active: boolean }[];
  programs_without_coordinator?: { id: number; code: string }[];
  terms: { id: number; is_active: boolean }[];
  chat_enabled: boolean;
  recent_activity: { id: number; action: string; created_at: string }[];
};
type SystemStats = { stats: { label: string; value: number }[]; generated_at: string };

const ROLE_LABEL: Record<string, string> = {
  student: "Students",
  coordinator: "Coordinators",
  supervisor: "Supervisors",
  dean: "Deans",
  admin: "Administrators",
};

/**
 * Administrator overview. Account and program figures come from GET /admin/overview; placement,
 * host and opportunity counts from the system-wide GET /dashboard. Nothing is hard-coded.
 */
export function AdminOverview() {
  const [data, setData] = useState<Overview | null>(null);
  const [system, setSystem] = useState<SystemStats | null>(null);
  const [error, setError] = useState("");
  const [systemError, setSystemError] = useState("");
  const [revision, setRevision] = useState(0);
  const [includeSynthetic, setIncludeSynthetic] = useState(false);
  useEffect(() => {
    let active = true;
    setError("");
    api<Overview>(withSynthetic("/admin/overview", includeSynthetic))
      .then((d) => {
        if (active) setData(d);
      })
      .catch((e: Error) => {
        if (active) setError(e.message);
      });
    return () => {
      active = false;
    };
  }, [revision, includeSynthetic]);
  useEffect(() => {
    let active = true;
    setSystemError("");
    setSystem(null);
    api<SystemStats>(withSynthetic("/dashboard", includeSynthetic))
      .then((d) => {
        if (active) setSystem(d);
      })
      .catch((e: Error) => {
        if (active) setSystemError(e.message);
      });
    return () => {
      active = false;
    };
  }, [revision, includeSynthetic]);

  const retry = () => setRevision((v) => v + 1);
  const count = (role: string) => data?.users_by_role.find((r) => r.role === role)?.total ?? 0;
  const stat = (label: string) => system?.stats.find((x) => x.label === label)?.value ?? null;
  const noCoordinator = data?.programs_without_coordinator ?? [];
  const noOpenTerm = !!data && !data.terms.some((t) => t.is_active);
  const loading = !data && !error;
  const systemLoading = !system && !systemError;

  const actions: ActionItem[] = [];
  if (noCoordinator.length)
    actions.push({
      id: "coordinator",
      tone: "danger",
      to: "/admin/users",
      title: `${noCoordinator.map((p) => p.code).join(", ")} ${noCoordinator.length === 1 ? "has" : "have"} no active coordinator`,
      detail: "No one can review or place students until a coordinator is assigned",
    });
  if (noOpenTerm)
    actions.push({
      id: "term",
      tone: "warn",
      to: "/admin/terms",
      title: "No academic term is open",
      detail: "Recommendations and placements need an active term",
    });

  const roles = ["student", "coordinator", "supervisor", "dean", "admin"].map((role) => ({
    role: ROLE_LABEL[role] ?? role,
    accounts: count(role),
  }));
  const pipeline = [
    { label: "Pending decision", value: stat("Pending placements") ?? 0, color: TONE_COLOR.warn },
    { label: "Active", value: stat("Active placements") ?? 0, color: TONE_COLOR.info },
    { label: "Completed", value: stat("Completed placements") ?? 0, color: TONE_COLOR.success },
  ];
  const pipelineTotal = pipeline.reduce((sum, x) => sum + x.value, 0);

  return (
    <div className="space-y-8">
      <PageHeader
        title="Overview"
        subtitle="System-wide summary and platform status"
        action={
          <>
            <SyntheticToggle
              checked={includeSynthetic}
              onChange={setIncludeSynthetic}
              disabled={systemLoading}
            />
            <Button variant="outline" size="sm" onClick={retry}>
              Refresh
            </Button>
          </>
        }
      />
      {error && <ErrorState title="Could not load the overview" message={error} onRetry={retry} />}

      <section aria-labelledby="admin-attention">
        <SectionHeading id="admin-attention" title="Needs your attention" />
        <ActionList
          items={actions}
          loading={loading}
          emptyText="Nothing needs your attention right now."
        />
      </section>

      <section aria-labelledby="admin-kpis">
        <SectionHeading id="admin-kpis" title="System at a glance" />
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
          <KpiCard
            label="Total accounts"
            value={data?.total_users ?? null}
            icon={Users}
            loading={loading}
          />
          <KpiCard
            label="Students"
            value={data ? count("student") : null}
            icon={GraduationCap}
            loading={loading}
          />
          <KpiCard
            label="Active programs"
            value={data ? data.programs.filter((x) => x.is_active).length : null}
            icon={BookOpen}
            loading={loading}
          />
          <KpiCard
            label="Active host establishments"
            value={stat("Active hosts (system-wide)")}
            icon={Building2}
            loading={systemLoading}
          />
          <KpiCard
            label="Published opportunities"
            value={stat("Published opportunities (system-wide)")}
            icon={Briefcase}
            loading={systemLoading}
          />
          <KpiCard
            label="Active placements"
            value={stat("Active placements")}
            icon={CircleCheck}
            tone="info"
            loading={systemLoading}
          />
        </div>
      </section>

      <section aria-labelledby="admin-charts" className="space-y-4">
        <SectionHeading id="admin-charts" title="Accounts and placements" />
        <div className="grid gap-4 xl:grid-cols-5">
          <ChartCard
            className="xl:col-span-3"
            title="Accounts by role"
            description="Every account in the system"
            loading={loading}
            error={error}
            onRetry={retry}
            isEmpty={!data || data.total_users === 0}
            emptyTitle="No accounts yet"
          >
            <BarChartView
              title="Accounts by role"
              data={roles}
              categoryKey="role"
              categoryLabel="Role"
              valueLabel="Accounts"
              unit="accounts"
              series={[{ key: "accounts", label: "Accounts", color: SERIES_COLORS[0]! }]}
            />
          </ChartCard>
          <ChartCard
            className="xl:col-span-2"
            title="Placement status"
            description="All placements in the system"
            loading={systemLoading}
            error={systemError}
            onRetry={retry}
            isEmpty={pipelineTotal === 0}
            emptyTitle="No placements yet"
            emptyDescription="Placements appear after coordinators approve proposals."
          >
            <DonutChartView
              title="Placement status"
              slices={pipeline}
              totalLabel="placements"
              unit="placements"
            />
          </ChartCard>
        </div>
      </section>

      <section aria-labelledby="admin-lists" className="grid gap-4 xl:grid-cols-2">
        <h2 id="admin-lists" className="sr-only">
          Programs, messaging and recent activity
        </h2>
        <Card>
          <SectionHeading
            title="Programs"
            action={<AdminLink section="programs">Manage programs</AdminLink>}
          />
          {loading ? (
            <LoadingBlock label="Loading programs" rows={3} />
          ) : !data || data.programs.length === 0 ? (
            <EmptyState compact title="No programs yet" />
          ) : (
            <ul className="divide-y divide-border">
              {data.programs
                .filter((x) => x.is_active)
                .map((x) => (
                  <li key={x.id} className="flex items-center justify-between gap-3 py-2.5 text-sm">
                    <span className="min-w-0">
                      <strong>{x.code}</strong>
                      <span className="ml-2 text-muted-foreground">{x.cluster}</span>
                    </span>
                    {noCoordinator.some((c) => c.id === x.id) ? (
                      <Pill tone="brand">No coordinator</Pill>
                    ) : (
                      <Pill tone="success">Active</Pill>
                    )}
                  </li>
                ))}
            </ul>
          )}
        </Card>
        <Card>
          <SectionHeading
            title="Recent administrative activity"
            action={<AdminLink section="audit">View audit trail</AdminLink>}
          />
          {loading ? (
            <LoadingBlock label="Loading activity" rows={3} />
          ) : !data || data.recent_activity.length === 0 ? (
            <EmptyState compact title="No recorded activity" />
          ) : (
            <ul className="divide-y divide-border">
              {data.recent_activity.map((a) => (
                <li key={a.id} className="py-2.5 text-sm">
                  <p>{formatAction(a.action)}</p>
                  <p className="text-xs text-muted-foreground" title={a.created_at}>
                    {formatDateTime(a.created_at)}
                  </p>
                </li>
              ))}
            </ul>
          )}
          <div className="mt-4 flex items-center justify-between gap-3 border-t border-border pt-4 text-sm">
            <span>
              Chat{" "}
              {data && (
                <Pill tone={data.chat_enabled ? "success" : "muted"}>
                  {data.chat_enabled ? "On" : "Off"}
                </Pill>
              )}
            </span>
            <AdminLink section="chat-settings">Chat availability</AdminLink>
          </div>
        </Card>
      </section>
    </div>
  );
}
