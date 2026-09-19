import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, CardTitle, PageHeader } from "./ui";
import { useSessionUser } from "./SessionGuard";
import { EvaluationPanel } from "./EvaluationPanel";

type Placement = {
  id: number;
  status: string;
  starts_on: string | null;
  ends_on: string | null;
  host_establishment: { name: string };
  opportunity: { title: string };
  student_enrollment: {
    student: { user: { name: string } };
    program_term: { program: { code: string } };
  };
  supervisor: { name: string } | null;
};
type Log = {
  id: number;
  time_in: string;
  time_out: string;
  break_minutes: number;
  credited_minutes: number | null;
  status: string;
  notes: string | null;
};
type Journal = { id: number; week_starts_on: string; content: string; status: string };
type Monitoring = {
  time_logs: Log[];
  journals: Journal[];
  progress: {
    required_hours: number | null;
    completed_minutes: number;
    remaining_minutes: number | null;
    remaining_days: number | null;
    hours_rule_configured: boolean;
    flags: { code: string; description: string }[];
    method: string;
  };
};
const localInput = (value: string) => {
  const date = new Date(value);
  return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};

export function MonitoringWorkspace() {
  const [result, setResult] = useState<{ data: Placement[]; last_page: number } | null>(null);
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState<number | null>(null);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    api<{ data: Placement[]; last_page: number }>(`/placements?page=${page}`)
      .then((value) => {
        if (active) {
          setResult(value);
          setSelected(value.data[0]?.id ?? null);
        }
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [page]);
  const placement = result?.data.find((item) => item.id === selected);
  return (
    <>
      <PageHeader
        title="Internship monitoring"
        subtitle="Placement records, certified hours, and weekly journals."
      />
      {error && <p role="alert">{error}</p>}
      {!result && !error && <p role="status">Loading placements…</p>}
      {result?.data.length === 0 && <Card>No placement records are available.</Card>}
      {!!result?.data.length && (
        <label className="mb-4 block text-sm">
          Placement
          <select
            className="mt-1 block w-full rounded border bg-card p-2"
            value={selected ?? ""}
            onChange={(event) => setSelected(Number(event.target.value))}
          >
            {result.data.map((item) => (
              <option key={item.id} value={item.id}>
                {item.student_enrollment.student.user.name} · {item.host_establishment.name} ·{" "}
                {item.status}
              </option>
            ))}
          </select>
        </label>
      )}
      {placement && <PlacementMonitoring key={placement.id} placement={placement} />}
      {result && result.last_page > 1 && (
        <div className="mt-4 flex gap-3">
          <Button disabled={page === 1} onClick={() => setPage(page - 1)}>
            Previous
          </Button>
          <span>
            {page} / {result.last_page}
          </span>
          <Button disabled={page === result.last_page} onClick={() => setPage(page + 1)}>
            Next
          </Button>
        </div>
      )}
    </>
  );
}

function PlacementMonitoring({ placement }: { placement: Placement }) {
  const user = useSessionUser();
  const [data, setData] = useState<Monitoring | null>(null);
  const [revision, setRevision] = useState(0);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const emptyTime = {
    id: null as number | null,
    time_in: "",
    time_out: "",
    break_minutes: "0",
    notes: "",
  };
  const [time, setTime] = useState(emptyTime);
  const [journal, setJournal] = useState({ week_starts_on: "", content: "", status: "draft" });
  const [comments, setComments] = useState<Record<string, string>>({});
  useEffect(() => {
    let active = true;
    api<Monitoring>(`/placements/${placement.id}/monitoring`)
      .then((value) => {
        if (active) setData(value);
      })
      .catch((cause: Error) => {
        if (active) setMessage(cause.message);
      });
    return () => {
      active = false;
    };
  }, [placement.id, revision]);
  const action = async (url: string, payload: unknown) => {
    setBusy(true);
    setMessage("");
    try {
      await mutate(url, payload);
      setRevision((value) => value + 1);
      setMessage("Record saved.");
      return true;
    } catch (cause) {
      setMessage(cause instanceof Error ? cause.message : "Unable to save record.");
      return false;
    } finally {
      setBusy(false);
    }
  };
  const canSubmit = user.role === "student" && placement.status === "active";
  const progress = data?.progress;
  return (
    <div className="space-y-5">
      <Card>
        <CardTitle>
          {placement.opportunity.title} · {placement.host_establishment.name}
        </CardTitle>
        <p>
          {placement.student_enrollment.program_term.program.code} · {placement.status} ·
          Supervisor: {placement.supervisor?.name ?? "Unassigned"}
        </p>
        <p className="text-sm">
          {placement.starts_on?.slice(0, 10) ?? "Start unset"} to{" "}
          {placement.ends_on?.slice(0, 10) ?? "End unset"}
        </p>
        {progress && (
          <div className="mt-3 space-y-2">
            <p>
              Certified: {(progress.completed_minutes / 60).toFixed(2)} hours · Required:{" "}
              {progress.required_hours ?? "Unconfirmed"} · Remaining:{" "}
              {progress.remaining_minutes === null
                ? "Unconfirmed"
                : `${(progress.remaining_minutes / 60).toFixed(2)} hours`}
            </p>
            <p>
              Remaining period:{" "}
              {progress.remaining_days === null
                ? "End date not configured"
                : `${progress.remaining_days} days`}
            </p>
            <p className="text-sm text-muted-foreground">
              {progress.method}{" "}
              {!progress.hours_rule_configured &&
                "The program hours-attention thresholds have not been configured."}
            </p>
            {progress.flags.map((flag) => (
              <p key={flag.code} className="rounded border border-warn/40 bg-warn/10 p-3">
                Attention required: {flag.description}
              </p>
            ))}
          </div>
        )}
      </Card>
      {message && <p role="status">{message}</p>}
      <Card>
        <CardTitle>Time logs</CardTitle>
        {canSubmit && (
          <form
            className="mb-5 grid gap-3 sm:grid-cols-2"
            onSubmit={async (event) => {
              event.preventDefault();
              if (
                await action(`/placements/${placement.id}/time-logs`, {
                  ...time,
                  time_in: new Date(time.time_in).toISOString(),
                  time_out: new Date(time.time_out).toISOString(),
                  break_minutes: Number(time.break_minutes),
                })
              )
                setTime(emptyTime);
            }}
          >
            {(["time_in", "time_out"] as const).map((key) => (
              <label key={key} className="text-sm">
                {key === "time_in" ? "Start (local time)" : "End (local time)"}
                <input
                  type="datetime-local"
                  required
                  value={time[key]}
                  onChange={(event) =>
                    setTime((current) => ({ ...current, [key]: event.target.value }))
                  }
                  className="mt-1 block w-full rounded border p-2"
                />
              </label>
            ))}
            <label className="text-sm">
              Break minutes
              <input
                type="number"
                min="0"
                required
                value={time.break_minutes}
                onChange={(event) =>
                  setTime((current) => ({ ...current, break_minutes: event.target.value }))
                }
                className="mt-1 block w-full rounded border p-2"
              />
            </label>
            <label className="text-sm">
              Work notes
              <input
                value={time.notes}
                onChange={(event) =>
                  setTime((current) => ({ ...current, notes: event.target.value }))
                }
                className="mt-1 block w-full rounded border p-2"
              />
            </label>
            <Button type="submit" disabled={busy}>
              {time.id ? "Resubmit time log" : "Submit time log"}
            </Button>
            {time.id && (
              <Button variant="outline" onClick={() => setTime(emptyTime)}>
                Cancel edit
              </Button>
            )}
          </form>
        )}
        {data?.time_logs.length === 0 && <p>No time logs recorded.</p>}
        <div className="divide-y">
          {data?.time_logs.map((log) => (
            <section key={log.id} className="space-y-2 py-3 text-sm">
              <p className="font-semibold">
                {new Date(log.time_in).toLocaleString()} — {new Date(log.time_out).toLocaleString()}
              </p>
              <p>
                {log.status} · Break: {log.break_minutes} minutes · Certified:{" "}
                {log.credited_minutes === null
                  ? "Awaiting verification"
                  : `${(log.credited_minutes / 60).toFixed(2)} hours`}
              </p>
              <p>{log.notes}</p>
              {canSubmit && log.status !== "verified" && (
                <Button
                  variant="outline"
                  onClick={() =>
                    setTime({
                      id: log.id,
                      time_in: localInput(log.time_in),
                      time_out: localInput(log.time_out),
                      break_minutes: String(log.break_minutes),
                      notes: log.notes ?? "",
                    })
                  }
                >
                  Edit time log
                </Button>
              )}
              {user.role === "supervisor" && log.status !== "verified" && (
                <div className="flex flex-wrap items-end gap-2">
                  <label>
                    Review note
                    <input
                      className="ml-2 rounded border p-2"
                      value={comments[`time${log.id}`] ?? ""}
                      onChange={(event) =>
                        setComments((current) => ({
                          ...current,
                          [`time${log.id}`]: event.target.value,
                        }))
                      }
                    />
                  </label>
                  {["verified", "flagged"].map((status) => (
                    <Button
                      key={status}
                      disabled={busy}
                      variant="outline"
                      onClick={() =>
                        action(`/time-logs/${log.id}/verify`, {
                          status,
                          notes: comments[`time${log.id}`] || null,
                        })
                      }
                    >
                      {status === "verified" ? "Certify hours" : "Flag for correction"}
                    </Button>
                  ))}
                </div>
              )}
            </section>
          ))}
        </div>
      </Card>
      <Card>
        <CardTitle>Weekly journals</CardTitle>
        {canSubmit && (
          <form
            className="mb-4 space-y-3"
            onSubmit={async (event) => {
              event.preventDefault();
              if (await action(`/placements/${placement.id}/journals`, journal))
                setJournal({ week_starts_on: "", content: "", status: "draft" });
            }}
          >
            <label className="block text-sm">
              Week beginning (Monday)
              <input
                type="date"
                required
                value={journal.week_starts_on}
                onChange={(event) =>
                  setJournal((current) => ({ ...current, week_starts_on: event.target.value }))
                }
                className="ml-2 rounded border p-2"
              />
            </label>
            <label className="block text-sm">
              Journal content
              <textarea
                required
                maxLength={30000}
                value={journal.content}
                onChange={(event) =>
                  setJournal((current) => ({ ...current, content: event.target.value }))
                }
                className="mt-1 min-h-32 w-full rounded border p-2"
              />
            </label>
            <label className="block text-sm">
              Save as
              <select
                value={journal.status}
                onChange={(event) =>
                  setJournal((current) => ({ ...current, status: event.target.value }))
                }
                className="ml-2 rounded border p-2"
              >
                <option value="draft">Draft</option>
                <option value="submitted">Submit for review</option>
              </select>
            </label>
            <Button type="submit" disabled={busy}>
              Save journal
            </Button>
          </form>
        )}
        {data?.journals.length === 0 && <p>No journals recorded.</p>}
        <div className="divide-y">
          {data?.journals.map((entry) => (
            <section key={entry.id} className="space-y-2 py-3 text-sm">
              <p className="font-semibold">
                Week of {entry.week_starts_on.slice(0, 10)} · {entry.status}
              </p>
              <p className="whitespace-pre-wrap">{entry.content}</p>
              {canSubmit && ["draft", "rejected"].includes(entry.status) && (
                <Button
                  variant="outline"
                  onClick={() =>
                    setJournal({
                      week_starts_on: entry.week_starts_on.slice(0, 10),
                      content: entry.content,
                      status: "draft",
                    })
                  }
                >
                  Edit journal
                </Button>
              )}
              {user.role === "coordinator" && entry.status === "submitted" && (
                <div className="flex flex-wrap gap-2">
                  <label>
                    Review comments
                    <input
                      className="ml-2 rounded border p-2"
                      value={comments[`journal${entry.id}`] ?? ""}
                      onChange={(event) =>
                        setComments((current) => ({
                          ...current,
                          [`journal${entry.id}`]: event.target.value,
                        }))
                      }
                    />
                  </label>
                  {["approved", "rejected"].map((status) => (
                    <Button
                      key={status}
                      variant="outline"
                      disabled={busy}
                      onClick={() =>
                        action(`/journals/${entry.id}/review`, {
                          status,
                          comments: comments[`journal${entry.id}`] || null,
                        })
                      }
                    >
                      {status === "approved" ? "Approve journal" : "Return for correction"}
                    </Button>
                  ))}
                </div>
              )}
            </section>
          ))}
        </div>
      </Card>
      <EvaluationPanel placementId={placement.id} placementStatus={placement.status} />
    </div>
  );
}
