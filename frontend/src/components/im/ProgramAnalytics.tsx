import { useEffect, useMemo, useState } from "react";
import { Building2, FileCheck2, MapPinOff, Route, Ruler, TriangleAlert } from "lucide-react";
import { api } from "@/lib/api";
import { withSynthetic } from "@/lib/synthetic";
import { SERIES_COLORS, TONE_COLOR } from "@/lib/status";
import { AccessibilityMap } from "./AccessibilityMap";
import { SyntheticToggle } from "./SyntheticToggle";
import { Button, Card, PageHeader, Pill, Row, Table } from "./ui";
import { inputClass } from "./formUtils";
import { EmptyState, ErrorState, KpiCard, SectionHeading } from "./ds";
import { BarChartView, ChartCard, DonutChartView } from "./charts";
import { WeeklyActivity, type Week } from "./WeeklyActivity";

type Band = { label: string; value: number };
type Summary = {
  enrollments: number;
  placed: number;
  completed: number;
  placement_rate: number | null;
  completion_rate: number | null;
};
export type Data = {
  programs: (Summary & {
    code: string;
    name: string;
    evaluation_percent: number | null;
    evaluation_count: number;
  })[];
  hosts: {
    id: number;
    name: string;
    industry: string;
    city: string;
    is_active: boolean;
    current_agreements: number;
    agreements_expiring_90_days: number;
  }[];
  equity: {
    median_km: number | null;
    measured_placements: number;
    missing_coordinates: number;
    over_20_km: number;
    bands: Band[];
  };
  similarity_bands: Band[];
  competency_demand: Band[];
  weekly: Week[];
  terms: (Summary & { term: string })[];
  generated_at: string;
};
const percent = (v: number | null) => (v === null ? "No records" : `${v.toFixed(1)}%`);

const TITLES = {
  performance: "Program performance",
  hosts: "Host establishments",
  equity: "Placement equity",
  analytics: "Analytics",
};
const SUBTITLES = {
  performance: "Placement, completion and evaluation outcomes for your assigned programs.",
  hosts: "Host establishments and their agreements within your assigned programs.",
  equity: "How far placed students travel to their hosts, using straight-line distance.",
  analytics: "Recommendation scores, outcomes by term and the skills employers ask for.",
};

export function ProgramAnalytics({
  section,
}: {
  section: "performance" | "hosts" | "equity" | "analytics";
}) {
  const [data, setData] = useState<Data | null>(null),
    [error, setError] = useState("");
  const [revision, setRevision] = useState(0),
    [program, setProgram] = useState(""),
    [includeSynthetic, setIncludeSynthetic] = useState(false);
  useEffect(() => {
    let active = true;
    setData(null);
    setError("");
    api<Data>(withSynthetic("/program-analytics", includeSynthetic))
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
  const loading = !data && !error;
  const retry = () => setRevision((v) => v + 1);
  const [hostSearch, setHostSearch] = useState("");
  const [hostFilter, setHostFilter] = useState("all");
  const visibleHosts = useMemo(
    () =>
      data?.hosts.filter(
        (h) =>
          `${h.name} ${h.industry} ${h.city}`
            .toLowerCase()
            .includes(hostSearch.trim().toLowerCase()) &&
          (hostFilter === "all" ||
            (hostFilter === "expiring"
              ? h.agreements_expiring_90_days > 0
              : hostFilter === "uncovered"
                ? h.current_agreements === 0
                : h.is_active)),
      ) ?? [],
    [data, hostSearch, hostFilter],
  );
  const programs = (data?.programs ?? []).filter((p) => !program || p.code === program);
  const hostCoverage = useMemo(() => {
    const hosts = data?.hosts ?? [];
    const none = hosts.filter((h) => h.current_agreements === 0).length;
    const expiring = hosts.filter(
      (h) => h.current_agreements > 0 && h.agreements_expiring_90_days > 0,
    ).length;
    return [
      {
        label: "Current agreement",
        value: hosts.length - none - expiring,
        color: TONE_COLOR.success,
      },
      { label: "Expiring within 90 days", value: expiring, color: TONE_COLOR.warn },
      { label: "No current agreement", value: none, color: TONE_COLOR.brand },
    ];
  }, [data]);

  return (
    <div className="space-y-8">
      <PageHeader
        title={TITLES[section]}
        subtitle={SUBTITLES[section]}
        action={
          <>
            <SyntheticToggle
              checked={includeSynthetic}
              onChange={setIncludeSynthetic}
              disabled={loading}
            />
            <Button variant="outline" size="sm" onClick={retry} disabled={loading}>
              Refresh
            </Button>
          </>
        }
      />
      {error && (
        <ErrorState title="Could not load program records" message={error} onRetry={retry} />
      )}

      {section === "performance" && (
        <>
          <div className="flex flex-wrap items-center gap-3">
            <label className="flex items-center gap-2 text-sm font-medium">
              Program
              <select
                aria-label="Program"
                className={`${inputClass} w-auto`}
                value={program}
                onChange={(e) => setProgram(e.target.value)}
                disabled={!data}
              >
                <option value="">All assigned programs</option>
                {data?.programs.map((p) => (
                  <option key={p.code} value={p.code}>
                    {p.code}
                  </option>
                ))}
              </select>
            </label>
            {program && (
              <Button variant="ghost" size="sm" onClick={() => setProgram("")}>
                Clear filter
              </Button>
            )}
          </div>
          <div className="grid gap-4 xl:grid-cols-2">
            <ChartCard
              title="Placement and completion rate by program"
              description="Share of enrollment records"
              loading={loading}
              error={error}
              onRetry={retry}
              isEmpty={programs.length === 0}
              emptyTitle="No programs assigned"
              emptyDescription="Programs appear here once an administrator assigns them to you."
              footnote="Placed includes approved, active and completed placements. A blank bar means the program has no enrollment records."
            >
              <BarChartView
                title="Placement and completion rate by program"
                data={programs.map((p) => ({
                  program: p.code,
                  placement: p.placement_rate === null ? null : Number(p.placement_rate.toFixed(1)),
                  completion:
                    p.completion_rate === null ? null : Number(p.completion_rate.toFixed(1)),
                }))}
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
              title="Enrollment records by outcome"
              description="Enrolled, placed and completed"
              loading={loading}
              error={error}
              onRetry={retry}
              isEmpty={programs.every((p) => p.enrollments === 0)}
              emptyTitle="No enrollment records yet"
              emptyDescription="Counts appear once students are enrolled in your programs."
              footnote="A student can have more than one enrollment, so counts are enrollment records, not unique people."
            >
              <BarChartView
                title="Enrollment records by outcome"
                data={programs.map((p) => ({
                  program: p.code,
                  enrolled: p.enrollments,
                  placed: p.placed,
                  completed: p.completed,
                }))}
                categoryKey="program"
                categoryLabel="Program"
                valueLabel="Enrollment records"
                unit="records"
                series={[
                  { key: "enrolled", label: "Enrolled", color: SERIES_COLORS[0]! },
                  { key: "placed", label: "Placed", color: TONE_COLOR.info },
                  { key: "completed", label: "Completed", color: TONE_COLOR.success },
                ]}
              />
            </ChartCard>
          </div>
          <Card>
            <SectionHeading
              title="Enrollment outcomes"
              description="Evaluation averages include only submitted, scored evaluations."
            />
            {loading ? null : programs.length === 0 ? (
              <EmptyState compact title="No programs assigned" />
            ) : (
              <Table
                head={[
                  "Program",
                  "Enrollments",
                  "Placed",
                  "Completed",
                  "Placement rate",
                  "Completion rate",
                  "Evaluation average",
                  "Evaluations",
                ]}
              >
                {programs.map((p) => (
                  <Row key={p.code}>
                    <td className="font-medium">
                      {p.code} · {p.name}
                    </td>
                    <td className="tabular-nums">{p.enrollments}</td>
                    <td className="tabular-nums">{p.placed}</td>
                    <td className="tabular-nums">{p.completed}</td>
                    <td>{percent(p.placement_rate)}</td>
                    <td>{percent(p.completion_rate)}</td>
                    <td>{percent(p.evaluation_percent)}</td>
                    <td className="tabular-nums">{p.evaluation_count}</td>
                  </Row>
                ))}
              </Table>
            )}
          </Card>
        </>
      )}

      {section === "hosts" && (
        <>
          <div className="grid gap-4 sm:grid-cols-3">
            <KpiCard
              label="Active host establishments"
              value={data ? data.hosts.filter((h) => h.is_active).length : null}
              icon={Building2}
              loading={loading}
            />
            <KpiCard
              label="Current agreements"
              value={data ? data.hosts.reduce((n, h) => n + h.current_agreements, 0) : null}
              icon={FileCheck2}
              tone="success"
              loading={loading}
              hint="Scoped to your programs"
            />
            <KpiCard
              label="Agreements expiring within 90 days"
              value={
                data ? data.hosts.reduce((n, h) => n + h.agreements_expiring_90_days, 0) : null
              }
              icon={TriangleAlert}
              tone="warn"
              loading={loading}
            />
          </div>
          <div className="grid gap-4 xl:grid-cols-5">
            <ChartCard
              className="xl:col-span-2"
              title="Agreement coverage"
              description="Host establishments by agreement status"
              loading={loading}
              error={error}
              onRetry={retry}
              isEmpty={!data?.hosts.length}
              emptyTitle="No host establishments yet"
              emptyDescription="Hosts appear once they are linked to your programs."
            >
              <DonutChartView
                title="Agreement coverage"
                slices={hostCoverage}
                totalLabel="hosts"
                unit="hosts"
              />
            </ChartCard>
            <Card className="xl:col-span-3">
              <SectionHeading
                title="Establishments"
                description={
                  data ? `${visibleHosts.length} of ${data.hosts.length} hosts` : undefined
                }
              />
              <div className="mb-4 flex flex-wrap items-center gap-3">
                <input
                  type="search"
                  aria-label="Search hosts"
                  placeholder="Search name, industry or city"
                  className={`${inputClass} max-w-xs`}
                  value={hostSearch}
                  onChange={(e) => setHostSearch(e.target.value)}
                />
                <select
                  aria-label="Host coverage"
                  className={`${inputClass} w-auto`}
                  value={hostFilter}
                  onChange={(e) => setHostFilter(e.target.value)}
                >
                  <option value="all">All hosts</option>
                  <option value="active">Active hosts</option>
                  <option value="expiring">Agreements expiring within 90 days</option>
                  <option value="uncovered">No current agreement</option>
                </select>
                {(hostSearch || hostFilter !== "all") && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => {
                      setHostSearch("");
                      setHostFilter("all");
                    }}
                  >
                    Clear filters
                  </Button>
                )}
              </div>
              {loading ? null : visibleHosts.length === 0 ? (
                <EmptyState compact title="No hosts match these filters" />
              ) : (
                <Table
                  head={["Establishment", "Industry", "City", "Status", "Agreements", "Expiring"]}
                >
                  {visibleHosts.map((h) => (
                    <Row key={h.id}>
                      <td className="font-medium">{h.name}</td>
                      <td className="text-muted-foreground">{h.industry}</td>
                      <td className="text-muted-foreground">{h.city}</td>
                      <td>
                        <Pill tone={h.is_active ? "success" : "muted"}>
                          {h.is_active ? "Active" : "Inactive"}
                        </Pill>
                      </td>
                      <td className="tabular-nums">
                        {h.current_agreements === 0 ? (
                          <Pill tone="brand">None current</Pill>
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
          </div>
        </>
      )}

      {section === "equity" && (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <KpiCard
              label="Median straight-line distance"
              value={
                data?.equity.median_km === null || !data
                  ? "Not recorded"
                  : `${data.equity.median_km} km`
              }
              icon={Route}
              loading={loading}
            />
            <KpiCard
              label="Measured current placements"
              value={data?.equity.measured_placements ?? null}
              icon={Ruler}
              loading={loading}
              hint="Approved and active, with saved coordinates"
            />
            <KpiCard
              label="Placements over 20 km"
              value={data?.equity.over_20_km ?? null}
              icon={TriangleAlert}
              tone={data && data.equity.over_20_km > 0 ? "warn" : "muted"}
              loading={loading}
            />
            <KpiCard
              label="Placements missing coordinates"
              value={data?.equity.missing_coordinates ?? null}
              icon={MapPinOff}
              tone={data && data.equity.missing_coordinates > 0 ? "warn" : "muted"}
              loading={loading}
            />
          </div>
          <ChartCard
            title="Current placements by distance band"
            description="Approved and active placements"
            loading={loading}
            error={error}
            onRetry={retry}
            isEmpty={!data || data.equity.bands.every((b) => b.value === 0)}
            emptyTitle="No distances to show"
            emptyDescription="Distances need approved or active placements with saved student and host coordinates."
            footnote="Distances use stored coordinates and the Haversine formula. They do not measure road distance, travel time or fairness."
          >
            <BarChartView
              title="Current placements by distance band"
              data={(data?.equity.bands ?? []).map((b) => ({ band: b.label, placements: b.value }))}
              categoryKey="band"
              categoryLabel="Distance band"
              valueLabel="Placements"
              unit="placements"
              series={[{ key: "placements", label: "Placements", color: SERIES_COLORS[0]! }]}
            />
          </ChartCard>
          <AccessibilityMap note="Host locations within your assigned programs. Student addresses are not exposed in this view." />
        </>
      )}

      {section === "analytics" && (
        <WeeklyActivity weeks={data?.weekly} loading={loading} error={error} onRetry={retry} />
      )}
      {section === "analytics" && (
        <div className="mt-4 grid gap-4 xl:grid-cols-2">
          <ChartCard
            title="Stored recommendations by similarity score"
            description="Retained recommendation generations, grouped by raw cosine similarity score"
            loading={loading}
            error={error}
            onRetry={retry}
            isEmpty={!data || data.similarity_bands.every((b) => b.value === 0)}
            emptyTitle="No stored recommendations yet"
            emptyDescription="Scores appear after recommendations are generated for students."
            footnote="Similarity scores are not placement probabilities."
          >
            <BarChartView
              title="Stored recommendations by similarity score"
              data={(data?.similarity_bands ?? []).map((b) => ({
                band: b.label,
                recommendations: b.value,
              }))}
              categoryKey="band"
              categoryLabel="Similarity score"
              valueLabel="Recommendations"
              unit="recommendations"
              series={[
                { key: "recommendations", label: "Recommendations", color: SERIES_COLORS[0]! },
              ]}
            />
          </ChartCard>
          <ChartCard
            title="Enrollment outcomes by term"
            description="Enrollment records, placed and completed"
            loading={loading}
            error={error}
            onRetry={retry}
            isEmpty={!data || data.terms.length === 0}
            emptyTitle="No recorded terms"
            emptyDescription="Terms appear once students are enrolled."
            footnote="A student can have more than one enrollment, so counts are enrollment records."
          >
            <BarChartView
              title="Enrollment outcomes by term"
              data={(data?.terms ?? []).map((t) => ({
                term: t.term,
                enrolled: t.enrollments,
                placed: t.placed,
                completed: t.completed,
              }))}
              categoryKey="term"
              categoryLabel="Term"
              valueLabel="Enrollment records"
              unit="records"
              series={[
                { key: "enrolled", label: "Enrolled", color: SERIES_COLORS[0]! },
                { key: "placed", label: "Placed", color: TONE_COLOR.info },
                { key: "completed", label: "Completed", color: TONE_COLOR.success },
              ]}
            />
          </ChartCard>
          <ChartCard
            className="xl:col-span-2"
            title="Skills requested by published opportunities"
            description="Number of published opportunities requiring each competency (top 10; a competency can overlap with others)"
            loading={loading}
            error={error}
            onRetry={retry}
            isEmpty={!data || data.competency_demand.length === 0}
            emptyTitle="No published opportunities list competencies"
            emptyDescription="Competencies appear when opportunities are published with required skills."
          >
            <BarChartView
              title="Skills requested by published opportunities"
              data={(data?.competency_demand ?? [])
                .slice(0, 10)
                .map((b) => ({ competency: b.label, opportunities: b.value }))}
              categoryKey="competency"
              categoryLabel="Competency"
              valueLabel="Opportunities"
              unit="opportunities"
              layout="horizontal"
              height={Math.max(180, Math.min(10, data?.competency_demand.length ?? 0) * 34)}
              series={[{ key: "opportunities", label: "Opportunities", color: SERIES_COLORS[0]! }]}
            />
          </ChartCard>
        </div>
      )}

      {data && (
        <p className="text-xs text-muted-foreground">
          Retrieved{" "}
          {new Date(data.generated_at).toLocaleString("en-PH", {
            timeZone: "Asia/Manila",
            dateStyle: "medium",
            timeStyle: "short",
          })}
        </p>
      )}
    </div>
  );
}
