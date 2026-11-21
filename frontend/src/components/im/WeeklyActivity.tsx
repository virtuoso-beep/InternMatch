import { SERIES_COLORS, TONE_COLOR } from "@/lib/status";
import { ChartCard, LineChartView } from "./charts";

export type Week = {
  week: string;
  label: string;
  certified_hours: number;
  logs: number;
  interns: number;
};

/** The last 16 weeks of practicum activity in scope: certified hours, time logs and interns logging. */
export function WeeklyActivity({
  weeks,
  loading,
  error,
  onRetry,
}: {
  weeks: Week[] | undefined;
  loading: boolean;
  error?: string | undefined;
  onRetry?: (() => void) | undefined;
}) {
  const data = weeks ?? [];
  const empty = data.every((w) => w.logs === 0);
  return (
    <div className="grid gap-4 xl:grid-cols-2">
      <ChartCard
        title="Certified hours per week"
        description="Hours from time logs the host supervisor certified, last 16 weeks"
        loading={loading}
        error={error}
        onRetry={onRetry}
        isEmpty={empty}
        emptyTitle="No time logs in the last 16 weeks"
        emptyDescription="Weekly hours appear once interns in your programs submit time logs."
        footnote="Weeks start on Monday. Hours awaiting certification are not counted."
      >
        <LineChartView
          title="Certified hours per week"
          data={data.map((w) => ({ week: w.label, hours: w.certified_hours }))}
          categoryKey="week"
          categoryLabel="Week starting"
          valueLabel="Certified hours"
          unit="hours"
          series={[{ key: "hours", label: "Certified hours", color: TONE_COLOR.success }]}
        />
      </ChartCard>
      <ChartCard
        title="Time logs and active interns per week"
        description="Time logs submitted and interns who logged time, last 16 weeks"
        loading={loading}
        error={error}
        onRetry={onRetry}
        isEmpty={empty}
        emptyTitle="No time logs in the last 16 weeks"
        emptyDescription="Weekly activity appears once interns submit time logs."
        footnote="A drop in interns logging time can signal students who need follow-up."
      >
        <LineChartView
          title="Time logs and active interns per week"
          data={data.map((w) => ({ week: w.label, logs: w.logs, interns: w.interns }))}
          categoryKey="week"
          categoryLabel="Week starting"
          valueLabel="Count"
          series={[
            { key: "logs", label: "Time logs", color: SERIES_COLORS[0]! },
            { key: "interns", label: "Interns logging", color: TONE_COLOR.info },
          ]}
        />
      </ChartCard>
    </div>
  );
}
