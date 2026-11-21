/**
 * One colour meaning per status, shared by badges and charts. These are the system's existing
 * status colours and rules (unchanged); charts reuse them so a segment matches its badge.
 *   success  approved, active, verified, deployed, on track, submitted, published, completed
 *   warn     pending, draft, expiring, under review, flagged
 *   brand    missing, disabled, at risk, closed, failed, unplaced (problems use the brand colour)
 *   info     informational (not returned by statusTone; used explicitly)
 *   muted    anything else
 * "danger" is accepted as an alias of "brand" so problem items can say what they mean.
 */
export type Tone = "brand" | "success" | "warn" | "danger" | "info" | "muted";

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
    [
      "missing",
      "disabled",
      "at risk",
      "low",
      "closed",
      "failed",
      "unplaced",
      "expired",
      "terminated",
    ].some((k) => s.includes(k))
  )
    return "brand";
  return "muted";
}

/** Solid colour for a tone (chart segments, dots). Same colours as the badges. */
export const TONE_COLOR: Record<Tone, string> = {
  brand: "var(--brand)",
  danger: "var(--brand)",
  success: "var(--success)",
  warn: "var(--warn)",
  info: "var(--info)",
  muted: "var(--muted-foreground)",
};

/** Categorical series colours: the system's existing chart palette, in order. */
export const SERIES_COLORS = [
  "var(--chart-1)",
  "var(--chart-2)",
  "var(--chart-3)",
  "var(--chart-4)",
  "var(--chart-5)",
];
