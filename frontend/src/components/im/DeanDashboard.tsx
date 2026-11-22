import { useEffect, useMemo, useState } from "react";
import { Link } from "@tanstack/react-router";
import { Briefcase, CircleCheck, Clock, GraduationCap, Percent, TrendingUp } from "lucide-react";
import { api } from "@/lib/api";
import { withSynthetic } from "@/lib/synthetic";
import { SERIES_COLORS, TONE_COLOR } from "@/lib/status";
import { SyntheticToggle } from "./SyntheticToggle";
import { Button, Card, PageHeader, Pill, Row, Table } from "./ui";
import { inputClass } from "./formUtils";
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
import type { Data as Analytics } from "./ProgramAnalytics";

type Dashboard = {
  stats: { label: string; value: number }[];
  programs: { id: number; code: string }[];
  generated_at: string;
};
type ReportRow = {
  id: number;
  kind: string;
  program_term_id: number;
  approved_at: string | null;
  contains_synthetic?: boolean;
};
type ReportIndex = {
  reports: { data: ReportRow[] };
  cohorts: { id: number; program: { code: string }; academic_term: { name: string } }[];
};

const BASE = "/dean";
const KIND: Record<string, string> = {
  placement: "Placements",
  progress: "Student progress",
  hours: "Internship hours",
  requirements: "Requirements",
  evaluation: "Supervisor evaluations",
  recommendation: "Recommendations",
  summary: "Program summary",
};
const linkClass = "text-sm font-semibold text-brand hover:underline";

/** Load one endpoint with its own loading and error state, so one failure never blanks the page. */
function useEndpoint<T>(path: string, revision: number) {
  const [state, setState] = useState<{ data: T | null; error: string; loading: boolean }>({
    data: null,
    error: "",
    loading: true,
  });
  useEffect(() => {
    let active = true;
    setState({ data: null, error: "", loading: true });
    api<T>(path)
      .then((data) => active && setState({ data, error: "", loading: false }))
      .catch(
        (cause: Error) => active && setState({ data: null, error: cause.message, loading: false }),
      );
    return () => {
      active = false;
    };
  }, [path, revision]);
  return state;
}

const plural = (count: number, one: string, many: string) =>
  `${count.toLocaleString("en-PH")} ${count === 1 ? one : many}`;
const rate = (numerator: number, denominator: number) =>
  denominator ? `${((numerator / denominator) * 100).toFixed(1)}%` : null;

/**
 * Dean executive dashboard. Every figure comes from the role-scoped endpoints GET /dashboard,
 * GET /program-analytics (the dean's assigned programs only) and GET /reports (approved
 * reports only); nothing is computed from downloaded record lists.
 */
export function DeanDashboard() {
  const [revision, setRevision] = useState(0);
  const [program, setProgram] = useState("");
  const [includeSynthetic, setIncludeSynthetic] = useState(false);
  const dashboard = useEndpoint<Dashboard>(
    withSynthetic(`/dashboard${program ? `?program_id=${program}` : ""}`, includeSynthetic),
    revision,
  );
  const analytics = useEndpoint<Analytics>(
    withSynthetic("/program-analytics", includeSynthetic),
    revision,
  );
  const reports = useEndpoint<ReportIndex>("/reports", revision);
  const retry = () => setRevision((v) => v + 1);
  const stat = (label: string) =>
    dashboard.data?.stats.find((s) => s.label === label)?.value ?? null;
  const code = dashboard.data?.programs.find((p) => String(p.id) === program)?.code ?? "";
  const programs = (analytics.data?.programs ?? []).filter((p) => !code || p.code === code);
  const enrollments = programs.reduce((sum, p) => sum + p.enrollments, 0);
  const placed = programs.reduce((sum, p) => sum + p.placed, 0);
  const completed = programs.reduce((sum, p) => sum + p.completed, 0);

  const actions = useMemo<ActionItem[]>(() => {
    const items: ActionItem[] = [];
    const hosts = analytics.data?.hosts ?? [];
    const expiring = hosts.reduce((n, h) => n + h.agreements_expiring_90_days, 0);
    const uncovered = hosts.filter((h) => h.is_active && h.current_agreements === 0).length;
    if (expiring)
      items.push({
        id: "expiring",
        tone: "warn",
        to: `${BASE}/hosts`,
        title: `${plural(expiring, "agreement expires", "agreements expire")} within 90 days`,
      });
    if (uncovered)
      items.push({
        id: "uncovered",
        tone: "danger",
        to: `${BASE}/hosts`,
        title: `${plural(uncovered, "active host has", "active hosts have")} no current agreement`,
        detail: "Students cannot be placed at a host without a current agreement",
      });
    const missing = analytics.data?.equity.missing_coordinates ?? 0;
    if (missing)
      items.push({
        id: "coords",
        tone: "info",
        to: `${BASE}/equity`,
        title: `${plural(missing, "placement is", "placements are")} missing saved coordinates`,
        detail: "Distance cannot be measured for these placements",
      });
    return items;
  }, [analytics.data]);

  const pipeline = [
    { label: "Pending decision", value: stat("Pending placements") ?? 0, color: TONE_COLOR.warn },
    { label: "Active", value: stat("Active placements") ?? 0, color: TONE_COLOR.info },
    { label: "Completed", value: stat("Completed placements") ?? 0, color: TONE_COLOR.success },
  ];
  const pipelineTotal = pipeline.reduce((sum, s) => sum + s.value, 0);
  const comparison = programs.map((p) => ({
    program: p.code,
    placement: p.placement_rate === null ? null : Number(p.placement_rate.toFixed(1)),
    completion: p.completion_rate === null ? null : Number(p.completion_rate.toFixed(1)),
  }));
  const cohorts = reports.data?.cohorts ?? [];
  const latest = (reports.data?.reports.data ?? []).slice(0, 5);

  return (
    <div className="space-y-8">
      <PageHeader
        title="Executive dashboard"
        subtitle="Internship participation and outcomes across your assigned programs"
        action={
          <>
            <label className="flex items-center gap-2 text-sm font-medium">
              <span className="whitespace-nowrap">Program</span>
              <select
                className={`${inputClass} w-auto`}
                value={program}
                onChange={(e) => setProgram(e.target.value)}
                disabled={!dashboard.data && !dashboard.error}
              >
                <option value="">All assigned programs</option>
                {dashboard.data?.programs.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.code}
                  </option>
                ))}
              </select>
            </label>
            <SyntheticToggle
              checked={includeSynthetic}
              onChange={setIncludeSynthetic}
              disabled={dashboard.loading}
            />
            <Button variant="outline" size="sm" onClick={retry} disabled={dashboard.loading}>
              Refresh
            </Button>
          </>
        }
      />

      <section aria-labelledby="dean-attention">
        <SectionHeading id="dean-attention" title="Needs your attention" />
        {analytics.error ? (
          <ErrorState
            title="Could not load the work queue"
            message={analytics.error}
            onRetry={retry}
          />
        ) : (
          <ActionList
            items={actions}
            loading={analytics.loading}
            emptyText="Nothing needs your attention right now."
          />
        )}
      </section>

      <section aria-labelledby="dean-kpis">
        <SectionHeading
          id="dean-kpis"
          title="At a glance"
          description={code ? `Program ${code}` : "All assigned programs"}
        />
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
          <KpiCard
            label="Enrollment records"
            value={stat("Enrollment records")}
            icon={GraduationCap}
            loading={dashboard.loading}
            hint="A student can have more than one"
          />
          <KpiCard
            label="Active placements"
            value={stat("Active placements")}
            icon={Briefcase}
            tone="info"
            loading={dashboard.loading}
          />
          <KpiCard
            label="Completed placements"
            value={stat("Completed placements")}
            icon={CircleCheck}
            tone="success"
            loading={dashboard.loading}
          />
          <KpiCard
            label="Certified hours"
            value={stat("Certified hours")}
            icon={Clock}
            loading={dashboard.loading}
            hint="Supervisor-verified only"
          />
          <KpiCard
            label="Placement rate"
            value={rate(placed, enrollments) ?? "No records"}
            icon={Percent}
            loading={analytics.loading}
            hint="Share of enrollment records"
          />
          <KpiCard
            label="Completion rate"
            value={rate(completed, enrollments) ?? "No records"}
            icon={TrendingUp}
            tone="success"
            loading={analytics.loading}
            hint="Share of enrollment records"
          />
        </div>
      </section>

      <section aria-labelledby="dean-charts" className="space-y-4">
        <SectionHeading
          id="dean-charts"
          title="Programs and placements"
          action={
            <Link to="/dean/$section" params={{ section: "performance" }} className={linkClass}>
              Program performance
            </Link>
          }
        />
        <div className="grid gap-4 xl:grid-cols-5">
          <ChartCard
            className="xl:col-span-3"
            title="Placement and completion rate by program"
            description="Share of enrollment records"
            loading={analytics.loading}
            error={analytics.error}
            onRetry={retry}
            isEmpty={comparison.length === 0 || enrollments === 0}
            emptyTitle="No enrollment records yet"
            emptyDescription="Rates appear once students are enrolled in your programs."
          >
            <BarChartView
              title="Placement and completion rate by program"
              data={comparison}
              categoryKey="program"
              categoryLabel="Program"
              valueLabel="Share of enrollments (%)"
              unit="%"
              series={[
                { key: "placement", label: "Placement rate", color: TONE_COLOR.info },
                { key: "completion", label: "Completion rate", color: TONE_COLOR.success },
              ]}
            />
          </ChartCard>
          <ChartCard
            className="xl:col-span-2"
            title="Placement status"
            description="Placement records by current status"
            loading={dashboard.loading}
            error={dashboard.error}
            onRetry={retry}
            isEmpty={pipelineTotal === 0}
            emptyTitle="No placements yet"
            emptyDescription="Placements appear here after coordinators approve proposals."
          >
            <DonutChartView
              title="Placement status"
              slices={pipeline}
              totalLabel="placements"
              unit="placements"
            />
          </ChartCard>
        </div>
        <ChartCard
          title="Straight-line distance to host"
          description="Approved and active placements by distance band"
          loading={analytics.loading}
          error={analytics.error}
          onRetry={retry}
          isEmpty={!analytics.data || analytics.data.equity.bands.every((b) => b.value === 0)}
          emptyTitle="No distances to show"
          emptyDescription="Distances need approved or active placements with saved student and host coordinates."
          footnote={
            analytics.data
              ? `${analytics.data.equity.measured_placements.toLocaleString("en-PH")} measured${analytics.data.equity.median_km !== null ? ` · median ${analytics.data.equity.median_km} km` : ""}. Straight-line distance, not travel time.`
              : undefined
          }
        >
          <BarChartView
            title="Straight-line distance to host"
            data={(analytics.data?.equity.bands ?? []).map((b) => ({
              band: b.label,
              placements: b.value,
            }))}
            categoryKey="band"
            categoryLabel="Distance band"
            valueLabel="Placements"
            unit="placements"
            height={220}
            series={[{ key: "placements", label: "Placements", color: SERIES_COLORS[0]! }]}
          />
        </ChartCard>
      </section>

      <section aria-labelledby="dean-reports">
        <SectionHeading
          id="dean-reports"
          title="Approved reports"
          description="Latest coordinator-approved snapshots in your programs"
          action={
            <Link to="/dean/$section" params={{ section: "accreditation" }} className={linkClass}>
              All approved reports
            </Link>
          }
        />
        <Card>
          {reports.error ? (
            <ErrorState title="Could not load reports" message={reports.error} onRetry={retry} />
          ) : reports.loading ? (
            <LoadingBlock label="Loading approved reports" rows={3} />
          ) : latest.length === 0 ? (
            <EmptyState
              compact
              title="No approved reports yet"
              description="Reports appear here after a practicum coordinator approves them."
            />
          ) : (
            <Table head={["Report", "Program and term", "Approved"]}>
              {latest.map((r) => {
                const cohort = cohorts.find((c) => c.id === r.program_term_id);
                return (
                  <Row key={r.id}>
                    <td className="font-medium">
                      {KIND[r.kind] ?? r.kind} · #{r.id}{" "}
                      {r.contains_synthetic && <Pill tone="warn">Contains test data</Pill>}
                    </td>
                    <td className="text-muted-foreground">
                      {cohort
                        ? `${cohort.program.code} · ${cohort.academic_term.name}`
                        : "Assigned program"}
                    </td>
                    <td className="text-muted-foreground">
                      {r.approved_at
                        ? new Date(r.approved_at).toLocaleDateString("en-PH", {
                            timeZone: "Asia/Manila",
                            dateStyle: "medium",
                          })
                        : "Not approved"}
                    </td>
                  </Row>
                );
              })}
            </Table>
          )}
        </Card>
      </section>
    </div>
  );
}
