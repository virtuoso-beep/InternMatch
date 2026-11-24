import { useEffect, useState, type ReactNode } from "react";
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Label,
  Legend,
  Line,
  LineChart,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Card, cx } from "./ui";
import { EmptyState, ErrorState, Skeleton } from "./ds";

/**
 * Chart components on Recharts, styled with the design tokens (docs/DESIGN_SYSTEM.md).
 * Rules: pick the chart by the data (bar = compare categories, stacked bar = parts across
 * categories, line = change over time, donut = one share split of 2–5 parts); every chart has
 * a title, axis labels with units, a legend for more than one series, exact-value tooltips,
 * and a screen-reader table with the same numbers. Colours come from lib/status.ts.
 */

export type Series = { key: string; label: string; color: string };
type Datum = Record<string, string | number | null>;

function usePrefersReducedMotion(): boolean {
  const [reduced, setReduced] = useState(false);
  useEffect(() => {
    const query = window.matchMedia("(prefers-reduced-motion: reduce)");
    setReduced(query.matches);
    const update = () => setReduced(query.matches);
    query.addEventListener("change", update);
    return () => query.removeEventListener("change", update);
  }, []);
  return reduced;
}

const number = (value: unknown) =>
  typeof value === "number" ? value.toLocaleString("en-PH") : String(value ?? "—");

type TooltipEntry = { name?: string | number; value?: unknown; color?: string; dataKey?: unknown };
type TooltipRender = {
  active?: boolean;
  payload?: ReadonlyArray<TooltipEntry>;
  label?: string | number;
};

/** Tooltip with exact values and the unit, on the popover surface. */
function makeTooltip(unit: string) {
  return function ChartTooltip({ active, payload, label }: TooltipRender) {
    if (!active || !payload?.length) return null;
    return (
      <div className="rounded-lg border border-border bg-popover px-3 py-2 text-xs text-popover-foreground shadow-md">
        {label !== undefined && label !== "" && <p className="mb-1 font-semibold">{label}</p>}
        <ul className="space-y-0.5">
          {payload.map((entry, i) => (
            <li key={i} className="flex items-center gap-2">
              <span
                aria-hidden="true"
                className="size-2 rounded-full"
                style={{ background: entry.color }}
              />
              <span className="text-muted-foreground">{entry.name}</span>
              <span className="ml-auto pl-3 font-semibold tabular-nums">
                {number(entry.value)}
                {unit ? ` ${unit}` : ""}
              </span>
            </li>
          ))}
        </ul>
      </div>
    );
  };
}

const axisTick = { fill: "var(--muted-foreground)", fontSize: 12 };
const axisLabel = { fill: "var(--muted-foreground)", fontSize: 12 };

/** Frame shared by every chart: title, description, state handling, footnote. */
export function ChartCard({
  title,
  description,
  loading,
  error,
  onRetry,
  isEmpty,
  emptyTitle,
  emptyDescription,
  height = 260,
  footnote,
  action,
  children,
  className,
}: {
  title: string;
  description?: string | undefined;
  loading?: boolean;
  error?: string | undefined;
  onRetry?: (() => void) | undefined;
  isEmpty?: boolean;
  emptyTitle: string;
  emptyDescription?: string | undefined;
  height?: number;
  footnote?: ReactNode;
  action?: ReactNode;
  children: ReactNode;
  className?: string;
}) {
  return (
    <Card className={cx("flex flex-col", className)}>
      <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <h3 className="text-sm font-semibold text-foreground">{title}</h3>
          {description && <p className="mt-0.5 text-xs text-muted-foreground">{description}</p>}
        </div>
        {action}
      </div>
      <div className="min-w-0 flex-1">
        {error ? (
          <ErrorState title={`Could not load "${title}"`} message={error} onRetry={onRetry} />
        ) : loading ? (
          <div
            role="status"
            aria-label={`Loading ${title}`}
            className="flex items-end gap-3"
            style={{ height }}
          >
            {[55, 80, 40, 70, 30].map((h) => (
              <div key={h} className="flex-1" style={{ height: `${h}%` }}>
                <Skeleton className="h-full w-full rounded-b-none" />
              </div>
            ))}
            <span className="sr-only">Loading {title}</span>
          </div>
        ) : isEmpty ? (
          <EmptyState compact title={emptyTitle} description={emptyDescription} />
        ) : (
          children
        )}
      </div>
      {footnote && !error && !loading && !isEmpty && (
        <p className="mt-3 border-t border-border pt-3 text-xs leading-relaxed text-muted-foreground">
          {footnote}
        </p>
      )}
    </Card>
  );
}

/** Screen-reader table carrying the exact numbers drawn in a chart. */
function DataTable({
  caption,
  data,
  categoryKey,
  categoryLabel,
  series,
}: {
  caption: string;
  data: Datum[];
  categoryKey: string;
  categoryLabel: string;
  series: Series[];
}) {
  // The hidden class sits on a wrapper: a table never shrinks below its content, so an sr-only table
  // still widened the page on phones (dean Analytics scrolled sideways).
  return (
    <div className="sr-only">
      <table>
        <caption>{caption}</caption>
        <thead>
          <tr>
            <th scope="col">{categoryLabel}</th>
            {series.map((s) => (
              <th key={s.key} scope="col">
                {s.label}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {data.map((row, i) => (
            <tr key={i}>
              <th scope="row">{String(row[categoryKey])}</th>
              {series.map((s) => (
                <td key={s.key}>{number(row[s.key])}</td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function LegendText(value: string) {
  return <span className="text-xs text-muted-foreground">{value}</span>;
}

/**
 * Bar chart for comparing categories. `layout="horizontal"` draws horizontal bars (long
 * category names); `stacked` stacks the series (parts of a whole across categories).
 */
export function BarChartView({
  title,
  data,
  categoryKey,
  categoryLabel,
  series,
  valueLabel,
  unit = "",
  layout = "auto",
  stacked = false,
  height = 260,
  onSelect,
  colorFor,
}: {
  title: string;
  data: Datum[];
  categoryKey: string;
  categoryLabel: string;
  series: Series[];
  /** Axis title for the values, including the unit, e.g. "Students". */
  valueLabel: string;
  unit?: string;
  /** "auto" draws horizontal bars when there are several long category labels (they overlap on phones). */
  layout?: "vertical" | "horizontal" | "auto";
  stacked?: boolean;
  height?: number;
  /** Called with the category when a bar is clicked (only wire to an existing filtered page). */
  onSelect?: (category: string) => void;
  /** Per-bar colour for single-series status charts. */
  colorFor?: (row: Datum) => string;
}) {
  const reduced = usePrefersReducedMotion();
  const longest = Math.max(...data.map((d) => String(d[categoryKey]).length), 4);
  const horizontal =
    layout === "horizontal" || (layout === "auto" && !stacked && data.length >= 4 && longest >= 9);
  return (
    <figure className="w-full">
      <div style={{ height }} className="w-full" aria-hidden="true">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart
            data={data}
            layout={horizontal ? "vertical" : "horizontal"}
            margin={{ top: 4, right: 12, bottom: horizontal ? 18 : 22, left: 4 }}
            barCategoryGap="28%"
          >
            <CartesianGrid stroke="var(--border)" vertical={horizontal} horizontal={!horizontal} />
            {horizontal ? (
              <>
                <XAxis type="number" allowDecimals={false} tick={axisTick} stroke="var(--border)">
                  <Label
                    value={valueLabel}
                    position="insideBottom"
                    offset={-12}
                    style={axisLabel}
                  />
                </XAxis>
                <YAxis
                  type="category"
                  dataKey={categoryKey}
                  tick={axisTick}
                  width={Math.min(170, longest * 7 + 12)}
                  stroke="var(--border)"
                />
              </>
            ) : (
              <>
                <XAxis dataKey={categoryKey} tick={axisTick} stroke="var(--border)" interval={0}>
                  <Label
                    value={categoryLabel}
                    position="insideBottom"
                    offset={-14}
                    style={axisLabel}
                  />
                </XAxis>
                <YAxis allowDecimals={false} tick={axisTick} stroke="var(--border)" width={44}>
                  <Label
                    value={valueLabel}
                    angle={-90}
                    position="insideLeft"
                    style={{ ...axisLabel, textAnchor: "middle" }}
                  />
                </YAxis>
              </>
            )}
            <Tooltip cursor={{ fill: "var(--muted)", opacity: 0.6 }} content={makeTooltip(unit)} />
            {series.length > 1 && (
              <Legend
                verticalAlign="top"
                align="right"
                height={28}
                iconType="circle"
                iconSize={8}
                formatter={LegendText}
              />
            )}
            {series.map((s, index) => (
              <Bar
                key={s.key}
                dataKey={s.key}
                name={s.label}
                fill={s.color}
                {...(stacked ? { stackId: "stack" } : {})}
                radius={
                  stacked && index < series.length - 1
                    ? 0
                    : horizontal
                      ? [0, 4, 4, 0]
                      : [4, 4, 0, 0]
                }
                maxBarSize={44}
                isAnimationActive={!reduced}
                animationDuration={400}
                {...(onSelect
                  ? {
                      cursor: "pointer",
                      onClick: (entry: unknown) => {
                        const category = (entry as { payload?: Datum }).payload?.[categoryKey];
                        if (category !== undefined && category !== null) onSelect(String(category));
                      },
                    }
                  : {})}
              >
                {colorFor && series.length === 1
                  ? data.map((row, i) => <Cell key={i} fill={colorFor(row)} />)
                  : null}
              </Bar>
            ))}
          </BarChart>
        </ResponsiveContainer>
      </div>
      <DataTable
        caption={title}
        data={data}
        categoryKey={categoryKey}
        categoryLabel={categoryLabel}
        series={series}
      />
    </figure>
  );
}

/** Line chart for change over time (only with real dated data). */
export function LineChartView({
  title,
  data,
  categoryKey,
  categoryLabel,
  series,
  valueLabel,
  unit = "",
  height = 260,
}: {
  title: string;
  data: Datum[];
  categoryKey: string;
  categoryLabel: string;
  series: Series[];
  valueLabel: string;
  unit?: string;
  height?: number;
}) {
  const reduced = usePrefersReducedMotion();
  return (
    <figure className="w-full">
      <div style={{ height }} className="w-full" aria-hidden="true">
        <ResponsiveContainer width="100%" height="100%">
          <LineChart data={data} margin={{ top: 4, right: 12, bottom: 22, left: 4 }}>
            <CartesianGrid stroke="var(--border)" vertical={false} />
            <XAxis dataKey={categoryKey} tick={axisTick} stroke="var(--border)">
              <Label value={categoryLabel} position="insideBottom" offset={-14} style={axisLabel} />
            </XAxis>
            <YAxis tick={axisTick} stroke="var(--border)" width={44}>
              <Label
                value={valueLabel}
                angle={-90}
                position="insideLeft"
                style={{ ...axisLabel, textAnchor: "middle" }}
              />
            </YAxis>
            <Tooltip content={makeTooltip(unit)} />
            {series.length > 1 && (
              <Legend
                verticalAlign="top"
                align="right"
                height={28}
                iconType="circle"
                iconSize={8}
                formatter={LegendText}
              />
            )}
            {series.map((s) => (
              <Line
                key={s.key}
                type="monotone"
                dataKey={s.key}
                name={s.label}
                stroke={s.color}
                strokeWidth={2}
                dot={{ r: 3 }}
                isAnimationActive={!reduced}
              />
            ))}
          </LineChart>
        </ResponsiveContainer>
      </div>
      <DataTable
        caption={title}
        data={data}
        categoryKey={categoryKey}
        categoryLabel={categoryLabel}
        series={series}
      />
    </figure>
  );
}

export type Slice = { label: string; value: number; color: string };

/**
 * Donut for one share breakdown with 2–5 parts. The legend lists every part with its count and
 * share, so colour is never the only cue; the centre shows the total.
 */
export function DonutChartView({
  title,
  slices,
  totalLabel,
  unit = "",
  onSelect,
}: {
  title: string;
  slices: Slice[];
  totalLabel: string;
  unit?: string;
  onSelect?: (label: string) => void;
}) {
  const reduced = usePrefersReducedMotion();
  const total = slices.reduce((sum, s) => sum + s.value, 0);
  const share = (v: number) => (total ? Math.round((v / total) * 100) : 0);
  return (
    <figure className="flex flex-col items-center gap-5 sm:flex-row sm:items-center">
      <div className="relative size-44 shrink-0" aria-hidden="true">
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie
              data={slices}
              dataKey="value"
              nameKey="label"
              innerRadius="64%"
              outerRadius="100%"
              paddingAngle={slices.filter((s) => s.value > 0).length > 1 ? 2 : 0}
              stroke="var(--card)"
              strokeWidth={2}
              isAnimationActive={!reduced}
              animationDuration={400}
              {...(onSelect
                ? {
                    cursor: "pointer",
                    onClick: (entry: unknown) => {
                      const label = (entry as { label?: string }).label;
                      if (label) onSelect(label);
                    },
                  }
                : {})}
            >
              {slices.map((s) => (
                <Cell key={s.label} fill={s.color} />
              ))}
            </Pie>
            <Tooltip content={makeTooltip(unit)} />
          </PieChart>
        </ResponsiveContainer>
        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
          <span className="text-2xl font-semibold tabular-nums">
            {total.toLocaleString("en-PH")}
          </span>
          <span className="text-xs text-muted-foreground">{totalLabel}</span>
        </div>
      </div>
      <ul className="w-full min-w-0 space-y-2">
        {slices.map((s) => (
          <li key={s.label} className="flex items-center gap-2.5 text-sm">
            <span
              aria-hidden="true"
              className="size-2.5 shrink-0 rounded-full"
              style={{ background: s.color }}
            />
            <span className="min-w-0 flex-1 text-muted-foreground">{s.label}</span>
            <span className="font-semibold tabular-nums">{s.value.toLocaleString("en-PH")}</span>
            <span className="w-10 text-right text-xs text-muted-foreground tabular-nums">
              {share(s.value)}%
            </span>
          </li>
        ))}
      </ul>
      <div className="sr-only">
        <table>
          <caption>{title}</caption>
          <thead>
            <tr>
              <th scope="col">Part</th>
              <th scope="col">Count</th>
              <th scope="col">Share</th>
            </tr>
          </thead>
          <tbody>
            {slices.map((s) => (
              <tr key={s.label}>
                <th scope="row">{s.label}</th>
                <td>{s.value}</td>
                <td>{share(s.value)}%</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </figure>
  );
}
