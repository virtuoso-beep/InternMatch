import { useEffect, useMemo, useState } from "react";
import { Link } from "@tanstack/react-router";
import { Briefcase, CircleCheck, ClipboardCheck, Clock, Hourglass, RotateCw } from "lucide-react";
import { api } from "@/lib/api";
import { TONE_COLOR } from "@/lib/status";
import { useSessionUser } from "./SessionGuard";
import { Button, Card, PageHeader, Pill, Row, Table, statusTone } from "./ui";
import {
  ActionList,
  EmptyState,
  ErrorState,
  KpiCard,
  LoadingBlock,
  SectionHeading,
  type ActionItem,
} from "./ds";
import { ChartCard, DonutChartView } from "./charts";
import type { Placement } from "./MonitoringWorkspace";

type Dashboard = {
  stats: { label: string; value: number }[];
  generated_at: string;
};

const BASE = "/supervisor";
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
const day = (value: string | null) =>
  value
    ? new Date(value).toLocaleDateString("en-PH", { timeZone: "Asia/Manila", dateStyle: "medium" })
    : "Not set";

/**
 * Host supervisor dashboard. Counts come from the role-scoped GET /dashboard (the supervisor's own
 * interns only) and the intern list from GET /placements (same scoping, paginated by the server).
 */
export function SupervisorDashboard() {
  const user = useSessionUser();
  const [revision, setRevision] = useState(0);
  const dashboard = useEndpoint<Dashboard>("/dashboard", revision);
  const placements = useEndpoint<{ data: Placement[] }>("/placements", revision);
  const stat = (label: string) =>
    dashboard.data?.stats.find((s) => s.label === label)?.value ?? null;

  const actions = useMemo<ActionItem[]>(() => {
    if (!dashboard.data) return [];
    const n = (label: string) => dashboard.data?.stats.find((s) => s.label === label)?.value ?? 0;
    const items: ActionItem[] = [];
    const logs = n("Time logs awaiting verification");
    if (logs)
      items.push({
        id: "logs",
        tone: "warn",
        to: `${BASE}/attendance`,
        title: `${plural(logs, "time log is", "time logs are")} waiting for your verification`,
        detail: "Interns' hours count only after you certify them",
      });
    const active = n("Active placements");
    const evaluations = n("Submitted evaluations");
    if (active > 0 && evaluations === 0)
      items.push({
        id: "evaluations",
        tone: "info",
        to: `${BASE}/evaluations`,
        title: "No evaluation has been submitted yet",
        detail: `${plural(active, "intern is", "interns are")} currently on active placement`,
      });
    return items;
  }, [dashboard.data]);

  const pipeline = [
    { label: "Pending", value: stat("Pending placements") ?? 0, color: TONE_COLOR.warn },
    { label: "Active", value: stat("Active placements") ?? 0, color: TONE_COLOR.info },
    { label: "Completed", value: stat("Completed placements") ?? 0, color: TONE_COLOR.success },
  ];
  const pipelineTotal = pipeline.reduce((sum, s) => sum + s.value, 0);
  const interns = (placements.data?.data ?? []).slice(0, 8);
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
        subtitle={`${user.name}${updated ? ` · updated ${updated}` : ""}`}
        action={
          <Button
            variant="outline"
            size="sm"
            onClick={() => setRevision((v) => v + 1)}
            disabled={dashboard.loading}
          >
            <RotateCw size={14} aria-hidden="true" />
            Refresh
          </Button>
        }
      />

      <section aria-labelledby="sup-attention">
        <SectionHeading id="sup-attention" title="Needs your attention" />
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

      <section aria-labelledby="sup-kpis">
        <SectionHeading
          id="sup-kpis"
          title="Your interns at a glance"
          description="Interns assigned to you"
        />
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
          <KpiCard
            label="Active interns"
            value={stat("Active placements")}
            icon={Briefcase}
            tone="info"
            loading={dashboard.loading}
            to={`${BASE}/interns`}
          />
          <KpiCard
            label="Completed placements"
            value={stat("Completed placements")}
            icon={CircleCheck}
            tone="success"
            loading={dashboard.loading}
            to={`${BASE}/interns`}
          />
          <KpiCard
            label="Time logs awaiting verification"
            value={stat("Time logs awaiting verification")}
            icon={Hourglass}
            tone="warn"
            loading={dashboard.loading}
            to={`${BASE}/attendance`}
          />
          <KpiCard
            label="Certified hours"
            value={stat("Certified hours")}
            icon={Clock}
            hint="Verified time logs only"
            loading={dashboard.loading}
            to={`${BASE}/attendance`}
          />
          <KpiCard
            label="Submitted evaluations"
            value={stat("Submitted evaluations")}
            icon={ClipboardCheck}
            loading={dashboard.loading}
            to={`${BASE}/evaluations`}
          />
        </div>
      </section>

      <section aria-labelledby="sup-chart" className="grid gap-4 xl:grid-cols-5">
        <h2 id="sup-chart" className="sr-only">
          Placement status and intern list
        </h2>
        <ChartCard
          className="xl:col-span-2"
          title="Placement status"
          description="Your interns' placements by status"
          loading={dashboard.loading}
          error={dashboard.error}
          onRetry={() => setRevision((v) => v + 1)}
          isEmpty={pipelineTotal === 0}
          emptyTitle="No interns assigned yet"
          emptyDescription="Interns appear here after a coordinator places them at your establishment."
        >
          <DonutChartView
            title="Placement status"
            slices={pipeline}
            totalLabel="placements"
            unit="placements"
          />
        </ChartCard>

        <Card className="xl:col-span-3">
          <SectionHeading
            title="Assigned interns"
            description="Most recent placements"
            action={
              <Link to="/supervisor/$section" params={{ section: "interns" }} className={linkClass}>
                All assigned interns
              </Link>
            }
          />
          {placements.error ? (
            <ErrorState
              title="Could not load interns"
              message={placements.error}
              onRetry={() => setRevision((v) => v + 1)}
            />
          ) : placements.loading ? (
            <LoadingBlock label="Loading assigned interns" rows={4} />
          ) : interns.length === 0 ? (
            <EmptyState compact title="No interns are assigned to you yet" />
          ) : (
            <Table head={["Intern", "Program", "Position", "Status", "Dates"]}>
              {interns.map((p) => (
                <Row key={p.id}>
                  <td className="font-medium">{p.student_enrollment.student.user.name}</td>
                  <td>{p.student_enrollment.program_term.program.code}</td>
                  <td className="text-muted-foreground">{p.opportunity.title}</td>
                  <td>
                    <Pill tone={statusTone(p.status)}>{p.status}</Pill>
                  </td>
                  <td className="text-xs whitespace-nowrap text-muted-foreground">
                    {day(p.starts_on)} to {day(p.ends_on)}
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
