import { useEffect, useState } from "react";
import { toast } from "sonner";
import { ConfirmButton } from "./actions";
import { api, mutate } from "@/lib/api";
import {
  Button,
  Card,
  CardTitle,
  FormField,
  FormGrid,
  Modal,
  PageHeader,
  Field,
  Pill,
  ViewModal,
  formatSimilarity,
  statusTone,
} from "./ui";
import { inputClass as fieldClass } from "./formUtils";

const PAGE_SIZE = 15;
const STATUS_TABS: Array<[string, string]> = [
  ["pending", "Needs decision"],
  ["approved", "Approved"],
  ["rejected", "Rejected"],
  ["all", "All"],
];

/** Short label for a PlacementReadiness reason; the full sentence stays available as a tooltip. */
function shortReason(reason: string): string {
  if (reason.startsWith("Enrollment is not active")) return "Enrollment not active";
  if (reason.startsWith("Program OJT hours")) return "OJT hours not approved";
  if (reason.startsWith("BSIT placement requires")) return "Not fourth year";
  if (reason.startsWith("Coordinator academic eligibility")) return "Academic review needed";
  if (reason.includes("already has a current or completed placement")) return "Already placed";
  if (reason.startsWith("No pre-deployment requirements")) return "No requirements configured";
  const pending = reason.match(/^(.+) requires approval or confirmed attendance\.$/);
  if (pending) return `${pending[1]} pending`;
  return reason.replace(/\.$/, "");
}

function Pager({
  label,
  page,
  total,
  onPage,
}: {
  label: string;
  page: number;
  total: number;
  onPage: (page: number) => void;
}) {
  if (total <= PAGE_SIZE) return null;
  const last = Math.ceil(total / PAGE_SIZE);
  const from = (page - 1) * PAGE_SIZE + 1;
  const to = Math.min(total, page * PAGE_SIZE);
  return (
    <nav
      aria-label={label}
      className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-3 text-sm"
    >
      <p className="text-muted-foreground">
        Showing {from}–{to} of {total}
      </p>
      <div className="flex items-center gap-2">
        <Button size="sm" variant="outline" disabled={page <= 1} onClick={() => onPage(page - 1)}>
          Previous
        </Button>
        <span aria-live="polite">
          Page {page} of {last}
        </span>
        <Button
          size="sm"
          variant="outline"
          disabled={page >= last}
          onClick={() => onPage(page + 1)}
        >
          Next
        </Button>
      </div>
    </nav>
  );
}

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
  async function perform(action: () => Promise<unknown>, success?: string) {
    setBusy(true);
    setError("");
    try {
      await action();
      await load();
      setReview(null);
      setAssessment(null);
      if (success) toast.success(success);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Unable to save decision.");
    } finally {
      setBusy(false);
    }
  }
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("pending");
  const closeDecision = () => {
    setReview(null);
    setAssessment(null);
    setError("");
  };
  const [proposalPage, setProposalPage] = useState(1);
  const [eligPage, setEligPage] = useState(1);
  const [eligSearch, setEligSearch] = useState("");
  const [eligFilter, setEligFilter] = useState("all");
  const [detail, setDetail] = useState<Item | null>(null);
  const proposal = data?.proposals.find((p) => String(p.program_term_id) === cohort);
  const term = search.trim().toLowerCase();
  const visibleItems =
    proposal?.items.filter(
      (item) =>
        (status === "all" || item.review_status === status) &&
        item.student_name.toLowerCase().includes(term),
    ) ?? [];
  const pagedItems = visibleItems.slice((proposalPage - 1) * PAGE_SIZE, proposalPage * PAGE_SIZE);
  const cohortStudents = data?.students.filter((s) => String(s.program_term_id) === cohort) ?? [];
  // Reasons every student shares (for example no requirements configured) are shown once, not per row.
  const shared =
    cohortStudents.length > 1
      ? (cohortStudents[0]?.reasons ?? []).filter((r) =>
          cohortStudents.every((s) => s.reasons.includes(r)),
        )
      : [];
  const blockersOf = (student: Data["students"][number]) =>
    student.reasons.filter((r) => !shared.includes(r));
  const readyCount = cohortStudents.filter((s) => s.reasons.length === 0).length;
  const blockerCounts = new Map<string, number>();
  for (const student of cohortStudents)
    for (const r of blockersOf(student))
      blockerCounts.set(shortReason(r), (blockerCounts.get(shortReason(r)) ?? 0) + 1);
  const eligibilityFilters: Array<[string, string, number]> = [
    ["all", "All", cohortStudents.length],
    ["ready", "Ready", readyCount],
    ...(shared.length
      ? [
          [
            "shared",
            "Program-wide items only",
            cohortStudents.filter((s) => s.reasons.length > 0 && blockersOf(s).length === 0).length,
          ] as [string, string, number],
        ]
      : []),
    ...[...blockerCounts.entries()]
      .sort((a, b) => b[1] - a[1])
      .map(([label, count]): [string, string, number] => [`r:${label}`, label, count]),
  ];
  const eligTerm = eligSearch.trim().toLowerCase();
  const visibleStudents = cohortStudents.filter(
    (s) =>
      s.name.toLowerCase().includes(eligTerm) &&
      (eligFilter === "all" ||
        (eligFilter === "ready"
          ? s.reasons.length === 0
          : eligFilter === "shared"
            ? s.reasons.length > 0 && blockersOf(s).length === 0
            : blockersOf(s).some((r) => `r:${shortReason(r)}` === eligFilter))),
  );
  const pagedStudents = visibleStudents.slice((eligPage - 1) * PAGE_SIZE, eligPage * PAGE_SIZE);
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
              setProposalPage(1);
              setEligPage(1);
              setEligSearch("");
              setEligFilter("all");
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
      <div className="mt-5 space-y-5">
        <Card>
          <CardTitle
            right={
              proposal ? (
                <span className="text-xs text-muted-foreground">Batch #{proposal.id}</span>
              ) : undefined
            }
          >
            Placement proposals
          </CardTitle>
          {!proposal ? (
            <p className="text-sm">
              No proposed placements yet. Open “Create new proposals” below to prepare a batch for
              this program and term.
            </p>
          ) : (
            <>
              <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div className="flex flex-wrap gap-2" role="group" aria-label="Placement decisions">
                  {STATUS_TABS.map(([value, label]) => (
                    <button
                      key={value}
                      type="button"
                      aria-pressed={status === value}
                      className={`rounded-full px-3.5 py-1.5 text-sm font-medium ${status === value ? "bg-brand text-brand-foreground" : "bg-muted text-muted-foreground"}`}
                      onClick={() => {
                        setStatus(value);
                        setProposalPage(1);
                      }}
                    >
                      {label} (
                      {
                        proposal.items.filter(
                          (item) => value === "all" || item.review_status === value,
                        ).length
                      }
                      )
                    </button>
                  ))}
                </div>
                <input
                  type="search"
                  aria-label="Find student in proposals"
                  placeholder="Search student name"
                  className={`${inputClass} mt-0 lg:max-w-xs`}
                  value={search}
                  onChange={(e) => {
                    setSearch(e.target.value);
                    setProposalPage(1);
                  }}
                />
              </div>
              <p className="mt-3 text-xs text-muted-foreground">
                Counts and decisions belong to this batch. Slots are checked again when you approve.
              </p>
              {visibleItems.length === 0 ? (
                <div className="mt-4 rounded-lg bg-muted/40 p-5 text-center">
                  <p className="font-medium">
                    {search
                      ? "No students match your search."
                      : status === "pending"
                        ? "No proposals are waiting for a decision."
                        : `No ${status === "all" ? "" : status + " "}proposals in this batch.`}
                  </p>
                  {(search || status !== "all") && (
                    <Button
                      variant="outline"
                      className="mt-3"
                      onClick={() => {
                        setStatus("all");
                        setSearch("");
                        setProposalPage(1);
                      }}
                    >
                      View all in this batch
                    </Button>
                  )}
                </div>
              ) : (
                <ul className="mt-2 divide-y divide-border" aria-label="Proposed placements">
                  {pagedItems.map((item) => (
                    <li
                      key={item.id}
                      className="grid gap-2 py-3 md:grid-cols-[minmax(0,14rem)_minmax(0,1fr)_auto] md:items-center md:gap-4"
                    >
                      <div className="min-w-0">
                        <p className="truncate font-semibold">{item.student_name}</p>
                        <Pill tone={statusTone(item.review_status)}>{item.review_status}</Pill>
                      </div>
                      <div className="min-w-0 text-sm">
                        <p className="truncate">
                          {item.host_name ?? "Unassigned"}
                          {item.opportunity_title ? ` · ${item.opportunity_title}` : ""}
                        </p>
                        {item.similarity_score !== null && (
                          <p className="text-xs text-muted-foreground">
                            Similarity score {formatSimilarity(item.similarity_score)} ·{" "}
                            {item.distance_km === null
                              ? "Distance unavailable"
                              : `${Number(item.distance_km).toFixed(2)} km straight-line`}
                          </p>
                        )}
                        <p
                          className="mt-1 line-clamp-1 text-xs text-muted-foreground"
                          title={item.review_reason ?? item.reason}
                        >
                          {item.review_reason ?? item.reason}
                        </p>
                      </div>
                      <div className="md:text-right">
                        {item.review_status === "pending" ? (
                          <Button
                            size="sm"
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
                        ) : (
                          <Button size="sm" variant="ghost" onClick={() => setDetail(item)}>
                            View decision
                          </Button>
                        )}
                      </div>
                    </li>
                  ))}
                </ul>
              )}
              <Pager
                label="Proposal pages"
                page={proposalPage}
                total={visibleItems.length}
                onPage={setProposalPage}
              />
            </>
          )}
        </Card>
        <details className="rounded-xl border bg-card p-5">
          <summary className="cursor-pointer font-semibold">
            Student eligibility and requirements{" "}
            <span className="font-normal text-muted-foreground">
              · {cohortStudents.length} students · {readyCount} ready
            </span>
          </summary>
          <p className="mt-3 text-sm text-muted-foreground">
            Use this section when a student cannot proceed. Academic review checks eligibility; it
            does not approve a placement.{" "}
            <a className="font-semibold text-brand underline" href="/coordinator/requirements">
              Check requirements →
            </a>
          </p>
          {shared.length > 0 && cohortStudents.length > 1 && (
            <div className="mt-3 rounded-lg border border-warn/40 bg-warn-soft p-3 text-sm">
              <p className="font-semibold">Applies to every student in this program and term</p>
              <ul className="mt-1 list-disc pl-5">
                {shared.map((r) => (
                  <li key={r}>{r}</li>
                ))}
              </ul>
            </div>
          )}
          <div className="mt-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div className="flex flex-wrap gap-2" role="group" aria-label="Eligibility filter">
              {eligibilityFilters.map(([value, label, count]) => (
                <button
                  key={value}
                  type="button"
                  aria-pressed={eligFilter === value}
                  className={`rounded-full px-3 py-1 text-xs font-medium ${eligFilter === value ? "bg-brand text-brand-foreground" : "bg-muted text-muted-foreground"}`}
                  onClick={() => {
                    setEligFilter(value);
                    setEligPage(1);
                  }}
                >
                  {label} ({count})
                </button>
              ))}
            </div>
            <input
              type="search"
              aria-label="Find student in eligibility list"
              placeholder="Search student name"
              className={`${inputClass} mt-0 lg:max-w-xs`}
              value={eligSearch}
              onChange={(e) => {
                setEligSearch(e.target.value);
                setEligPage(1);
              }}
            />
          </div>
          {visibleStudents.length === 0 ? (
            <p className="mt-4 rounded-lg bg-muted/40 p-4 text-center text-sm">
              No students match this filter.
            </p>
          ) : (
            <ul className="mt-2 divide-y divide-border" aria-label="Student eligibility">
              {pagedStudents.map((student) => {
                const own = blockersOf(student);
                return (
                  <li
                    key={student.id}
                    className="grid gap-2 py-3 md:grid-cols-[minmax(0,14rem)_minmax(0,1fr)_auto] md:items-center md:gap-4"
                  >
                    <p className="truncate font-medium">{student.name}</p>
                    <div className="flex flex-wrap gap-1.5">
                      {student.reasons.length === 0 ? (
                        <Pill tone="success">Ready for allocation</Pill>
                      ) : own.length === 0 ? (
                        <span className="text-xs text-muted-foreground">
                          Only the program-wide items above
                        </span>
                      ) : (
                        own.map((r) => (
                          <span key={r} title={r}>
                            <Pill tone="warn">{shortReason(r)}</Pill>
                            <span className="sr-only">: {r}</span>
                          </span>
                        ))
                      )}
                    </div>
                    <div className="md:text-right">
                      <Button
                        size="sm"
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
                  </li>
                );
              })}
            </ul>
          )}
          <Pager
            label="Eligibility pages"
            page={eligPage}
            total={visibleStudents.length}
            onPage={setEligPage}
          />
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
          <ConfirmButton
            variant="primary"
            disabled={busy || !cohort}
            title={`Create a proposal batch for ${(() => {
              const c = data?.cohorts.find((t) => String(t.id) === cohort);
              return c ? `${c.program.code} · ${c.academic_term.name}` : "this program and term";
            })()}?`}
            description="A new matching batch replaces the list shown above. It does not approve placements or reserve slots."
            confirmLabel="Create proposal batch"
            success="Proposal batch created"
            onConfirm={async () => {
              await mutate(`/program-terms/${cohort}/allocation`, {});
              setStatus("pending");
              setSearch("");
              setProposalPage(1);
              await load();
            }}
          >
            Create proposal batch
          </ConfirmButton>
        </details>
      </div>
      <Modal
        open={Boolean(review)}
        onClose={closeDecision}
        size="md"
        dismissible={!busy}
        isDirty={() => Boolean(supervisor || start || end || reason.trim())}
        title={`Review placement for ${review?.student_name ?? ""}`}
        description="Approve the proposed placement or record why it cannot proceed."
        footer={
          <>
            <Button variant="outline" disabled={busy} onClick={closeDecision}>
              Cancel
            </Button>
            <Button
              variant="outline"
              disabled={busy || reason.trim().length < 5}
              onClick={() =>
                void perform(
                  () =>
                    mutate(`/allocation-items/${review!.id}/decision`, {
                      decision: "reject",
                      reason,
                    }),
                  "Proposal rejected",
                )
              }
            >
              Reject proposal
            </Button>
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
                void perform(
                  () =>
                    mutate(`/allocation-items/${review!.id}/decision`, {
                      decision: "approve",
                      opportunity_id: Number(opportunity),
                      supervisor_id: Number(supervisor),
                      starts_on: start,
                      ends_on: end,
                      reason,
                    }),
                  "Placement approved",
                )
              }
            >
              Approve placement
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <FormField label="Opportunity" required>
            <select
              aria-label="Opportunity"
              className={fieldClass}
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
          </FormField>
          <FormField label="Supervisor" required>
            <select
              aria-label="Supervisor"
              className={fieldClass}
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
          </FormField>
          <FormGrid>
            <FormField label="Start date" required>
              <input
                className={fieldClass}
                type="date"
                value={start}
                onChange={(e) => setStart(e.target.value)}
              />
            </FormField>
            <FormField label="End date" required>
              <input
                className={fieldClass}
                type="date"
                value={end}
                onChange={(e) => setEnd(e.target.value)}
              />
            </FormField>
          </FormGrid>
          {start && end && end < start && (
            <p role="alert" className="text-sm text-destructive">
              End date must not precede start date.
            </p>
          )}
          <FormField
            label="Decision reason"
            required
            helper="At least 5 characters. Your reason is saved with the decision."
          >
            <textarea
              className={fieldClass}
              rows={3}
              placeholder="Explain why you are approving or rejecting this proposal."
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              maxLength={2000}
            />
          </FormField>
          <div className="rounded-lg border bg-muted/40 p-3 text-sm">
            <p>
              <strong>Approve:</strong> confirm the selected opportunity, supervisor and dates. To
              change the host, select a different opportunity above.
            </p>
            <p className="mt-2">
              <strong>Reject:</strong> close this proposal without approving a placement.
              Reconsideration requires a new proposal. The student’s enrollment stays unchanged.
            </p>
            <p className="mt-2 text-muted-foreground">
              Rejection does not currently notify the student. Explain the decision through Chat.
            </p>
          </div>
          {review && error && (
            <p role="alert" className="text-sm text-destructive">
              {error}
            </p>
          )}
        </div>
      </Modal>
      <ViewModal
        open={Boolean(detail)}
        onClose={() => setDetail(null)}
        size="sm"
        title={`Decision for ${detail?.student_name ?? ""}`}
      >
        {detail && (
          <div className="space-y-3">
            <Field
              label="Status"
              value={<Pill tone={statusTone(detail.review_status)}>{detail.review_status}</Pill>}
            />
            <Field
              label="Placement"
              value={`${detail.host_name ?? "Unassigned"}${detail.opportunity_title ? ` · ${detail.opportunity_title}` : ""}`}
            />
            <Field label="Decision reason" value={detail.review_reason ?? "No reason recorded."} />
            <Field label="Matching explanation" value={detail.reason} />
          </div>
        )}
      </ViewModal>
      <Modal
        open={Boolean(assessment)}
        onClose={closeDecision}
        size="sm"
        dismissible={!busy}
        isDirty={() => Boolean(reason.trim())}
        title={`Academic review for ${assessment?.name ?? ""}`}
        description="Record whether the department confirms academic eligibility."
        footer={
          <>
            <Button variant="outline" disabled={busy} onClick={closeDecision}>
              Cancel
            </Button>
            <Button
              disabled={busy || reason.trim().length < 5}
              onClick={() =>
                void perform(
                  () =>
                    mutate(`/enrollments/${assessment!.id}/academic-eligibility`, {
                      eligible,
                      reason,
                    }),
                  "Academic review saved",
                )
              }
            >
              Save academic review
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <label className="flex gap-2 text-sm">
            <input
              type="checkbox"
              checked={eligible}
              onChange={(e) => setEligible(e.target.checked)}
            />
            Department academic eligibility confirmed
          </label>
          <FormField
            label="Decision reason"
            required
            helper="At least 5 characters. Your reason is saved with the decision."
          >
            <textarea
              className={fieldClass}
              rows={3}
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              maxLength={2000}
            />
          </FormField>
          {assessment && error && (
            <p role="alert" className="text-sm text-destructive">
              {error}
            </p>
          )}
        </div>
      </Modal>
    </>
  );
}
