import type { ReactNode } from "react";
import { Card, cx } from "./ui";

export function DashboardLink({ href, children }: { href: string; children: ReactNode }) {
  return (
    <a
      href={href}
      className="inline-flex items-center gap-2 rounded text-sm font-semibold text-brand hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand"
    >
      {children}
      <span aria-hidden="true">→</span>
    </a>
  );
}

export function DashboardMetric({
  label,
  value,
  hint,
  index = 0,
  accent = false,
}: {
  label: string;
  value: string;
  hint: string;
  index?: number;
  accent?: boolean;
}) {
  const paths = [
    "M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3M9 15l12-12M14 3h7v7",
    "M12 3v18M3 12h18M5.6 5.6l12.8 12.8M5.6 18.4 18.4 5.6",
    "M9 11l2 2 4-4M9 3H5v18h14V3h-4M9 2h6v4H9z",
    "M12 8v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0",
  ];
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
          <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
          >
            <path d={paths[index % paths.length]} />
          </svg>
        </span>
      </div>
      <p
        className={cx(
          "mt-1 break-words text-3xl font-bold tracking-tight",
          accent ? "text-success" : "text-foreground",
        )}
      >
        {value}
      </p>
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
