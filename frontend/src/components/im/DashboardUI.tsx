import type { ReactNode } from "react";
import {
  ArrowRight,
  Briefcase,
  Building2,
  CircleCheck,
  ClipboardCheck,
  ClipboardList,
  Clock,
  FileCheck,
  Gauge,
  GraduationCap,
  Hourglass,
  Sparkles,
  TriangleAlert,
  Users,
  type LucideIcon,
} from "lucide-react";
import { Card, cx } from "./ui";

/** Icon by meaning, not by card position. First matching keyword wins. */
const METRIC_ICONS: [RegExp, LucideIcon][] = [
  [/attention|flag/i, TriangleAlert],
  [/hour|time log/i, Clock],
  [/incomplete requirement|requirements to complete/i, ClipboardList],
  [/requirement|submission/i, FileCheck],
  [/evaluation/i, ClipboardCheck],
  [/recommend/i, Sparkles],
  [/slot/i, Gauge],
  [/host/i, Building2],
  [/completed/i, CircleCheck],
  [/pending|awaiting/i, Hourglass],
  [/placement/i, Briefcase],
  [/enrollment|student/i, GraduationCap],
  [/account|user/i, Users],
];
function metricIcon(label: string): LucideIcon {
  return METRIC_ICONS.find(([pattern]) => pattern.test(label))?.[1] ?? Gauge;
}

export function DashboardLink({ href, children }: { href: string; children: ReactNode }) {
  return (
    <a
      href={href}
      className="inline-flex items-center gap-2 rounded text-sm font-semibold text-brand hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand"
    >
      {children}
      <ArrowRight size={14} aria-hidden="true" />
    </a>
  );
}

export function DashboardMetric({
  label,
  value,
  hint,
  accent = false,
  progress,
}: {
  label: string;
  value: string;
  hint: string;
  /** Kept for compatibility; icons are now chosen from the label. */
  index?: number;
  accent?: boolean;
  /** Optional 0–100 completion shown as a thin bar under the value. */
  progress?: number | null;
}) {
  const Icon = metricIcon(label);
  return (
    <Card className="relative overflow-hidden !rounded-xl !p-5">
      <div className="flex items-start justify-between gap-3">
        <p className="text-sm font-medium text-muted-foreground">{label}</p>
        <span
          className={cx(
            "rounded-lg p-2",
            accent ? "bg-success-soft text-success" : "bg-brand-soft text-brand",
          )}
        >
          <Icon size={18} strokeWidth={1.8} aria-hidden="true" />
        </span>
      </div>
      <p
        className={cx(
          "mt-1 break-words text-3xl font-bold tracking-tight tabular-nums",
          accent ? "text-success" : "text-foreground",
        )}
      >
        {value}
      </p>
      {progress !== undefined && progress !== null && (
        <div className="mt-3">
          <DashboardProgress value={progress} label={`${label} progress`} />
        </div>
      )}
      <p className="mt-2 text-xs leading-relaxed text-muted-foreground">{hint}</p>
    </Card>
  );
}

export function DashboardHeading({
  title,
  description,
  action,
}: {
  title: string;
  description?: string;
  action?: ReactNode;
}) {
  return (
    <div className="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
      <div>
        <h2 className="font-semibold">{title}</h2>
        {description && (
          <p className="mt-1 text-xs leading-relaxed text-muted-foreground">{description}</p>
        )}
      </div>
      {action}
    </div>
  );
}

export function DashboardEmpty({ children }: { children: ReactNode }) {
  return (
    <div className="rounded-lg border border-dashed border-border bg-muted/40 px-5 py-8 text-sm leading-relaxed text-muted-foreground">
      {children}
    </div>
  );
}

export function DashboardProgress({ value, label }: { value: number; label: string }) {
  const percent = Math.max(0, Math.min(100, value));
  return (
    <div
      role="progressbar"
      aria-label={label}
      aria-valuemin={0}
      aria-valuemax={100}
      aria-valuenow={Math.round(percent)}
      className="h-2.5 overflow-hidden rounded-full bg-muted"
    >
      <div
        className="h-full rounded-full bg-success transition-[width] motion-reduce:transition-none"
        style={{ width: `${percent}%` }}
      />
    </div>
  );
}

type Segment = { label: string; value: number; tone: "warn" | "info" | "success" | "muted" };
const SEGMENT_FILL: Record<Segment["tone"], string> = {
  warn: "bg-warn",
  info: "bg-info",
  success: "bg-success",
  muted: "bg-muted-foreground/40",
};

/**
 * One horizontal stacked bar for a status breakdown (e.g. placement pipeline).
 * Status colours are reserved and always paired with a text label and count; a
 * screen-reader table carries the same numbers.
 */
export function StatusBreakdown({ title, segments }: { title: string; segments: Segment[] }) {
  const total = segments.reduce((sum, s) => sum + s.value, 0);
  const pct = (value: number) => (total ? Math.round((value / total) * 100) : 0);
  if (!total) {
    return <DashboardEmpty>No records in this scope yet.</DashboardEmpty>;
  }
  return (
    <figure>
      <div className="flex h-3 w-full gap-0.5 overflow-hidden rounded-full" aria-hidden="true">
        {segments
          .filter((s) => s.value > 0)
          .map((s) => (
            <div
              key={s.label}
              title={`${s.label}: ${s.value.toLocaleString()} (${pct(s.value)}%)`}
              className={cx(
                "h-full first:rounded-l-full last:rounded-r-full",
                SEGMENT_FILL[s.tone],
              )}
              style={{ width: `${(s.value / total) * 100}%` }}
            />
          ))}
      </div>
      <ul className="mt-4 grid gap-3 sm:grid-cols-3" aria-hidden="true">
        {segments.map((s) => (
          <li key={s.label} className="flex items-start gap-2 text-sm">
            <span className={cx("mt-1.5 size-2.5 shrink-0 rounded-full", SEGMENT_FILL[s.tone])} />
            <span>
              <span className="block text-muted-foreground">{s.label}</span>
              <span className="font-semibold tabular-nums">{s.value.toLocaleString()}</span>
              <span className="ml-1 text-xs text-muted-foreground">{pct(s.value)}%</span>
            </span>
          </li>
        ))}
      </ul>
      <table className="sr-only">
        <caption>{title}</caption>
        <thead>
          <tr>
            <th scope="col">Status</th>
            <th scope="col">Count</th>
            <th scope="col">Share</th>
          </tr>
        </thead>
        <tbody>
          {segments.map((s) => (
            <tr key={s.label}>
              <th scope="row">{s.label}</th>
              <td>{s.value}</td>
              <td>{pct(s.value)}%</td>
            </tr>
          ))}
        </tbody>
      </table>
    </figure>
  );
}
