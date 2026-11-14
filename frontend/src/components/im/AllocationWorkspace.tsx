import { useEffect, useRef, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, CardTitle, PageHeader, Pill } from "./ui";

type Item = {
  id: number;
  student_name: string;
  student_enrollment_id: number;
  opportunity_id: number | null;
  opportunity_title: string | null;
  host_name: string | null;
  similarity_score: number | null;
  distance_km: number | null;
  reason: string;
  review_status: string;
  review_reason: string | null;
};
type Data = {
  cohorts: { id: number; program: { code: string }; academic_term: { name: string } }[];
  students: {
    id: number;
    name: string;
    program_term_id: number;
    academic_eligibility_confirmed: boolean | null;
    reasons: string[];
  }[];
  proposals: { id: number; program_term_id: number; status: string; items: Item[] }[];
  opportunities: { id: number; title: string; host_establishment_id: number }[];
  supervisors: { id: number; name: string; host_establishments: { id: number }[] }[];
};
const inputClass = "mt-1 block w-full rounded-lg border border-input bg-background p-2 text-sm";

export function AllocationWorkspace() {
  const [data, setData] = useState<Data | null>(null),
    [error, setError] = useState(""),
    [busy, setBusy] = useState(false),
    [cohort, setCohort] = useState("");
  const [review, setReview] = useState<Item | null>(null),
    [opportunity, setOpportunity] = useState(""),
    [supervisor, setSupervisor] = useState(""),
    [reason, setReason] = useState(""),
    [start, setStart] = useState(""),
    [end, setEnd] = useState("");
  const [assessment, setAssessment] = useState<Data["students"][number] | null>(null),
    [eligible, setEligible] = useState(false);
  async function load() {
    const next = await api<Data>("/allocation");
    setData(next);
    setCohort((current) => current || String(next.cohorts[0]?.id ?? ""));
  }
  useEffect(() => {
    void load().catch((e: Error) => setError(e.message));
  }, []);
  async function perform(action: () => Promise<unknown>) {
    setBusy(true);
    setError("");
    try {
      await action();
      await load();
      setReview(null);
      setAssessment(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Unable to save decision.");
    } finally {
      setBusy(false);
    }
  }
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("pending");
  const modal = useRef<HTMLDialogElement>(null);
  useEffect(() => {
    if (review || assessment) modal.current?.showModal();
  }, [review, assessment]);
  const proposal = data?.proposals.find((p) => String(p.program_term_id) === cohort);
  const selected = data?.opportunities.find((o) => String(o.id) === opportunity);
  return (
    <>
      <PageHeader
        title="Pending approvals"
        subtitle="Choose a student’s proposed placement, then approve it or record why it cannot proceed."
      />
      {error && (
        <p role="alert" className="text-destructive">
          {error}
        </p>
      )}
      {!data && !error && <p role="status">Loading allocation records…</p>}
      <div className="flex flex-wrap items-end gap-3">
        <label className="text-sm font-semibold">
          Program and term
          <select
            aria-label="Program and term"
            className={inputClass}
            value={cohort}
            disabled={busy}
            onChange={(e) => {
              setCohort(e.target.value);
              setSearch("");
              setStatus("pending");
            }}
          >
            {data?.cohorts.map((t) => (
              <option key={t.id} value={t.id}>
                {t.program.code} · {t.academic_term.name}
              </option>
            ))}
          </select>
        </label>
      </div>
      <div className="my-5 rounded-xl border bg-card p-4">
        <div className="mb-4 flex flex-wrap gap-2" role="group" aria-label="Placement decisions">
          {[
            ["pending", "Needs decision"],
            ["approved", "Approved"],
            ["rejected", "Rejected"],
            ["all", "All"],
          ].map(([value, label]) => (
            <button
              key={value}
              type="button"
              aria-pressed={status === value}
              className={`rounded-full px-4 py-2 text-sm font-medium ${status === value ? "bg-brand text-white" : "bg-muted text-muted-foreground"}`}
              onClick={() => setStatus(value ?? "pending")}
            >
              {label} (
              {proposal?.items.filter((item) => value === "all" || item.review_status === value)
                .length ?? 0}
              )
            </button>
          ))}
        </div>
        <div className="flex flex-wrap items-end gap-3">
          <label className="min-w-0 flex-1 text-sm">
            Find student
            <input
              type="search"
              placeholder="Search student name"
              className={inputClass}
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </label>
          {search && (
            <Button variant="ghost" onClick={() => setSearch("")}>
              Clear search
            </Button>
          )}
          <a
            className="py-2 text-sm font-semibold text-brand underline"
            href="/coordinator/requirements"
          >
            Check requirements →
          </a>
        </div>
      </div>
      <div className="space-y-5">
        <Card>
          <CardTitle>
            {status === "pending"
              ? "Students awaiting a decision"
              : status === "approved"
                ? "Approved proposals"
                : status === "rejected"
                  ? "Rejected proposals"
                  : "All proposed placements"}
          </CardTitle>
          {!proposal ? (
            <p>
              No proposed placements yet. Open “Create new proposals” below to prepare a batch for
              this program and term.
            </p>
          ) : (
            <>
              <p className="mb-3 text-sm text-muted-foreground">
                Latest batch #{proposal.id}. Counts and decisions shown here belong to this batch.
                Slots are checked again when you approve.
              </p>
              {!proposal.items.some(
                (item) =>
                  (status === "all" || item.review_status === status) &&
                  item.student_name.toLowerCase().includes(search.trim().toLowerCase()),
              ) && (
                <div className="my-4 rounded-lg bg-muted/40 p-5 text-center">
                  <p className="font-medium">
                    {search
                      ? "No students match your search."
                      : status === "pending"
                        ? "No proposals are waiting for a decision."
                        : `No ${status === "all" ? "" : status + " "}proposals in this batch.`}
                  </p>
                  <p className="mt-2 text-sm text-muted-foreground">
                    {search
                      ? "Try another name or clear the search."
                      : "Choose another tab to see earlier decisions in this batch."}
                  </p>
                  {status !== "all" && (
                    <Button
                      variant="outline"
                      className="mt-3"
                      onClick={() => {
                        setStatus("all");
                        setSearch("");
                      }}
                    >
                      View all in this batch
                    </Button>
                  )}
                </div>
              )}
              <div className="space-y-3">
                {proposal.items
                  .filter(
                    (item) =>
                      (status === "all" || item.review_status === status) &&
                      item.student_name.toLowerCase().includes(search.trim().toLowerCase()),
                  )
                  .map((item) => (
                    <div key={item.id} className="rounded-md border border-border p-4">
                      <div className="flex items-center justify-between gap-3">
                        <p className="font-semibold">{item.student_name}</p>
                        <Pill>{item.review_status}</Pill>
                      </div>
                      <p className="mt-1 text-sm text-muted-foreground">
                        {item.host_name ?? "Unassigned"}
                        {item.opportunity_title ? ` · ${item.opportunity_title}` : ""}
                      </p>
                      {item.similarity_score !== null && (
                        <p className="text-sm">
                          {(Number(item.similarity_score) * 100).toFixed(1)}% similarity ·{" "}
                          {item.distance_km === null
                            ? "Distance unavailable"
                            : `${Number(item.distance_km).toFixed(2)} km straight-line`}
                        </p>
                      )}
                      <p className="mt-2 text-sm">{item.review_reason ?? item.reason}</p>
                      {item.review_status === "pending" && (
                        <div className="mt-3 flex gap-2">
                          <Button
                            disabled={busy}
                            onClick={() => {
                              setReview(item);
                              setOpportunity(String(item.opportunity_id ?? ""));
                              setSupervisor("");
                              setReason("");
                              setStart("");
                              setEnd("");
                            }}
                          >
                            Review placement
                          </Button>
                        </div>
                      )}
                    </div>
                  ))}
              </div>
            </>
          )}
        </Card>
        <details className="rounded-xl border bg-card p-5">
          <summary className="cursor-pointer font-semibold">
            Student eligibility and requirements
          </summary>
          <p className="my-3 text-sm text-muted-foreground">
            Use this section when a student cannot proceed. Academic review checks eligibility; it
            does not approve a placement.
          </p>
          <div className="space-y-3">
            {data?.students
              .filter((s) => String(s.program_term_id) === cohort)
              .map((student) => (
                <div key={student.id} className="rounded-md border border-border p-4">
                  <p className="font-medium">{student.name}</p>
                  <p className="mt-2 text-sm">
                    {student.reasons.length ? student.reasons.join(" ") : "Ready for allocation"}
                  </p>
                  <div className="mt-3">
                    <Button
                      variant="outline"
                      disabled={busy}
                      onClick={() => {
                        setAssessment(student);
                        setEligible(student.academic_eligibility_confirmed === true);
                        setReason("");
                      }}
                    >
                      Academic review
                    </Button>
                  </div>
                </div>
              ))}
          </div>
          <p className="mt-3 text-xs text-muted-foreground">
            BSIT requires fourth-year enrollment and no failing major subjects. Confirm against
            department records. Requirement documents and event attendance are reviewed in
            Requirements.
          </p>
        </details>
        <details className="rounded-xl border bg-card p-5">
          <summary className="cursor-pointer font-semibold">Create new proposals</summary>
          <p className="my-3 text-sm text-muted-foreground">
            Prepare a new matching batch for the selected program and term. This does not approve
            placements or reserve slots. The new batch becomes the list shown above.
          </p>
          <Button
            disabled={busy || !cohort}
            onClick={() => {
              setStatus("pending");
              setSearch("");
              void perform(() => mutate(`/program-terms/${cohort}/allocation`, {}));
            }}
          >
            {busy ? "Preparing…" : "Create proposal batch"}
          </Button>
        </details>
      </div>
      {(review || assessment) && (
        <dialog
          ref={modal}
          onCancel={(e) => {
            if (busy) e.preventDefault();
            else {
              setReview(null);
              setAssessment(null);
              setError("");
            }
          }}
          className="m-auto max-h-[90vh] w-[min(95vw,450px)] overflow-auto rounded-lg bg-card p-0 text-foreground backdrop:bg-black/30"
        >
          <div
            role="dialog"
            aria-modal="true"
            aria-label={review ? "Review placement" : "Academic eligibility"}
            className="max-h-[90vh] w-full max-w-md overflow-auto rounded-lg border border-border bg-card p-5 shadow-xl"
          >
            <h2 className="text-lg font-semibold">{review?.student_name ?? assessment?.name}</h2>
            {assessment ? (
              <label className="mt-3 flex gap-2 text-sm">
                <input
                  type="checkbox"
                  checked={eligible}
                  onChange={(e) => setEligible(e.target.checked)}
                />
                Department academic eligibility confirmed
              </label>
            ) : (
              <>
                <label className="mt-3 block text-sm">
                  Opportunity
                  <select
                    aria-label="Opportunity"
                    className={inputClass}
                    value={opportunity}
                    onChange={(e) => {
                      setOpportunity(e.target.value);
                      setSupervisor("");
                    }}
                  >
                    <option value="">Select opportunity</option>
                    {data?.opportunities.map((o) => (
                      <option key={o.id} value={o.id}>
                        {o.title} (#{o.id})
                      </option>
                    ))}
                  </select>
                </label>
                <label className="mt-3 block text-sm">
                  Supervisor
                  <select
                    aria-label="Supervisor"
                    className={inputClass}
                    value={supervisor}
                    onChange={(e) => setSupervisor(e.target.value)}
                  >
                    <option value="">Select supervisor</option>
                    {data?.supervisors
                      .filter((s) =>
                        s.host_establishments.some((h) => h.id === selected?.host_establishment_id),
                      )
                      .map((s) => (
                        <option key={s.id} value={s.id}>
                          {s.name}
                        </option>
                      ))}
                  </select>
                </label>
                <label className="mt-3 block text-sm">
                  Start date
                  <input
                    className={inputClass}
                    type="date"
                    value={start}
                    onChange={(e) => setStart(e.target.value)}
                  />
                </label>
                <label className="mt-3 block text-sm">
                  End date
                  <input
                    className={inputClass}
                    type="date"
                    value={end}
                    onChange={(e) => setEnd(e.target.value)}
                  />
                </label>
              </>
            )}
            {start && end && end < start && (
              <p role="alert" className="mt-3 text-sm text-destructive">
                End date must not precede start date.
              </p>
            )}
            <label className="mt-3 block text-sm">
              Decision reason
              <textarea
                className={inputClass}
                rows={3}
                placeholder="Explain why you are approving or rejecting this proposal."
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                maxLength={2000}
              />
            </label>
            <p className="mt-1 text-xs text-muted-foreground">
              At least 5 characters. Your reason is saved with the decision.
            </p>
            {review && (
              <div className="mt-4 rounded-lg border bg-muted/40 p-3 text-sm">
                <p>
                  <strong>Approve:</strong> confirm the selected opportunity, supervisor and dates.
                  To change the host, select a different opportunity above.
                </p>
                <p className="mt-2">
                  <strong>Reject:</strong> close this proposal without approving a placement.
                  Reconsideration requires a new proposal. The student’s enrollment stays unchanged.
                </p>
                <p className="mt-2 text-muted-foreground">
                  Rejection does not currently notify the student. Explain the decision through
                  Chat.
                </p>
              </div>
            )}
            {error && (
              <p role="alert" className="mt-2 text-sm text-destructive">
                {error}
              </p>
            )}
            <div className="mt-5 flex flex-wrap gap-2">
              {assessment ? (
                <Button
                  disabled={busy || reason.trim().length < 5}
                  onClick={() =>
                    void perform(() =>
                      mutate(`/enrollments/${assessment.id}/academic-eligibility`, {
                        eligible,
                        reason,
                      }),
                    )
                  }
                >
                  Save academic review
                </Button>
              ) : (
                <>
                  <Button
                    disabled={
                      busy ||
                      !opportunity ||
                      !supervisor ||
                      !start ||
                      !end ||
                      end < start ||
                      reason.trim().length < 5
                    }
                    onClick={() =>
                      void perform(() =>
                        mutate(`/allocation-items/${review!.id}/decision`, {
                          decision: "approve",
                          opportunity_id: Number(opportunity),
                          supervisor_id: Number(supervisor),
                          starts_on: start,
                          ends_on: end,
                          reason,
                        }),
                      )
                    }
                  >
                    Approve placement
                  </Button>
                  <Button
                    variant="outline"
                    disabled={busy || reason.trim().length < 5}
                    onClick={() =>
                      void perform(() =>
                        mutate(`/allocation-items/${review!.id}/decision`, {
                          decision: "reject",
                          reason,
                        }),
                      )
                    }
                  >
                    Reject proposal
                  </Button>
                </>
              )}
              <Button
                variant="outline"
                disabled={busy}
                onClick={() => {
                  setReview(null);
                  setAssessment(null);
                  setError("");
                }}
              >
                Cancel
              </Button>
            </div>
          </div>
        </dialog>
      )}
    </>
  );
}
