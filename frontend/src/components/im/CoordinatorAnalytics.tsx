import { useEffect, useState } from "react";
import { ClipboardCheck, MapPinOff, Percent, Route, TrendingUp } from "lucide-react";
import { api } from "@/lib/api";
import { withSynthetic } from "@/lib/synthetic";
import { SERIES_COLORS, TONE_COLOR } from "@/lib/status";
import { Button, PageHeader } from "./ui";
import { ErrorState, KpiCard, SectionHeading } from "./ds";
import { BarChartView, ChartCard } from "./charts";
import { ReportWorkspace } from "./ReportWorkspace";
import { WeeklyActivity } from "./WeeklyActivity";
import { SyntheticToggle } from "./SyntheticToggle";
import type { Data } from "./ProgramAnalytics";

export function CoordinatorAnalytics() {
  const [data, setData] = useState<Data | null>(null),
    [error, setError] = useState("");
  const [revision, setRevision] = useState(0);
  const [includeSynthetic, setIncludeSynthetic] = useState(false);
  useEffect(() => {
    let active = true;
    setError("");
    setData(null);
    api<Data>(withSynthetic("/program-analytics", includeSynthetic))
      .then((result) => {
        if (active) setData(result);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [revision, includeSynthetic]);
  const loading = !data && !error;
  const retry = () => setRevision((v) => v + 1);
  const total = data?.programs.reduce((sum, p) => sum + p.enrollments, 0) ?? 0;
  const placed = data?.programs.reduce((sum, p) => sum + p.placed, 0) ?? 0;
  const completed = data?.programs.reduce((sum, p) => sum + p.completed, 0) ?? 0;
  const rate = (value: number) => (total ? `${((value / total) * 100).toFixed(1)}%` : null);
  const scored = data?.programs.filter((p) => p.evaluation_percent !== null) ?? [];
  const evaluationCount = scored.reduce((sum, p) => sum + p.evaluation_count, 0);
  const evaluationAverage = evaluationCount
    ? scored.reduce((sum, p) => sum + (p.evaluation_percent ?? 0) * p.evaluation_count, 0) /
      evaluationCount
    : null;
  const outcomes = (data?.programs ?? []).map((p) => ({
    program: p.code,
    enrollments: p.enrollments,
    placed: p.placed,
    completed: p.completed,
  }));
  const bands = (data?.equity.bands ?? []).map((b) => ({ band: b.label, placements: b.value }));
  const demand = (data?.competency_demand ?? [])
    .slice(0, 8)
    .map((b) => ({ competency: b.label, opportunities: b.value }));

  return (
    <div className="space-y-8">
      <PageHeader
        title="Analytics"
        subtitle="Placement outcomes and straight-line distance across all recorded terms in your assigned program."
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
      {error && <ErrorState title="Could not load analytics" message={error} onRetry={retry} />}

      <section aria-labelledby="analytics-kpis">
        <SectionHeading
          id="analytics-kpis"
          title="Outcomes"
          description="Share of enrollment records"
        />
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
          <KpiCard
            label="Placement rate"
            value={rate(placed)}
            icon={Percent}
            tone="info"
            loading={loading}
            hint={
              rate(placed) === null
                ? "No enrollment records yet"
                : "Approved, active or completed placements"
            }
          />
          <KpiCard
            label="Completion rate"
            value={rate(completed)}
            icon={TrendingUp}
            tone="success"
            loading={loading}
            hint={rate(completed) === null ? "No enrollment records yet" : "Completed placements"}
          />
          <KpiCard
            label="Average evaluation"
            value={evaluationAverage === null ? null : `${evaluationAverage.toFixed(1)}%`}
            icon={ClipboardCheck}
            loading={loading}
            hint={evaluationCount ? `${evaluationCount} submitted` : "None submitted yet"}
          />
          <KpiCard
            label="Median straight-line distance"
            value={
              data?.equity.median_km === null || data === null
                ? null
                : `${data.equity.median_km} km`
            }
            icon={Route}
            loading={loading}
            hint={
              data?.equity.median_km === null || data === null
                ? "No placement with saved locations yet"
                : undefined
            }
          />
          <KpiCard
            label="Missing coordinates"
            value={data?.equity.missing_coordinates ?? null}
            icon={MapPinOff}
            tone={data && data.equity.missing_coordinates > 0 ? "warn" : "muted"}
            loading={loading}
            hint="Placements without a saved student or host location"
          />
        </div>
      </section>

      <section aria-labelledby="analytics-charts" className="space-y-4">
        <SectionHeading id="analytics-charts" title="Charts" />
        <WeeklyActivity weeks={data?.weekly} loading={loading} error={error} onRetry={retry} />
        <div className="grid gap-4 xl:grid-cols-2">
          <ChartCard
            title="Placement outcomes by program"
            description="Enrollment records, placed students and completed placements"
            loading={loading}
            error={error}
            onRetry={retry}
            isEmpty={outcomes.length === 0 || total === 0}
            emptyTitle="No enrollment records yet"
            emptyDescription="Outcomes appear once students are enrolled in your program."
            footnote="A student can have more than one enrollment, so counts are enrollment records, not unique people."
          >
            <BarChartView
              title="Placement outcomes by program"
              data={outcomes}
              categoryKey="program"
              categoryLabel="Program"
              valueLabel="Enrollment records"
              unit="records"
              series={[
                { key: "enrollments", label: "Enrolled", color: SERIES_COLORS[0]! },
                { key: "placed", label: "Placed", color: TONE_COLOR.info },
                { key: "completed", label: "Completed", color: TONE_COLOR.success },
              ]}
            />
          </ChartCard>
          <ChartCard
            title="Placements by distance band"
            description="Approved and active placements with saved coordinates"
            loading={loading}
            error={error}
            onRetry={retry}
            isEmpty={bands.every((b) => b.placements === 0)}
            emptyTitle="No distances to show"
            emptyDescription="Distances need an approved or active placement with saved student and host coordinates."
            footnote={
              data
                ? `${data.equity.measured_placements} placements measured. Straight-line (Haversine) kilometres, not travel time.`
                : undefined
            }
          >
            <BarChartView
              title="Placements by distance band"
              data={bands}
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
          loading={loading}
          error={error}
          onRetry={retry}
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
        {data && (
          <p className="text-xs text-muted-foreground">
            Retrieved{" "}
            {new Date(data.generated_at).toLocaleString("en-PH", {
              timeZone: "Asia/Manila",
              dateStyle: "medium",
              timeStyle: "short",
            })}
            . Live analytics are separate from the saved report snapshots below.
          </p>
        )}
      </section>

      <ReportWorkspace title="Report snapshots" headingLevel={2} />
    </div>
  );
}
