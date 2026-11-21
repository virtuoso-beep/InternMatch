import type { ReactNode } from "react";
import { Link } from "@tanstack/react-router";
import { ChevronRight, CircleAlert, Inbox, RotateCw, type LucideIcon } from "lucide-react";
import { cx, Button } from "./ui";
import type { Tone } from "@/lib/status";

/**
 * Shared building blocks for states and dashboards (docs/DESIGN_SYSTEM.md).
 * Links use the router so navigation stays in-app; every link points at an existing route.
 */

export function Skeleton({ className }: { className?: string }) {
  return <div aria-hidden="true" className={cx("im-skeleton rounded-md", className)} />;
}

/** Placeholder shaped like the content that is loading; announced once for screen readers. */
export function LoadingBlock({ label, rows = 3 }: { label: string; rows?: number }) {
  return (
    <div role="status" aria-label={label} className="space-y-3">
      {Array.from({ length: rows }, (_, i) => (
        <Skeleton key={i} className={cx("h-4", i === rows - 1 ? "w-2/3" : "w-full")} />
      ))}
      <span className="sr-only">{label}</span>
    </div>
  );
}

/** Empty state that says why there is nothing and, when possible, what to do next. */
export function EmptyState({
  title,
  description,
  icon: Icon = Inbox,
  action,
  compact = false,
}: {
  title: string;
  description?: string | undefined;
  icon?: LucideIcon;
  action?: ReactNode;
  compact?: boolean;
}) {
  return (
    <div
      className={cx(
        "flex flex-col items-center justify-center rounded-lg border border-dashed border-border text-center",
        compact ? "px-4 py-6" : "px-6 py-10",
      )}
    >
      <Icon
        size={compact ? 20 : 24}
        strokeWidth={1.6}
        className="text-muted-foreground"
        aria-hidden="true"
      />
      <p className="mt-3 text-sm font-medium text-foreground">{title}</p>
      {description && (
        <p className="mt-1 max-w-sm text-sm leading-relaxed text-muted-foreground">{description}</p>
      )}
      {action && <div className="mt-4">{action}</div>}
    </div>
  );
}

/** Error state: what failed, the server's message, and a retry when one is possible. */
export function ErrorState({
  title,
  message,
  onRetry,
}: {
  title: string;
  message?: string | undefined;
  onRetry?: (() => void) | undefined;
}) {
  return (
    <div
      role="alert"
      className="flex items-start gap-3 rounded-lg border border-brand/20 bg-brand-soft px-4 py-3"
    >
      <CircleAlert size={18} className="mt-0.5 shrink-0 text-brand" aria-hidden="true" />
      <div className="min-w-0 flex-1">
        <p className="text-sm font-semibold text-brand">{title}</p>
        {message && <p className="mt-0.5 text-sm break-words text-brand/90">{message}</p>}
      </div>
      {onRetry && (
        <Button variant="outline" size="sm" onClick={onRetry}>
          <RotateCw size={14} aria-hidden="true" />
          Retry
        </Button>
      )}
    </div>
  );
}

/** Section heading inside a page: plain sentence-case title, optional one-line description. */
export function SectionHeading({
  title,
  description,
  action,
  id,
}: {
  title: string;
  description?: string | undefined;
  action?: ReactNode;
  id?: string | undefined;
}) {
  return (
    <div className="mb-3 flex flex-wrap items-end justify-between gap-x-4 gap-y-2">
      <div className="min-w-0">
        <h2 id={id} className="text-base font-semibold text-foreground">
          {title}
        </h2>
        {description && <p className="mt-0.5 text-sm text-muted-foreground">{description}</p>}
      </div>
      {action}
    </div>
  );
}

const kpiIconTone: Record<Tone, string> = {
  brand: "bg-brand-soft text-brand",
  success: "bg-success-soft text-success",
  warn: "bg-warn-soft text-warn",
  danger: "bg-brand-soft text-brand",
  info: "bg-info-soft text-info",
  muted: "bg-muted text-muted-foreground",
};

/**
 * One key number. Shows a plain value: no trend or percentage change unless real dated
 * history exists (none of the current endpoints provide it). When `to` is set the card
 * opens the matching list.
 */
export function KpiCard({
  label,
  value,
  icon: Icon,
  hint,
  tone = "muted",
  to,
  loading = false,
}: {
  label: string;
  value: number | string | null | undefined;
  icon: LucideIcon;
  hint?: string | undefined;
  tone?: Tone;
  to?: string | undefined;
  loading?: boolean;
}) {
  const shown =
    value === null || value === undefined
      ? "—"
      : typeof value === "number"
        ? value.toLocaleString("en-PH")
        : value;
  const body = (
    <>
      <div className="flex items-center justify-between gap-3">
        <p className="text-sm font-medium text-muted-foreground">{label}</p>
        <span className={cx("rounded-lg p-1.5", kpiIconTone[tone])}>
          <Icon size={16} strokeWidth={1.9} aria-hidden="true" />
        </span>
      </div>
      {loading ? (
        <Skeleton className="mt-3 h-8 w-20" />
      ) : (
        <p className="mt-2 text-3xl font-semibold tracking-tight text-foreground tabular-nums">
          {shown}
        </p>
      )}
      {hint && <p className="mt-1.5 text-xs leading-relaxed text-muted-foreground">{hint}</p>}
    </>
  );
  const frame =
    "block min-w-0 rounded-xl border border-border bg-card p-4 text-card-foreground shadow-xs";
  return to ? (
    <Link
      to={to}
      className={cx(
        frame,
        "transition-colors duration-150 hover:border-brand/40 hover:bg-brand-soft/30",
      )}
    >
      {body}
    </Link>
  ) : (
    <div className={frame}>{body}</div>
  );
}

export type ActionItem = {
  id: string;
  /** Plain statement with the real count, e.g. "4 submissions awaiting review". */
  title: string;
  detail?: string | undefined;
  tone: Extract<Tone, "warn" | "danger" | "info">;
  to: string;
};

const actionTone: Record<ActionItem["tone"], string> = {
  danger: "bg-brand",
  warn: "bg-warn",
  info: "bg-info",
};

/**
 * "What needs your attention". Items are rule-based on real records (counts from the
 * backend); the list is hidden when nothing needs action and says so plainly.
 */
export function ActionList({
  items,
  loading = false,
  emptyText,
}: {
  items: ActionItem[];
  loading?: boolean;
  emptyText: string;
}) {
  if (loading) return <LoadingBlock label="Loading items that need attention" rows={2} />;
  if (items.length === 0) {
    return (
      <p className="rounded-lg border border-border bg-card px-4 py-3 text-sm text-muted-foreground">
        {emptyText}
      </p>
    );
  }
  return (
    <ul className="divide-y divide-border overflow-hidden rounded-xl border border-border bg-card shadow-xs">
      {items.map((item) => (
        <li key={item.id}>
          <Link
            to={item.to}
            className="group flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/60"
          >
            <span
              aria-hidden="true"
              className={cx("size-2 shrink-0 rounded-full", actionTone[item.tone])}
            />
            <span className="min-w-0 flex-1">
              <span className="block text-sm font-medium text-foreground">{item.title}</span>
              {item.detail && (
                <span className="mt-0.5 block text-xs text-muted-foreground">{item.detail}</span>
              )}
            </span>
            <ChevronRight
              size={16}
              aria-hidden="true"
              className="shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
            />
          </Link>
        </li>
      ))}
    </ul>
  );
}

/** Icon-only button: always has an accessible name and a hover/focus tooltip. */
export function IconButton({
  label,
  icon: Icon,
  onClick,
  disabled,
  pressed,
  className,
}: {
  label: string;
  icon: LucideIcon;
  onClick: () => void;
  disabled?: boolean;
  pressed?: boolean;
  className?: string;
}) {
  return (
    <span className="group relative inline-flex">
      <button
        type="button"
        aria-label={label}
        aria-pressed={pressed}
        onClick={onClick}
        disabled={disabled}
        className={cx(
          "inline-flex size-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:opacity-40",
          className,
        )}
      >
        <Icon size={18} strokeWidth={1.8} aria-hidden="true" />
      </button>
      <span
        role="tooltip"
        className="pointer-events-none absolute top-full left-1/2 z-50 mt-1.5 -translate-x-1/2 rounded-md bg-foreground px-2 py-1 text-xs font-medium whitespace-nowrap text-background opacity-0 shadow-md transition-opacity duration-100 group-focus-within:opacity-100 group-hover:opacity-100"
      >
        {label}
      </span>
    </span>
  );
}
