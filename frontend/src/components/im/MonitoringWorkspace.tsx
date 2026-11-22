import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import {
  Button,
  Card,
  CardTitle,
  Field,
  FormField,
  FormGrid,
  FormModal,
  PageHeader,
  Pill,
  StatCard,
  StatGrid,
  statusTone,
  studentName,
  formatRating,
} from "./ui";
import { Plus } from "lucide-react";
import { toast } from "sonner";
import { useSessionUser } from "./SessionGuard";
import { ConfirmButton, ReasonButton } from "./actions";
import { inputClass, toFormError, type FormError } from "./formUtils";
import { EvaluationPanel } from "./EvaluationPanel";
import { AttendanceTable, InternProgress } from "./SupervisorMonitoring";

export type Placement = {
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
  /** Present when the list was requested with include=progress,evaluations. */
  progress?: Monitoring["progress"];
  evaluations?: { period: string; status: string; weighted_percentage: number | null }[];
  moa?: {
    status: string;
    lifecycle_status?: string;
    expires_on: string;
    effective_on: string;
  } | null;
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
type Journal = {
  id: number;
  week_starts_on: string;
  content: string;
  status: string;
  review_comments?: string | null;
};
export type Monitoring = {
  placement?: {
    status: string;
    hours_certified_at?: string | null;
    certified_minutes?: number | null;
  };
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

export function MonitoringWorkspace({
  view = "all",
}: {
  view?: "all" | "interns" | "attendance" | "evaluations";
}) {
  const user = useSessionUser();
  const [result, setResult] = useState<{ data: Placement[]; last_page: number } | null>(null);
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState<number | null>(null);
  const [error, setError] = useState("");
  const [revision, setRevision] = useState(0);
  const [detail, setDetail] = useState("Weekly journals");
  useEffect(() => {
    let active = true;
    setError("");
    setResult(null);
    api<{ data: Placement[]; last_page: number }>(
      `/placements?page=${page}${view === "interns" ? "&include=progress,evaluations" : ""}`,
    )
      .then((value) => {
        if (active) {
          setResult(value);
          setSelected(view === "interns" ? null : (value.data[0]?.id ?? null));
        }
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [page, revision, view]);
  const placement = result?.data.find((item) => item.id === selected);
  return (
    <>
      <PageHeader
        title={
          view === "interns"
            ? "Assigned interns"
            : view === "attendance"
              ? "Attendance & hours"
              : view === "evaluations"
                ? "Evaluations"
                : user.role === "student"
                  ? "My internship"
                  : "Internship monitoring"
        }
        subtitle={
          user.role === "student" && placement
            ? `${placement.host_establishment.name} · ${placement.opportunity.title}`
            : "Placement records, certified hours, and weekly journals."
        }
      />
      {error && (
        <p role="alert">
          {error}{" "}
          <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
            Try again
          </Button>
        </p>
      )}
      {!result && !error && <p role="status">Loading placements…</p>}
      {result?.data.length === 0 && <Card>No placement records are available.</Card>}
      {view === "interns" && result && (
        <div className="mb-5 grid gap-5 lg:grid-cols-2">
          {result.data.map((item) => (
            <Card key={item.id}>
              <div className="flex items-start justify-between gap-3">
                <CardTitle>{studentName(item.student_enrollment.student.user)}</CardTitle>
                <Pill tone={statusTone(item.status)}>{item.status}</Pill>
              </div>
              <p className="text-sm text-muted-foreground">
                {item.student_enrollment.program_term.program.code} · {item.opportunity.title}
              </p>
              <p className="my-4 text-sm">
                {item.host_establishment.name} · {item.starts_on?.slice(0, 10) ?? "Start unset"} –{" "}
                {item.ends_on?.slice(0, 10) ?? "End unset"}
              </p>
              <InternProgress
                placementId={item.id}
                progress={item.progress}
                evaluations={item.evaluations}
              />
              <div className="flex flex-wrap gap-2">
                <Button
                  onClick={() => {
                    setSelected(item.id);
                    setDetail("Evaluations");
                  }}
                >
                  Log evaluation
                </Button>
                <Button
                  variant="outline"
                  onClick={() => {
                    setSelected(item.id);
                    setDetail("Weekly journals");
                  }}
                >
                  View journal
                </Button>
                <Button
                  variant="outline"
                  onClick={() => {
                    setSelected(item.id);
                    setDetail("Time logs");
                  }}
                >
                  View hours
                </Button>
              </div>
            </Card>
          ))}
        </div>
      )}
      {!!result?.data.length &&
        view !== "interns" &&
        (user.role !== "student" || result.data.length > 1) && (
          <label className="mb-4 block text-sm">
            Placement
            <select
              className="mt-1 block w-full rounded border bg-card p-2"
              value={selected ?? ""}
              onChange={(event) => setSelected(Number(event.target.value))}
            >
              {result.data.map((item) => (
                <option key={item.id} value={item.id}>
                  {studentName(item.student_enrollment.student.user)} ·{" "}
                  {item.host_establishment.name} · {item.status}
                </option>
              ))}
            </select>
          </label>
        )}
      {view === "interns" && placement && (
        <Button variant="outline" onClick={() => setSelected(null)}>
          Close intern details
        </Button>
      )}
      {placement && (
        <PlacementMonitoring
          key={`${placement.id}-${view}-${detail}`}
          placement={placement}
          section={
            view === "attendance"
              ? "Time logs"
              : view === "evaluations"
                ? "Evaluations"
                : view === "interns"
                  ? detail
                  : "all"
          }
        />
      )}
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

function weekKey(value: string) {
  const day =
    value.includes("T") || value.includes(" ")
      ? new Intl.DateTimeFormat("en-CA", {
          timeZone: "Asia/Manila",
          year: "numeric",
          month: "2-digit",
          day: "2-digit",
        }).format(new Date(value))
      : value.slice(0, 10);
  const date = new Date(day + "T00:00:00Z");
  date.setUTCDate(date.getUTCDate() - ((date.getUTCDay() + 6) % 7));
  return date.toISOString().slice(0, 10);
}

function StudentInternshipSummary({
  placement,
  data,
  onOpen,
}: {
  placement: Placement;
  data: Monitoring;
  onOpen: (section: string) => void;
}) {
  const [rating, setRating] = useState<string>("Loading…");
  useEffect(() => {
    let active = true;
    api<{ data: { status: string; weighted_percentage: number | null }[] }>(
      `/placements/${placement.id}/evaluations`,
    )
      .then(({ data: evaluations }) => {
        const submitted = evaluations.filter(
          (item) => item.status === "submitted" && item.weighted_percentage !== null,
        );
        if (active)
          setRating(
            submitted.length
              ? formatRating(
                  submitted.reduce((sum, item) => sum + Number(item.weighted_percentage), 0) /
                    submitted.length,
                )
              : "Not rated",
          );
      })
      .catch(() => {
        if (active) setRating("Unavailable");
      });
    return () => {
      active = false;
    };
  }, [placement.id]);
  const weeks = new Map<string, { minutes: number; pending: number; journal?: Journal }>();
  for (const log of data.time_logs) {
    const key = weekKey(log.time_in);
    const row = weeks.get(key) ?? { minutes: 0, pending: 0 };
    if (log.status === "verified") row.minutes += log.credited_minutes ?? 0;
    else row.pending++;
    weeks.set(key, row);
  }
  for (const journal of data.journals) {
    const key = weekKey(journal.week_starts_on.slice(0, 10));
    const row = weeks.get(key) ?? { minutes: 0, pending: 0 };
    row.journal = journal;
    weeks.set(key, row);
  }
  const progress = data.progress;
  const percent = progress.required_hours
    ? Math.min(
        100,
        Math.max(0, (progress.completed_minutes / (progress.required_hours * 60)) * 100),
      )
    : null;
  const dateLabel = (value: string | null | undefined) =>
    value
      ? new Date(value.slice(0, 10) + "T00:00:00Z").toLocaleDateString("en-PH", {
          timeZone: "UTC",
          month: "short",
          day: "numeric",
          year: "numeric",
        })
      : "Not recorded";
  const today = new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Manila",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(new Date());
  const moaStatus = !placement.moa
    ? "Not recorded"
    : placement.moa.lifecycle_status
      ? placement.moa.lifecycle_status
      : placement.moa.expires_on.slice(0, 10) < today
        ? "expired"
        : placement.moa.effective_on.slice(0, 10) > today
          ? "Not yet effective"
          : placement.moa.status;
  return (
    <>
      <StatGrid>
        <StatCard label="Certified hours" value={(progress.completed_minutes / 60).toFixed(1)} />
        <StatCard
          label="Hours remaining"
          value={
            progress.remaining_minutes === null
              ? "Unconfirmed"
              : (progress.remaining_minutes / 60).toFixed(1)
          }
          tone="warn"
        />
        <StatCard
          label="Weeks with certified hours"
          value={String([...weeks.values()].filter((row) => row.minutes > 0).length)}
        />
        <StatCard label="Average submitted evaluation" value={rating} tone="success" />
      </StatGrid>
      <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <Card className="p-6">
          <CardTitle
            right={
              <Button variant="outline" onClick={() => onOpen("Time logs")}>
                View time logs
              </Button>
            }
          >
            Weekly time log
          </CardTitle>
          {weeks.size === 0 ? (
            <p className="py-8 text-sm text-muted-foreground">
              No time logs or journals yet. Your weekly progress will appear here after submission.
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead>
                  <tr className="border-b text-xs text-muted-foreground">
                    <th scope="col" className="py-3 pr-4 font-medium">
                      Week beginning
                    </th>
                    <th scope="col" className="py-3 pr-4 font-medium">
                      Certified hours
                    </th>
                    <th scope="col" className="py-3 font-medium">
                      Journal
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {[...weeks.entries()]
                    .sort(([a], [b]) => b.localeCompare(a))
                    .map(([key, row]) => (
                      <tr key={key} className="border-b last:border-0">
                        <th scope="row" className="py-4 pr-4 font-normal">
                          {dateLabel(key)}
                        </th>
                        <td className="py-4 pr-4">
                          {(row.minutes / 60).toFixed(1)}
                          {row.pending > 0 && (
                            <span className="mt-1 block text-xs text-muted-foreground">
                              {row.pending} uncertified {row.pending === 1 ? "log" : "logs"}
                            </span>
                          )}
                        </td>
                        <td className="py-4">
                          <button
                            type="button"
                            aria-label={`Open journals for week ${key}`}
                            onClick={() => onOpen("Weekly journals")}
                            className="rounded-full focus-visible:outline-2 focus-visible:outline-brand"
                          >
                            <Pill tone={row.journal ? statusTone(row.journal.status) : "muted"}>
                              {row.journal?.status ?? "Not submitted"}
                            </Pill>
                          </button>
                        </td>
                      </tr>
                    ))}
                </tbody>
              </table>
            </div>
          )}
          <p className="mt-4 text-xs text-muted-foreground">
            Weeks start on Monday in Asia/Manila. Pending and flagged logs do not count toward
            completed hours.
          </p>
        </Card>
        <Card className="space-y-5 p-6">
          <CardTitle>Placement details</CardTitle>
          <Field label="Supervisor" value={placement.supervisor?.name ?? "Unassigned"} />
          <Field label="Program" value={placement.student_enrollment.program_term.program.code} />
          <Field label="Internship role" value={placement.opportunity.title} />
          <Field label="Start date" value={dateLabel(placement.starts_on)} />
          <Field label="Target completion" value={dateLabel(placement.ends_on)} />
          <div>
            <p className="mb-2 text-xs text-muted-foreground">Placement status</p>
            <Pill tone={statusTone(placement.status)}>{placement.status}</Pill>
          </div>
          <div>
            <p className="mb-2 text-xs text-muted-foreground">MOA status</p>
            <Pill tone={statusTone(moaStatus)}>{moaStatus}</Pill>
          </div>
        </Card>
      </div>
      <Card>
        <CardTitle>Internship progress</CardTitle>
        <p className="mb-3 text-sm text-muted-foreground">
          {(progress.completed_minutes / 60).toFixed(1)} of{" "}
          {progress.required_hours ?? "unconfirmed"} required hours certified
          {percent === null ? "" : ` (${percent.toFixed(0)}%)`}
        </p>
        {percent !== null && (
          <div
            role="progressbar"
            aria-label="Certified internship hours"
            aria-valuenow={Number(percent.toFixed(1))}
            aria-valuemin={0}
            aria-valuemax={100}
            className="h-2.5 overflow-hidden rounded-full bg-muted"
          >
            <div className="h-full rounded-full bg-success" style={{ width: `${percent}%` }} />
          </div>
        )}
        {progress.flags.map((flag) => (
          <p
            key={flag.code}
            role="status"
            className="mt-3 rounded-lg border border-warn/30 bg-warn/10 p-3 text-sm"
          >
            Attention required: {flag.description}
          </p>
        ))}
        {!progress.hours_rule_configured && (
          <p className="mt-3 text-xs text-muted-foreground">
            Program hours-attention thresholds await coordinator configuration.
          </p>
        )}
        {placement.status !== "active" && (
          <p className="mt-3 text-sm text-muted-foreground">
            Time logs and journals can be submitted only during an active placement.
          </p>
        )}
      </Card>
    </>
  );
}

export function PlacementMonitoring({
  placement,
  section = "all",
}: {
  placement: Placement;
  section?: string;
}) {
  const user = useSessionUser();
  const [data, setData] = useState<Monitoring | null>(null);
  const [revision, setRevision] = useState(0);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [tab, setTab] = useState("Overview");
  const emptyTime = {
    id: null as number | null,
    time_in: "",
    time_out: "",
    break_minutes: "0",
    notes: "",
  };
  const [timeEditor, setTimeEditor] = useState<(typeof emptyTime & { reason?: string }) | null>(
    null,
  );
  const [journalEditor, setJournalEditor] = useState<JournalDraft | null>(null);
  const refresh = () => setRevision((value) => value + 1);
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
  const current = data?.placement ?? placement;
  const certified = Boolean(data?.placement?.hours_certified_at);
  const canSubmit = user.role === "student" && current.status === "active" && !certified;
  const canClose =
    (user.role === "coordinator" || user.role === "admin") && current.status === "active";
  const progress = data?.progress;
  return (
    <div className="space-y-5">
      {user.role === "student" && (
        <div role="group" aria-label="Internship sections" className="flex flex-wrap gap-2">
          {["Overview", "Time logs", "Weekly journals", "Evaluations"].map((name) => (
            <button
              key={name}
              type="button"
              aria-pressed={tab === name}
              onClick={() => setTab(name)}
              className={`rounded-full border px-4 py-2 text-sm font-medium ${tab === name ? "border-brand bg-brand text-brand-foreground" : "bg-card hover:bg-muted"}`}
            >
              {name}
            </button>
          ))}
        </div>
      )}
      {user.role === "student" && tab === "Overview" && data && (
        <StudentInternshipSummary placement={placement} data={data} onOpen={setTab} />
      )}
      {user.role === "student" && !data && !message && (
        <p role="status">Loading internship progress…</p>
      )}
      {user.role !== "student" && (
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
                  "The program alert levels for hours have not been set."}
              </p>
              {progress.flags.map((flag) => (
                <p key={flag.code} className="rounded border border-warn/40 bg-warn/10 p-3">
                  Attention required: {flag.description}
                </p>
              ))}
            </div>
          )}
          {certified && (
            <p className="mt-3 text-sm">
              Hours certified by the coordinator:{" "}
              {((data?.placement?.certified_minutes ?? 0) / 60).toFixed(2)} hours.
            </p>
          )}
          {(user.role === "coordinator" || user.role === "admin") &&
            current.status === "approved" && (
              <div className="mt-4">
                <ConfirmButton
                  variant="primary"
                  title={`Start the deployment of ${studentName(placement.student_enrollment.student.user)}?`}
                  description={`The placement at ${placement.host_establishment.name} becomes active${placement.starts_on ? ` (start date ${placement.starts_on.slice(0, 10)})` : ""}. The intern can then submit time logs and journals.`}
                  confirmLabel="Start deployment"
                  success="Deployment started"
                  onConfirm={() => mutate(`/placements/${placement.id}/start`, {})}
                  onDone={refresh}
                >
                  Start deployment
                </ConfirmButton>
              </div>
            )}
          {canClose && progress && (
            <div className="mt-4 flex flex-wrap gap-2">
              {!certified && (
                <ConfirmButton
                  title={`Certify hours for ${studentName(placement.student_enrollment.student.user)}?`}
                  description={`Certify ${(progress.completed_minutes / 60).toFixed(2)} supervisor-certified hours at ${placement.host_establishment.name}. Every time log must already be certified or returned. After this, the intern cannot add or change logs.`}
                  confirmLabel="Certify hours"
                  success="Hours certified"
                  onConfirm={() => mutate(`/placements/${placement.id}/certify-hours`, {})}
                  onDone={refresh}
                >
                  Certify hours
                </ConfirmButton>
              )}
              {certified && (
                <ConfirmButton
                  tone="danger"
                  title={`End the deployment of ${studentName(placement.student_enrollment.student.user)}?`}
                  description={`The placement at ${placement.host_establishment.name} becomes completed. Chat for this deployment becomes read-only, and the supervisor can no longer send messages. This cannot be undone.`}
                  confirmLabel="End deployment"
                  success="Deployment ended"
                  onConfirm={() => mutate(`/placements/${placement.id}/end`, {})}
                  onDone={refresh}
                >
                  End deployment
                </ConfirmButton>
              )}
            </div>
          )}
        </Card>
      )}
      {message && <p role="status">{message}</p>}
      {(user.role === "student"
        ? tab === "Time logs"
        : section === "all" || section === "Time logs") && (
        <Card>
          <CardTitle>Time logs</CardTitle>
          {canSubmit && (
            <div className="mb-4">
              <Button onClick={() => setTimeEditor(emptyTime)}>
                <Plus size={16} aria-hidden="true" />
                Add time log
              </Button>
            </div>
          )}
          {data?.time_logs.length === 0 && <p>No time logs recorded.</p>}
          {user.role === "supervisor" && data ? (
            <AttendanceTable
              data={data}
              busy={busy}
              review={(id, status, notes) =>
                action(`/time-logs/${id}/verify`, { status, notes: notes || null })
              }
            />
          ) : (
            <div className="divide-y">
              {data?.time_logs.map((log) => (
                <section key={log.id} className="space-y-2 py-3 text-sm">
                  <p className="font-semibold">
                    {new Date(log.time_in).toLocaleString()} —{" "}
                    {new Date(log.time_out).toLocaleString()}
                  </p>
                  <p>
                    {log.status} · Break: {log.break_minutes} minutes · Certified:{" "}
                    {log.credited_minutes === null
                      ? "Awaiting verification"
                      : `${(log.credited_minutes / 60).toFixed(2)} hours`}
                  </p>
                  {log.status === "flagged" && log.notes ? (
                    <p className="rounded-lg border border-warn/40 bg-warn-soft p-3">
                      Returned for correction: {log.notes}
                    </p>
                  ) : (
                    <p>{log.notes}</p>
                  )}
                  {canSubmit && log.status !== "verified" && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() =>
                        setTimeEditor({
                          id: log.id,
                          time_in: localInput(log.time_in),
                          time_out: localInput(log.time_out),
                          break_minutes: String(log.break_minutes),
                          notes: log.status === "flagged" ? "" : (log.notes ?? ""),
                          ...(log.status === "flagged" && log.notes ? { reason: log.notes } : {}),
                        })
                      }
                    >
                      {log.status === "flagged" ? "Correct and resubmit" : "Edit time log"}
                    </Button>
                  )}
                </section>
              ))}
            </div>
          )}
        </Card>
      )}
      {(user.role === "student"
        ? tab === "Weekly journals"
        : section === "all" || section === "Weekly journals") && (
        <Card>
          <CardTitle>Weekly journals</CardTitle>
          {canSubmit && (
            <div className="mb-4">
              <Button
                onClick={() =>
                  setJournalEditor({ week_starts_on: "", content: "", status: "draft" })
                }
              >
                <Plus size={16} aria-hidden="true" />
                Add journal
              </Button>
            </div>
          )}
          {data?.journals.length === 0 && <p>No journals recorded.</p>}
          <div className="divide-y">
            {data?.journals.map((entry) => (
              <section key={entry.id} className="space-y-2 py-3 text-sm">
                <p className="font-semibold">
                  Week of {entry.week_starts_on.slice(0, 10)} · {entry.status}
                </p>
                <p className="whitespace-pre-wrap">{entry.content}</p>
                {entry.status === "rejected" && entry.review_comments && (
                  <p className="rounded-lg border border-warn/40 bg-warn-soft p-3">
                    Returned for correction: {entry.review_comments}
                  </p>
                )}
                {canSubmit && ["draft", "rejected"].includes(entry.status) && (
                  <div className="flex flex-wrap gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() =>
                        setJournalEditor({
                          week_starts_on: entry.week_starts_on.slice(0, 10),
                          content: entry.content,
                          status: "draft",
                          ...(entry.review_comments ? { reason: entry.review_comments } : {}),
                        })
                      }
                    >
                      {entry.status === "rejected" ? "Revise journal" : "Edit journal"}
                    </Button>
                    {entry.status === "draft" && (
                      <ConfirmButton
                        size="sm"
                        variant="primary"
                        title={`Submit the journal for the week of ${entry.week_starts_on.slice(0, 10)}?`}
                        description="Your coordinator reviews it. A submitted journal cannot be edited unless it is returned."
                        confirmLabel="Submit journal"
                        success="Journal submitted"
                        onConfirm={() =>
                          mutate(`/placements/${placement.id}/journals`, {
                            week_starts_on: entry.week_starts_on.slice(0, 10),
                            content: entry.content,
                            status: "submitted",
                          })
                        }
                        onDone={refresh}
                      >
                        Submit journal
                      </ConfirmButton>
                    )}
                  </div>
                )}
                {user.role === "coordinator" && entry.status === "submitted" && (
                  <div className="flex flex-wrap gap-2">
                    <ConfirmButton
                      size="sm"
                      title={`Approve the journal for the week of ${entry.week_starts_on.slice(0, 10)}?`}
                      description={`Approve the weekly journal from ${studentName(placement.student_enrollment.student.user)}. The intern is notified.`}
                      confirmLabel="Approve"
                      success="Journal approved"
                      onConfirm={() =>
                        mutate(`/journals/${entry.id}/review`, {
                          status: "approved",
                          comments: null,
                        })
                      }
                      onDone={refresh}
                    >
                      Approve journal
                    </ConfirmButton>
                    <ReasonButton
                      size="sm"
                      title={`Return the journal for the week of ${entry.week_starts_on.slice(0, 10)}`}
                      description="The intern sees this reason and can revise and resubmit the journal."
                      submitLabel="Return journal"
                      field="comments"
                      success="Journal returned for correction"
                      onSubmit={(reason) =>
                        mutate(`/journals/${entry.id}/review`, {
                          status: "rejected",
                          comments: reason,
                        })
                      }
                      onDone={refresh}
                    >
                      Return for correction
                    </ReasonButton>
                  </div>
                )}
              </section>
            ))}
          </div>
        </Card>
      )}
      {(user.role === "student"
        ? tab === "Evaluations"
        : section === "all" || section === "Evaluations") && (
        <EvaluationPanel placementId={placement.id} placementStatus={current.status} />
      )}
      {timeEditor && (
        <TimeLogModal
          placementId={placement.id}
          initial={timeEditor}
          onClose={() => setTimeEditor(null)}
          onSaved={() => {
            setTimeEditor(null);
            refresh();
          }}
        />
      )}
      {journalEditor && (
        <JournalModal
          placementId={placement.id}
          initial={journalEditor}
          onClose={() => setJournalEditor(null)}
          onSaved={() => {
            setJournalEditor(null);
            refresh();
          }}
        />
      )}
    </div>
  );
}

type JournalDraft = { week_starts_on: string; content: string; status: string; reason?: string };

function TimeLogModal({
  placementId,
  initial,
  onClose,
  onSaved,
}: {
  placementId: number;
  initial: {
    id: number | null;
    time_in: string;
    time_out: string;
    break_minutes: string;
    notes: string;
    reason?: string;
  };
  onClose: () => void;
  onSaved: () => void;
}) {
  const [value, setValue] = useState(initial);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  return (
    <FormModal
      open
      onClose={onClose}
      title={
        initial.reason
          ? "Correct and resubmit time log"
          : initial.id
            ? "Edit time log"
            : "Add time log"
      }
      description="Times are entered in your local time. Your supervisor certifies the hours."
      submitLabel={initial.id ? "Resubmit time log" : "Submit time log"}
      busy={busy}
      error={error}
      onSubmit={async () => {
        setBusy(true);
        setError(null);
        try {
          await mutate(`/placements/${placementId}/time-logs`, {
            ...(value.id ? { id: value.id } : {}),
            time_in: new Date(value.time_in).toISOString(),
            time_out: new Date(value.time_out).toISOString(),
            break_minutes: Number(value.break_minutes),
            notes: value.notes || null,
          });
          onSaved();
          toast.success(initial.id ? "Time log resubmitted" : "Time log submitted");
        } catch (cause) {
          setError(toFormError(cause, "Unable to save the time log."));
        } finally {
          setBusy(false);
        }
      }}
    >
      {initial.reason && (
        <p className="rounded-lg border border-warn/40 bg-warn-soft p-3 text-sm">
          Returned for correction: {initial.reason}
        </p>
      )}
      <FormGrid>
        <FormField label="Start (local time)" required>
          <input
            type="datetime-local"
            name="time_in"
            required
            className={inputClass}
            value={value.time_in}
            onChange={(event) => setValue((v) => ({ ...v, time_in: event.target.value }))}
          />
        </FormField>
        <FormField label="End (local time)" required>
          <input
            type="datetime-local"
            name="time_out"
            required
            className={inputClass}
            value={value.time_out}
            onChange={(event) => setValue((v) => ({ ...v, time_out: event.target.value }))}
          />
        </FormField>
        <FormField label="Break minutes" required>
          <input
            type="number"
            name="break_minutes"
            min="0"
            required
            className={inputClass}
            value={value.break_minutes}
            onChange={(event) => setValue((v) => ({ ...v, break_minutes: event.target.value }))}
          />
        </FormField>
      </FormGrid>
      <FormField label="Work notes">
        <textarea
          name="notes"
          rows={3}
          maxLength={5000}
          className={inputClass}
          value={value.notes}
          onChange={(event) => setValue((v) => ({ ...v, notes: event.target.value }))}
        />
      </FormField>
    </FormModal>
  );
}

function JournalModal({
  placementId,
  initial,
  onClose,
  onSaved,
}: {
  placementId: number;
  initial: JournalDraft;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [value, setValue] = useState(initial);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  const editing = Boolean(initial.week_starts_on);
  return (
    <FormModal
      open
      size="lg"
      onClose={onClose}
      title={
        initial.reason
          ? `Revise journal: week of ${initial.week_starts_on}`
          : editing
            ? `Edit journal: week of ${initial.week_starts_on}`
            : "Add journal"
      }
      description="Save a draft to keep working on it, or submit it for your coordinator's review."
      submitLabel={value.status === "submitted" ? "Submit journal" : "Save draft"}
      busy={busy}
      error={error}
      onSubmit={async () => {
        setBusy(true);
        setError(null);
        try {
          await mutate(`/placements/${placementId}/journals`, {
            week_starts_on: value.week_starts_on,
            content: value.content,
            status: value.status,
          });
          onSaved();
          toast.success(value.status === "submitted" ? "Journal submitted" : "Draft saved");
        } catch (cause) {
          setError(toFormError(cause, "Unable to save the journal."));
        } finally {
          setBusy(false);
        }
      }}
    >
      {initial.reason && (
        <p className="rounded-lg border border-warn/40 bg-warn-soft p-3 text-sm">
          Returned for correction: {initial.reason}
        </p>
      )}
      <FormField label="Week beginning (Monday)" required>
        <input
          type="date"
          name="week_starts_on"
          required
          disabled={editing}
          className={inputClass}
          value={value.week_starts_on}
          onChange={(event) => setValue((v) => ({ ...v, week_starts_on: event.target.value }))}
        />
      </FormField>
      <FormField label="Journal content" required>
        <textarea
          name="content"
          required
          rows={8}
          maxLength={30000}
          className={inputClass}
          value={value.content}
          onChange={(event) => setValue((v) => ({ ...v, content: event.target.value }))}
        />
      </FormField>
      <FormField label="Save as">
        <select
          name="status"
          className={inputClass}
          value={value.status}
          onChange={(event) => setValue((v) => ({ ...v, status: event.target.value }))}
        >
          <option value="draft">Draft</option>
          <option value="submitted">Submit for review</option>
        </select>
      </FormField>
    </FormModal>
  );
}
