import { useEffect, useState } from "react";
import { toast } from "sonner";
import { api } from "@/lib/api";
import { Button, Modal, Pill, formatRating, formatStatus, statusTone } from "./ui";
import type { Monitoring } from "./MonitoringWorkspace";
import { ConfirmButton, ReasonButton } from "./actions";

type Score = { period: string; status: string; weighted_percentage: number | null };

/** Lists pass `progress` and `evaluations` from GET /placements?include=…; otherwise the card loads them. */
export function InternProgress({
  placementId,
  progress,
  evaluations,
}: {
  placementId: number;
  progress?: Monitoring["progress"] | undefined;
  evaluations?: Score[] | undefined;
}) {
  const [data, setData] = useState<Pick<Monitoring, "progress"> | null>(
    progress ? { progress } : null,
  );
  const [error, setError] = useState("");
  const [retry, setRetry] = useState(0);
  useEffect(() => {
    if (progress && retry === 0) return;
    let active = true;
    setError("");
    api<Monitoring>(`/placements/${placementId}/monitoring`)
      .then((value) => {
        if (active) setData(value);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [placementId, retry, progress]);
  if (error)
    return (
      <p role="alert">
        {error}{" "}
        <Button variant="ghost" onClick={() => setRetry((v) => v + 1)}>
          Retry progress
        </Button>
      </p>
    );
  if (!data)
    return (
      <p role="status" className="my-4 text-sm">
        Loading progress…
      </p>
    );
  const p = data.progress;
  const hours = p.completed_minutes / 60;
  const percent = p.required_hours ? Math.min(100, (hours / p.required_hours) * 100) : null;
  return (
    <div className="my-4 space-y-2">
      <p className="text-sm">
        <strong>{hours.toFixed(1)}</strong> / {p.required_hours ?? "Unconfirmed"} certified hours
      </p>
      {percent !== null && (
        <progress
          className="h-2 w-full accent-success"
          aria-label="Certified hours progress"
          max={100}
          value={percent}
        />
      )}
      <p className="text-xs text-muted-foreground">
        Certified hours include previous placements in this enrollment.
      </p>
      <Pill tone={p.flags.length ? "warn" : "muted"}>
        {p.flags.length
          ? `${p.flags.length} attention flags`
          : p.hours_rule_configured
            ? "No current flags"
            : "Monitoring rules pending"}
      </Pill>
      {p.flags.map((flag) => (
        <p key={flag.code} className="text-xs text-muted-foreground">
          {flag.description}
        </p>
      ))}
      <EvaluationSummary placementId={placementId} initial={evaluations} />
    </div>
  );
}

function EvaluationSummary({
  placementId,
  initial,
}: {
  placementId: number;
  initial?: Score[] | undefined;
}) {
  const [scores, setScores] = useState<Score[] | null>(initial ?? null);
  const [error, setError] = useState("");
  const [retry, setRetry] = useState(0);
  useEffect(() => {
    if (initial && retry === 0) return;
    let active = true;
    setError("");
    api<{ data: { period: string; status: string; weighted_percentage: number | null }[] }>(
      `/placements/${placementId}/evaluations`,
    )
      .then((value) => {
        if (active) setScores(value.data);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [placementId, retry, initial]);
  if (error)
    return (
      <p role="alert">
        {error}{" "}
        <Button variant="ghost" onClick={() => setRetry((v) => v + 1)}>
          Retry evaluations
        </Button>
      </p>
    );
  if (!scores) return <p className="text-xs">Loading evaluation summary…</p>;
  const submitted = scores.filter((score) => score.status === "submitted");
  return (
    <div className="border-t pt-2 text-sm">
      <p className="font-medium">Submitted evaluations: {submitted.length}</p>
      {!submitted.length && <p className="text-muted-foreground">No submitted rating yet.</p>}
      {submitted.map((score) => (
        <p key={score.period}>
          {score.period}:{" "}
          {score.weighted_percentage === null
            ? "Score unavailable"
            : formatRating(score.weighted_percentage)}
        </p>
      ))}
      <p className="text-xs text-muted-foreground">
        Rubric-weighted ratings; draft scores are excluded.
      </p>
    </div>
  );
}

export function AttendanceTable({
  data,
  busy,
  review,
}: {
  data: Monitoring;
  busy: boolean;
  review: (id: number, status: string, notes: string) => Promise<boolean>;
}) {
  const [status, setStatus] = useState("all");
  const [date, setDate] = useState("");
  const [notes] = useState<Record<number, string>>({});
  const [batchOpen, setBatchOpen] = useState(false);
  const [batch, setBatch] = useState<Monitoring["time_logs"]>([]);
  const [batchBusy, setBatchBusy] = useState(false);
  const [batchResult, setBatchResult] = useState("");
  const [page, setPage] = useState(1);
  const locked = busy || batchBusy;
  const day = (value: string) =>
    new Intl.DateTimeFormat("en-CA", {
      timeZone: "Asia/Manila",
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    }).format(new Date(value));
  const clock = (value: string) =>
    new Date(value).toLocaleTimeString("en-PH", {
      timeZone: "Asia/Manila",
      hour: "2-digit",
      minute: "2-digit",
    });
  const rows = data.time_logs.filter(
    (log) => (status === "all" || log.status === status) && (!date || day(log.time_in) === date),
  );
  const pending = rows.filter((log) => log.status === "pending");
  // 20 logs per page; filters and bulk review still cover every matching log.
  const PAGE_SIZE = 20;
  const pages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
  const current = Math.min(page, pages);
  const pageRows = rows.slice((current - 1) * PAGE_SIZE, current * PAGE_SIZE);
  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end gap-3">
        <label className="text-sm">
          Status
          <select
            className="ml-2 rounded border p-2"
            value={status}
            onChange={(e) => {
              setStatus(e.target.value);
              setPage(1);
            }}
          >
            {["all", "pending", "verified", "flagged"].map((value) => (
              <option key={value} value={value}>
                {value === "all" ? "All statuses" : formatStatus(value)}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm">
          Work date (Manila)
          <input
            className="ml-2 rounded border p-2"
            type="date"
            value={date}
            onChange={(e) => {
              setDate(e.target.value);
              setPage(1);
            }}
          />
        </label>
        <Button
          variant="outline"
          onClick={() => {
            setStatus("all");
            setDate("");
            setPage(1);
          }}
        >
          Clear filters
        </Button>
      </div>
      <p className="text-xs text-muted-foreground">
        {rows.length} of {data.time_logs.length} logs for the selected intern. Times use
        Asia/Manila. Only certified hours count toward progress.
      </p>
      <Button
        disabled={locked || pending.length === 0}
        onClick={() => {
          setBatch([...pending]);
          setBatchResult("");
          setBatchOpen(true);
        }}
      >
        Review pending logs ({pending.length})
      </Button>
      {batchResult && <p role="status">{batchResult}</p>}
      <Modal
        open={batchOpen}
        onClose={() => setBatchOpen(false)}
        size="md"
        dismissible={!batchBusy}
        title="Certify pending hours"
        description={`Review these ${batch.length} pending logs for the selected intern and current filters. Flagged and already certified logs are excluded.`}
        footer={
          <>
            <Button variant="outline" disabled={batchBusy} onClick={() => setBatchOpen(false)}>
              Cancel
            </Button>
            <Button
              disabled={batchBusy}
              onClick={async () => {
                setBatchBusy(true);
                let completed = 0;
                try {
                  for (const log of batch) {
                    if (!(await review(log.id, "verified", (notes[log.id] ?? "").trim()))) break;
                    completed++;
                  }
                  setBatchResult(
                    completed === batch.length
                      ? `Certified ${completed} logs.`
                      : `Certified ${completed} of ${batch.length} logs. Stopped after a failed review; remaining logs were not processed. Refresh and review the remaining records.`,
                  );
                  if (completed === batch.length) toast.success(`Certified ${completed} logs`);
                } finally {
                  setBatchBusy(false);
                  setBatchOpen(false);
                }
              }}
            >
              {batchBusy ? "Certifying…" : `Confirm certification (${batch.length})`}
            </Button>
          </>
        }
      >
        <ul className="space-y-2 text-sm">
          {batch.map((log) => (
            <li key={log.id} className="rounded-lg border p-3">
              Log #{log.id} · {day(log.time_in)} · {clock(log.time_in)}–{clock(log.time_out)} ·
              break {log.break_minutes} min
            </li>
          ))}
        </ul>
      </Modal>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[760px] text-left text-sm">
          <thead>
            <tr className="border-b text-xs text-muted-foreground">
              {["Date", "Time in", "Time out", "Break", "Certified hours", "Status", "Review"].map(
                (label) => (
                  <th key={label} className="p-3">
                    {label}
                  </th>
                ),
              )}
            </tr>
          </thead>
          <tbody>
            {pageRows.map((log) => (
              <tr key={log.id} className="border-b align-top">
                <td className="p-3">{day(log.time_in)}</td>
                <td className="p-3">{clock(log.time_in)}</td>
                <td className="p-3">{clock(log.time_out)}</td>
                <td className="p-3">{log.break_minutes} min</td>
                <td className="p-3">
                  {log.credited_minutes === null
                    ? "Not certified"
                    : (log.credited_minutes / 60).toFixed(2)}
                </td>
                <td className="p-3">
                  <Pill tone={statusTone(log.status)}>{log.status}</Pill>
                </td>
                <td className="space-y-2 p-3">
                  {log.notes && (
                    <p className="max-w-xs whitespace-pre-wrap break-words">{log.notes}</p>
                  )}
                  {log.status !== "verified" && (
                    <>
                      <div className="flex flex-wrap gap-2">
                        <ConfirmButton
                          size="sm"
                          disabled={locked}
                          title={`Certify hours for ${day(log.time_in)}?`}
                          description={`Certify the ${clock(log.time_in)}–${clock(log.time_out)} log (break ${log.break_minutes} min). Certified hours count toward the intern's progress and cannot be changed afterwards.`}
                          confirmLabel="Certify hours"
                          success="Hours certified"
                          onConfirm={async () => {
                            if (!(await review(log.id, "verified", "")))
                              throw new Error(
                                "The log could not be certified. Refresh and try again.",
                              );
                          }}
                        >
                          Certify hours
                        </ConfirmButton>
                        <ReasonButton
                          size="sm"
                          disabled={locked}
                          title={`Return the ${day(log.time_in)} log for correction`}
                          description="The intern sees this reason and can correct and resubmit the log."
                          submitLabel="Return log"
                          field="notes"
                          success="Log returned for correction"
                          onSubmit={async (reason) => {
                            if (!(await review(log.id, "flagged", reason)))
                              throw new Error(
                                "The log could not be returned. Refresh and try again.",
                              );
                          }}
                        >
                          Return for correction
                        </ReasonButton>
                      </div>
                    </>
                  )}
                </td>
              </tr>
            ))}
            {!rows.length && (
              <tr>
                <td colSpan={7} className="p-6 text-center">
                  No time logs match these filters.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
      {pages > 1 && (
        <nav
          aria-label="Time log pages"
          className="flex flex-wrap items-center justify-between gap-3 text-sm"
        >
          <p className="text-muted-foreground">
            Showing {(current - 1) * PAGE_SIZE + 1}–{Math.min(rows.length, current * PAGE_SIZE)} of{" "}
            {rows.length}
          </p>
          <div className="flex items-center gap-2">
            <Button
              size="sm"
              variant="outline"
              disabled={current <= 1}
              onClick={() => setPage(current - 1)}
            >
              Previous
            </Button>
            <span aria-live="polite">
              Page {current} of {pages}
            </span>
            <Button
              size="sm"
              variant="outline"
              disabled={current >= pages}
              onClick={() => setPage(current + 1)}
            >
              Next
            </Button>
          </div>
        </nav>
      )}
    </div>
  );
}
