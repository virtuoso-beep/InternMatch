import { useState, type ReactNode } from "react";
import { toast } from "sonner";

export function cx(...parts: Array<string | false | null | undefined>) {
  return parts.filter(Boolean).join(" ");
}

export function downloadText(filename: string, content: string, type = "text/plain") {
  const blob = new Blob([content], { type });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}

export function PageHeader({
  title,
  subtitle,
  action,
  headingLevel = 1,
}: {
  title: string;
  subtitle?: string;
  action?: ReactNode;
  headingLevel?: 1 | 2;
}) {
  const Heading = headingLevel === 1 ? "h1" : "h2";
  return (
    <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <p className="mb-2 text-sm font-medium text-brand">Dashboard / {title}</p>
        <Heading className="text-2xl font-bold text-foreground">{title}</Heading>
        {subtitle && <p className="mt-1 text-sm text-muted-foreground">{subtitle}</p>}
      </div>
      {action}
    </div>
  );
}

export function Card({ children, className }: { children: ReactNode; className?: string }) {
  return (
    <div
      className={cx(
        "min-w-0 rounded-xl border border-border bg-card p-5 shadow-[0_1px_2px_rgba(16,24,40,0.04)]",
        className,
      )}
    >
      {children}
    </div>
  );
}

export function CardTitle({ children, right }: { children: ReactNode; right?: ReactNode }) {
  return (
    <div className="mb-4 flex items-center justify-between gap-3 border-b border-border pb-3">
      <h2 className="text-base font-semibold text-foreground">{children}</h2>
      {right}
    </div>
  );
}

const toneMap = {
  brand: "bg-brand-soft text-brand",
  success: "bg-success-soft text-success",
  warn: "bg-warn-soft text-warn",
  info: "bg-info-soft text-info",
  muted: "bg-muted text-muted-foreground",
} as const;

export type Tone = keyof typeof toneMap;

/** "under_review" -> "Under review". For machine status codes shown to people. */
export function formatStatus(value: string): string {
  const text = value.replaceAll("_", " ").trim();
  return text ? text[0]!.toUpperCase() + text.slice(1) : text;
}

const STATUS_CODE = /^[a-z]+(?:[_ ][a-z]+)*$/;

export function Pill({ children, tone = "muted" }: { children: ReactNode; tone?: Tone }) {
  // Plain lowercase status codes ("active", "under_review") read as labels; other content is untouched.
  const content =
    typeof children === "string" && STATUS_CODE.test(children) ? formatStatus(children) : children;
  return (
    <span
      className={cx(
        "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold",
        toneMap[tone],
      )}
    >
      {content}
    </span>
  );
}

export function statusTone(status: string): Tone {
  const s = status.toLowerCase();
  if (
    [
      "approved",
      "active",
      "verified",
      "deployed",
      "on track",
      "ahead",
      "submitted",
      "published",
      "high",
      "completed",
      "healthy",
    ].some((k) => s.includes(k))
  )
    return "success";
  if (
    ["pending", "draft", "expiring", "moderate", "review", "flagged", "warning"].some((k) =>
      s.includes(k),
    )
  )
    return "warn";
  if (
    ["missing", "disabled", "at risk", "low", "closed", "failed", "unplaced"].some((k) =>
      s.includes(k),
    )
  )
    return "brand";
  return "muted";
}

/** Raw cosine similarity to two decimals (spec §8): never a "% match", no strength labels. */
export function formatSimilarity(score: number | string | null | undefined): string {
  const value = Number(score);
  return score === null || score === undefined || Number.isNaN(value) ? "—" : value.toFixed(2);
}

export function StatCard({ label, value, tone }: { label: string; value: string; tone?: Tone }) {
  const color =
    tone === "success"
      ? "text-success"
      : tone === "warn"
        ? "text-warn"
        : tone === "brand"
          ? "text-brand"
          : "text-foreground";
  return (
    <Card className="p-4">
      <p className="text-sm text-muted-foreground">{label}</p>
      <p className={cx("mt-1 text-3xl font-bold tracking-tight", color)}>{value}</p>
    </Card>
  );
}

export function StatGrid({ children }: { children: ReactNode }) {
  return <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{children}</div>;
}

export function Button({
  children,
  variant = "primary",
  onClick,
  type = "button",
  disabled = false,
  className,
  "aria-label": ariaLabel,
}: {
  children: ReactNode;
  variant?: "primary" | "outline" | "ghost";
  onClick?: () => void;
  type?: "button" | "submit";
  disabled?: boolean;
  className?: string;
  "aria-label"?: string;
}) {
  const styles = {
    primary: "bg-brand text-brand-foreground hover:opacity-90",
    outline: "border border-border bg-card text-foreground hover:bg-muted",
    ghost: "text-brand hover:bg-brand-soft",
  }[variant];
  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      aria-label={ariaLabel}
      className={cx(
        // relative: keeps any sr-only text positioned inside the button (no page-wide overflow in scrolled tables)
        "relative inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50",
        styles,
        className,
      )}
    >
      {children}
    </button>
  );
}

export function ActionDialog({
  title,
  description,
  fields,
  submitLabel = "Save",
  initialValues,
  open,
  onOpenChange,
  onSubmit,
}: {
  title: string;
  description?: string;
  fields: Array<{
    name: string;
    label: string;
    placeholder?: string;
    type?: "text" | "email" | "number" | "select";
    options?: string[];
  }>;
  submitLabel?: string;
  initialValues?: Record<string, string>;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSubmit: (values: Record<string, string>) => void;
}) {
  const [values, setValues] = useState<Record<string, string>>(initialValues ?? {});

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 px-4"
      role="presentation"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="action-dialog-title"
        className="max-h-[90dvh] w-full max-w-md overflow-y-auto rounded-lg border border-border bg-card p-5 shadow-xl"
      >
        <h2 id="action-dialog-title" className="text-lg font-semibold text-foreground">
          {title}
        </h2>
        {description && <p className="mt-1 text-sm text-muted-foreground">{description}</p>}
        <form
          className="mt-5 space-y-4"
          onSubmit={(event) => {
            event.preventDefault();
            onSubmit(values);
            setValues({});
          }}
        >
          {fields.map((field) => (
            <label key={field.name} className="block">
              <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                {field.label}
              </span>
              {field.type === "select" ? (
                <select
                  required
                  value={values[field.name] ?? ""}
                  onChange={(event) =>
                    setValues((current) => ({ ...current, [field.name]: event.target.value }))
                  }
                  className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground outline-none focus:border-brand focus:ring-2 focus:ring-brand/25"
                >
                  <option value="">Select an option</option>
                  {field.options?.map((option) => (
                    <option key={option} value={option}>
                      {option}
                    </option>
                  ))}
                </select>
              ) : (
                <input
                  required
                  type={field.type ?? "text"}
                  value={values[field.name] ?? ""}
                  placeholder={field.placeholder}
                  onChange={(event) =>
                    setValues((current) => ({ ...current, [field.name]: event.target.value }))
                  }
                  className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground outline-none focus:border-brand focus:ring-2 focus:ring-brand/25"
                />
              )}
            </label>
          ))}
          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button type="submit">{submitLabel}</Button>
          </div>
        </form>
      </div>
    </div>
  );
}

export function FilterChips({
  options,
  value,
  onChange,
}: {
  options: string[];
  value: string;
  onChange: (v: string) => void;
}) {
  return (
    <div className="mb-5 flex flex-wrap gap-2">
      {options.map((o) => (
        <button
          key={o}
          type="button"
          onClick={() => onChange(o)}
          className={cx(
            "rounded-full border px-4 py-1.5 text-sm font-medium transition-colors",
            o === value
              ? "border-brand bg-brand text-brand-foreground"
              : "border-border bg-card text-foreground hover:bg-muted",
          )}
        >
          {o}
        </button>
      ))}
    </div>
  );
}

export function Table({ head, children }: { head: string[]; children: ReactNode }) {
  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[640px] text-sm">
        <thead>
          <tr className="border-b border-border text-left">
            {head.map((h) => (
              <th
                key={h}
                scope="col"
                className="pr-4 pb-3 align-bottom text-xs font-semibold tracking-wide text-muted-foreground uppercase last:pr-0"
              >
                {h}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>{children}</tbody>
      </table>
    </div>
  );
}

export function Row({ children }: { children: ReactNode }) {
  return (
    <tr className="border-b border-border/70 transition-colors last:border-0 hover:bg-muted/40 [&>td]:py-3 [&>td]:pr-4 [&>td]:align-top [&>td:last-child]:pr-0">
      {children}
    </tr>
  );
}

export function Meter({
  value,
  tone = "brand",
}: {
  value: number;
  tone?: "brand" | "success" | "warn";
}) {
  const bg = tone === "success" ? "bg-success" : tone === "warn" ? "bg-warn" : "bg-brand";
  return (
    <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
      <div
        className={cx("h-full rounded-full", bg)}
        style={{ width: `${Math.min(100, value)}%` }}
      />
    </div>
  );
}

export function Field({ label, value }: { label: string; value: ReactNode }) {
  return (
    <div>
      <p className="text-xs tracking-wide text-muted-foreground uppercase">{label}</p>
      <p className="mt-1 text-sm font-medium text-foreground">{value}</p>
    </div>
  );
}

export function Bars({ data }: { data: Array<{ label: string; value: number; max?: number }> }) {
  return (
    <div className="space-y-3">
      {data.map((d) => (
        <div key={d.label}>
          <div className="mb-1 flex justify-between text-sm">
            <span className="text-foreground">{d.label}</span>
            <span className="font-semibold text-muted-foreground">{d.value}%</span>
          </div>
          <Meter value={(d.value / (d.max ?? 100)) * 100} />
        </div>
      ))}
    </div>
  );
}

/** Human-readable local date/time for API timestamps; falls back to the raw value. */
export function formatDateTime(value: string | null | undefined): string {
  if (!value) return "—";
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? value
    : date.toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
}

/** "chat.availability_updated" -> "Chat · availability updated". Codes stay raw in exports. */
export function formatAction(action: string): string {
  const [area = "", ...rest] = action.split(".");
  const words = (value: string) => value.replaceAll("_", " ");
  const head = words(area).replace(/^./, (c) => c.toUpperCase());
  return rest.length ? `${head} · ${rest.map(words).join(" · ")}` : head;
}

/** Display name for a student's account; archived (soft-deleted) accounts come back as null. */
export function studentName(user: { name?: string | null } | null | undefined): string {
  return user?.name ?? "Archived student account";
}

/**
 * Spec §13A: show the weighted result as a 1–5 rating with its descriptor
 * (4.50–5.00 Outstanding; 3.50–4.49 Very Satisfactory; 2.50–3.49 Satisfactory; 1.50–2.49 Fair; below 1.50 Poor).
 */
export function formatRating(percentage: number | string): string {
  const rating = (Number(percentage) / 100) * 5;
  const descriptor =
    rating >= 4.5
      ? "Outstanding"
      : rating >= 3.5
        ? "Very Satisfactory"
        : rating >= 2.5
          ? "Satisfactory"
          : rating >= 1.5
            ? "Fair"
            : "Poor";
  return `${rating.toFixed(2)} / 5 · ${descriptor} (${Number(percentage).toFixed(1)}%)`;
}
