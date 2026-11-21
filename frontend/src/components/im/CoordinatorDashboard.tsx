import { useEffect, useMemo, useState } from "react";
import { Link } from "@tanstack/react-router";
import { Briefcase, CircleCheck, Clock, GraduationCap, RotateCw, Ticket } from "lucide-react";
import { api } from "@/lib/api";
import { withSynthetic } from "@/lib/synthetic";
import { TONE_COLOR, SERIES_COLORS } from "@/lib/status";
import { useSessionUser } from "./SessionGuard";
import { SyntheticToggle } from "./SyntheticToggle";
import { Button, Card, PageHeader, Pill, Row, Table } from "./ui";
import {
  ActionList,
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
  generated_at: string;
};

const BASE = "/coordinator";

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

/**
 * Practicum Coordinator dashboard. Every figure comes from the role-scoped endpoints
 * GET /dashboard and GET /program-analytics (the coordinator's assigned program only);
 * nothing is computed from downloaded record lists and nothing is hard-coded.
 */
export function CoordinatorDashboard() {
  const user = useSessionUser();
  const [revision, setRevision] = useState(0);
  const [includeSynthetic, setIncludeSynthetic] = useState(false);
  const dashboard = useEndpoint<Dashboard>(withSynthetic("/dashboard", includeSynthetic), revision);
  const analytics = useEndpoint<Analytics>(
    withSynthetic("/program-analytics", includeSynthetic),
    revision,
  );
  const stat = (label: string) =>
    dashboard.data?.stats.find((s) => s.label === label)?.value ?? null;

  const expiring =
    analytics.data?.hosts.reduce((sum, h) => sum + h.agreements_expiring_90_days, 0) ?? 0;
  const actions = useMemo<ActionItem[]>(() => {
    if (!dashboard.data) return [];
    const n = (label: string) => dashboard.data?.stats.find((s) => s.label === label)?.value ?? 0;
    const plural = (count: number, one: string, many: string) =>
      `${count.toLocaleString("en-PH")} ${count === 1 ? one : many}`;
    const items: ActionItem[] = [];
    const flagged = n("Students needing attention (open monitoring flags)");
    if (flagged)
      items.push({
        id: "flags",
        tone: "danger",
        to: `${BASE}/progress`,
        title: `${plural(flagged, "student has", "students have")} an open monitoring flag`,
        detail: "Behind schedule, missing logs or at risk of not finishing hours",
      });
    const pending = n("Pending placements");
    if (pending)
      items.push({
        id: "pending",
        tone: "warn",
        to: `${BASE}/approvals`,
        title: `${plural(pending, "placement is", "placements are")} waiting for your decision`,
      });
    const submissions = n("Submissions awaiting review");
    if (submissions)
      items.push({
        id: "submissions",
        tone: "warn",
        to: `${BASE}/requirements`,
        title: `${plural(submissions, "requirement submission", "requirement submissions")} awaiting review`,
      });
    const incomplete = n("Awaiting students with incomplete requirements");
    if (incomplete)
      items.push({
        id: "incomplete",
        tone: "warn",
        to: `${BASE}/requirements`,
        title: `${plural(incomplete, "unplaced student has", "unplaced students have")} incomplete requirements`,
      });
    const awaiting = n("Students awaiting placement");
    if (awaiting)
      items.push({
        id: "awaiting",
        tone: "info",
        to: `${BASE}/recommendations`,
        title: `${plural(awaiting, "student is", "students are")} awaiting placement`,
      });
    if (expiring)
      items.push({
        id: "moa",
        tone: "warn",
        to: `${BASE}/moa`,
        title: `${plural(expiring, "agreement expires", "agreements expire")} within 90 days`,
      });
    return items;
  }, [dashboard.data, expiring]);

  const pipeline = [
    { label: "Pending decision", value: stat("Pending placements") ?? 0, color: TONE_COLOR.warn },
    { label: "Active", value: stat("Active placements") ?? 0, color: TONE_COLOR.info },
    { label: "Completed", value: stat("Completed placements") ?? 0, color: TONE_COLOR.success },
  ];
  const pipelineTotal = pipeline.reduce((sum, s) => sum + s.value, 0);
  const distance = (analytics.data?.equity.bands ?? []).map((b) => ({
    band: b.label,
    placements: b.value,
  }));
  const demand = (analytics.data?.competency_demand ?? [])
    .slice(0, 8)
    .map((b) => ({ competency: b.label, opportunities: b.value }));
  const hosts = [...(analytics.data?.hosts ?? [])]
    .sort(
      (a, b) =>
        b.agreements_expiring_90_days - a.agreements_expiring_90_days ||
        a.current_agreements - b.current_agreements,
    )
    .slice(0, 6);
  const updated = dashboard.data
    ? new Date(dashboard.data.generated_at).toLocaleTimeString("en-PH", {
        timeZone: "Asia/Manila",
        hour: "numeric",
        minute: "2-digit",
      })
    : null;

  return (
    <div className="space-y-8">
      <PageHeader
        title="Dashboard"
        subtitle={`${user.program_label ?? "Your assigned program"}${updated ? ` · updated ${updated}` : ""}`}
        action={
          <>
            <SyntheticToggle
              checked={includeSynthetic}
              onChange={setIncludeSynthetic}
              disabled={dashboard.loading}
            />
            <Button
              variant="outline"
              size="sm"
              onClick={() => setRevision((v) => v + 1)}
              disabled={dashboard.loading}
            >
              <RotateCw size={14} aria-hidden="true" />
              Refresh
            </Button>
          </>
        }
      />

      <section aria-labelledby="attention-heading">
        <SectionHeading id="attention-heading" title="Needs your attention" />
        {dashboard.error ? (
          <ErrorState
            title="Could not load your work queue"
            message={dashboard.error}
            onRetry={() => setRevision((v) => v + 1)}
          />
        ) : (
          <ActionList
            items={actions}
            loading={dashboard.loading}
            emptyText="Nothing needs your attention right now."
          />
        )}
      </section>

      <section aria-labelledby="kpi-heading">
        <SectionHeading
          id="kpi-heading"
          title="Program at a glance"
          description="Counts for your assigned program"
        />
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
          <KpiCard
            label="Enrollment records"
            value={stat("Enrollment records")}
            icon={GraduationCap}
            loading={dashboard.loading}
            to={`${BASE}/students`}
            hint="A student can have more than one enrollment"
          />
          <KpiCard
            label="Active placements"
            value={stat("Active placements")}
            icon={Briefcase}
            tone="info"
            loading={dashboard.loading}
            to={`${BASE}/progress`}
          />
          <KpiCard
            label="Completed placements"
            value={stat("Completed placements")}
            icon={CircleCheck}
            tone="success"
            loading={dashboard.loading}
            to={`${BASE}/progress`}
          />
          <KpiCard
            label="Certified hours"
            value={stat("Certified hours")}
            icon={Clock}
            loading={dashboard.loading}
            hint="Supervisor-verified time logs only"
            to={`${BASE}/progress`}
          />
          <KpiCard
            label="Open placement slots"
            value={stat("Available placement slots")}
            icon={Ticket}
            loading={dashboard.loading}
            hint="Published opportunities with a valid MOA"
            to={`${BASE}/opportunities`}
          />
        </div>
      </section>

      <section aria-labelledby="charts-heading" className="space-y-4">
        <SectionHeading id="charts-heading" title="Placements and matching" />
        <div className="grid gap-4 xl:grid-cols-2">
          <ChartCard
            title="Placement status"
            description="Placement records by current status"
            loading={dashboard.loading}
            error={dashboard.error}
            onRetry={() => setRevision((v) => v + 1)}
            isEmpty={pipelineTotal === 0}
            emptyTitle="No placements yet"
            emptyDescription="Placements appear here after you approve a proposal."
          >
            <DonutChartView
              title="Placement status"
              slices={pipeline}
              totalLabel="placements"
              unit="placements"
            />
          </ChartCard>
          <ChartCard
            title="Straight-line distance to host"
            description="Approved and active placements by distance band"
            loading={analytics.loading}
            error={analytics.error}
            onRetry={() => setRevision((v) => v + 1)}
            isEmpty={distance.every((d) => d.placements === 0)}
            emptyTitle="No distances to show"
            emptyDescription="Distances need an approved or active placement with saved student and host coordinates."
            footnote={
              analytics.data
                ? `${analytics.data.equity.measured_placements.toLocaleString("en-PH")} measured · ${analytics.data.equity.missing_coordinates.toLocaleString("en-PH")} missing coordinates${analytics.data.equity.median_km !== null ? ` · median ${analytics.data.equity.median_km} km` : ""}`
                : undefined
            }
          >
            <BarChartView
              title="Straight-line distance to host"
              data={distance}
              categoryKey="band"
              categoryLabel="Distance band"
              valueLabel="Placements"
              unit="placements"
              series={[{ key: "placements", label: "Placements", color: SERIES_COLORS[0]! }]}
            />
          </ChartCard>
        </div>
        <ChartCard
          title="Skills requested by open opportunities"
          description="Published opportunities in your program that list each competency (top 8)"
          loading={analytics.loading}
          error={analytics.error}
          onRetry={() => setRevision((v) => v + 1)}
          isEmpty={demand.length === 0}
          emptyTitle="No published opportunities list competencies"
          emptyDescription="Competencies appear when opportunities are published with required skills."
        >
          <BarChartView
            title="Skills requested by open opportunities"
            data={demand}
            categoryKey="competency"
            categoryLabel="Competency"
            valueLabel="Opportunities"
            unit="opportunities"
            layout="horizontal"
            height={Math.max(180, demand.length * 34)}
            series={[{ key: "opportunities", label: "Opportunities", color: SERIES_COLORS[0]! }]}
          />
        </ChartCard>
      </section>

      <section aria-labelledby="hosts-heading">
        <SectionHeading
          id="hosts-heading"
          title="Host agreements"
          description="Hosts with agreements expiring soonest first"
          action={
            <Link
              to="/coordinator/$section"
              params={{ section: "hosts" }}
              className="text-sm font-semibold text-brand hover:underline"
            >
              All host establishments
            </Link>
          }
        />
        <Card>
          {analytics.error ? (
            <ErrorState
              title="Could not load host agreements"
              message={analytics.error}
              onRetry={() => setRevision((v) => v + 1)}
            />
          ) : analytics.loading ? (
            <LoadingBlock label="Loading host agreements" rows={4} />
          ) : hosts.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No host establishments are linked to your program yet.
            </p>
          ) : (
            <Table
              head={[
                "Host establishment",
                "City",
                "Status",
                "Current agreements",
                "Expiring within 90 days",
              ]}
            >
              {hosts.map((h) => (
                <Row key={h.id}>
                  <td className="font-medium">{h.name}</td>
                  <td className="text-muted-foreground">{h.city || "Not recorded"}</td>
                  <td>
                    <Pill tone={h.is_active ? "success" : "muted"}>
                      {h.is_active ? "Active" : "Inactive"}
                    </Pill>
                  </td>
                  <td className="tabular-nums">
                    {h.current_agreements === 0 ? (
                      <Pill tone="danger">None</Pill>
                    ) : (
                      h.current_agreements
                    )}
                  </td>
                  <td className="tabular-nums">
                    {h.agreements_expiring_90_days > 0 ? (
                      <Pill tone="warn">{String(h.agreements_expiring_90_days)}</Pill>
                    ) : (
                      0
                    )}
                  </td>
                </Row>
              ))}
            </Table>
          )}
        </Card>
      </section>
    </div>
  );
}
