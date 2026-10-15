import { useEffect, useRef, useState } from "react";
import { api } from "@/lib/api";
import { Button, Pill, statusTone } from "./ui";
import type { Monitoring } from "./MonitoringWorkspace";

export function InternProgress({ placementId }: { placementId: number }) {
  const [data, setData] = useState<Monitoring | null>(null);
  const [error, setError] = useState("");
  const [retry, setRetry] = useState(0);
  useEffect(() => {
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
  }, [placementId, retry]);
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
          className="h-2 w-full accent-emerald-700"
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
      <EvaluationSummary placementId={placementId} />
    </div>
  );
}

function EvaluationSummary({ placementId }: { placementId: number }) {
  const [scores, setScores] = useState<
    { period: string; status: string; weighted_percentage: number | null }[] | null
  >(null);
  const [error, setError] = useState("");
  const [retry, setRetry] = useState(0);
  useEffect(() => {
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
  }, [placementId, retry]);
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
            : `${Number(score.weighted_percentage).toFixed(1)}%`}
        </p>
      ))}
      <p className="text-xs text-muted-foreground">
        Rubric-weighted percentages; draft scores are excluded.
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
  const [notes, setNotes] = useState<Record<number, string>>({});
  const [error, setError] = useState("");
  const dialog = useRef<HTMLDialogElement>(null);
  const [batch, setBatch] = useState<Monitoring["time_logs"]>([]);
  const [batchBusy, setBatchBusy] = useState(false);
  const [batchResult, setBatchResult] = useState("");
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
  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end gap-3">
        <label className="text-sm">
          Status
          <select
            className="ml-2 rounded border p-2"
            value={status}
            onChange={(e) => setStatus(e.target.value)}
          >
            {["all", "pending", "verified", "flagged"].map((value) => (
              <option key={value} value={value}>
                {value === "all" ? "All statuses" : value}
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
            onChange={(e) => setDate(e.target.value)}
          />
        </label>
        <Button
          variant="outline"
          onClick={() => {
            setStatus("all");
            setDate("");
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
          dialog.current?.showModal();
        }}
      >
        Review pending logs ({pending.length})
      </Button>
      {batchResult && <p role="status">{batchResult}</p>}
      <dialog
        ref={dialog}
        aria-labelledby="batch-title"
        onCancel={(event) => {
          if (batchBusy) event.preventDefault();
        }}
        className="m-auto max-h-[85vh] w-[min(95vw,640px)] overflow-auto rounded-xl border bg-card p-6 text-foreground backdrop:bg-black/40"
      >
        <h2 id="batch-title" className="text-lg font-semibold">
          Certify pending hours
        </h2>
        <p className="my-3 text-sm">
          Review these {batch.length} pending logs for the selected intern and current filters.
          Flagged and already certified logs are excluded.
        </p>
        <ul className="my-4 space-y-2 text-sm">
          {batch.map((log) => (
            <li key={log.id}>
              Log #{log.id} · {day(log.time_in)} · {clock(log.time_in)}–{clock(log.time_out)} ·
              break {log.break_minutes} min
            </li>
          ))}
        </ul>
        <div className="flex gap-3">
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
              } finally {
                setBatchBusy(false);
                dialog.current?.close();
              }
            }}
          >
            {batchBusy ? "Certifying…" : `Confirm certification (${batch.length})`}
          </Button>
          <Button variant="outline" disabled={batchBusy} onClick={() => dialog.current?.close()}>
            Cancel
          </Button>
        </div>
      </dialog>
      {error && (
        <p role="alert" className="text-brand">
          {error}
        </p>
      )}
      <div className="overflow-x-auto">
        <table className="w-full min-w-[760px] text-left text-sm">
          <thead>
            <tr className="border-b text-xs uppercase text-muted-foreground">
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
            {rows.map((log) => (
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
                      <label className="block">
                        Review note
                        <input
                          className="mt-1 block w-full rounded border p-2"
                          aria-label={`Review note for ${day(log.time_in)} log ${log.id}`}
                          value={notes[log.id] ?? ""}
                          maxLength={5000}
                          disabled={locked}
                          onChange={(e) =>
                            setNotes((current) => ({ ...current, [log.id]: e.target.value }))
                          }
                        />
                      </label>
                      <div className="flex gap-2">
                        {["verified", "flagged"].map((next) => (
                          <Button
                            key={next}
                            variant="outline"
                            disabled={locked}
                            onClick={async () => {
                              setError("");
                              const note = (notes[log.id] ?? "").trim();
                              if (next === "flagged" && !note) {
                                setError(
                                  "Enter a review note before flagging a log for correction.",
                                );
                                return;
                              }
                              await review(log.id, next, note);
                            }}
                          >
                            {next === "verified" ? "Certify hours" : "Flag for correction"}
                          </Button>
                        ))}
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
    </div>
  );
}
