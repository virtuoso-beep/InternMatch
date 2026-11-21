import { useEffect, useState } from "react";
import { toast } from "sonner";
import { ConfirmButton, ReasonButton } from "./actions";
import { api, mutate } from "@/lib/api";
import { activationLabel } from "@/lib/activation";
import {
  Button,
  Card,
  FormField,
  FormGrid,
  FormModal,
  FormSection,
  PageHeader,
  Row,
  Table,
  Pill,
  statusTone,
  formatSimilarity,
  studentName,
} from "./ui";
import { inputClass, toFormError, type FormError } from "./formUtils";
import { RecommendationBatch } from "./RecommendationBatch";
import { RankingStatus } from "./RankingStatus";
import { ExcelImportButton } from "./ExcelImportButton";

import { ImportHistory } from "./ImportHistory";
import { StudentHistory } from "./StudentHistory";

type Enrollment = {
  id: number;
  status: string;
  year_level?: number | null;
  enrolled_on?: string | null;
  target_completion_on?: string | null;
  student: {
    student_number: string;
    user: {
      id: number;
      name: string;
      email: string | null;
      status?: string;
      /** Spec §17A-B: link_sent | activated | expired (absent when no link was ever sent). */
      activation?: string | null;
    };
  };
  program_term: { program: { code: string }; academic_term: { name: string } };
  current_placement: null | { status: string; host_establishment: { name: string } };
};
type Review = {
  host_name?: string | null;
  opportunity_title?: string | null;
  similarity_score?: number;
  distance_km?: number | null;
  available_slots?: number;
  moa_status?: string;
  generation_id?: string;
  generated_at?: string | null;
  id: number;
  student_name: string;
  program: string;
  explanation: string;
  history: { id: number; judgment: string; reason: string; approved_at: string }[];
};

export function CoordinatorStudents() {
  const [rows, setRows] = useState<Enrollment[] | null>(null);
  const [error, setError] = useState("");
  const [revision, setRevision] = useState(0);
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState("All");
  const [term, setTerm] = useState("");
  const [enrollmentStatus, setEnrollmentStatus] = useState("");
  const [selectedStudent, setSelectedStudent] = useState<number | null>(null);
  const [editing, setEditing] = useState<Enrollment | null>(null);
  const [program, setProgram] = useState("");
  const [sort, setSort] = useState("name");
  const [page, setPage] = useState(1);
  useEffect(() => {
    let active = true;
    setError("");
    setRows(null);
    api<{ data: Enrollment[] }>("/enrollments")
      .then((r) => {
        if (active) {
          setRows(r.data);
          setPage(1);
        }
      })
      .catch((e: Error) => {
        if (active) setError(e.message);
      });
    return () => {
      active = false;
    };
  }, [revision]);
  const label = (r: Enrollment) =>
    r.current_placement?.status === "active"
      ? "Deployed"
      : r.current_placement?.status === "pending"
        ? "Pending approval"
        : r.current_placement?.status === "approved"
          ? "Approved"
          : "Not Placed";
  const options = ["All", "Deployed", "Pending approval", "Approved", "Not Placed"];
  const filtered = (rows ?? [])
    .filter(
      (r) =>
        (filter === "All" || label(r) === filter) &&
        (!term || r.program_term.academic_term.name === term) &&
        (!enrollmentStatus || r.status === enrollmentStatus) &&
        (!program || r.program_term.program.code === program) &&
        `${studentName(r.student.user)} ${r.student.student_number} ${r.student.user?.email ?? ""} ${r.program_term.academic_term.name} ${r.current_placement?.host_establishment.name ?? ""}`
          .toLowerCase()
          .includes(search.trim().toLowerCase()),
    )
    .sort((a, b) =>
      sort === "program"
        ? a.program_term.program.code.localeCompare(b.program_term.program.code) ||
          studentName(a.student.user).localeCompare(studentName(b.student.user))
        : studentName(a.student.user).localeCompare(studentName(b.student.user)),
    );
  const pages = Math.max(1, Math.ceil(filtered.length / 20));
  const visible = filtered.slice((page - 1) * 20, page * 20);
  const reset = () => {
    setSearch("");
    setFilter("All");
    setTerm("");
    setEnrollmentStatus("");
    setProgram("");
    setSort("name");
    setPage(1);
  };
  return (
    <>
      <PageHeader
        title="Student profiles"
        subtitle="Enrollment and placement records in your assigned programs."
        action={
          <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
            Refresh students
          </Button>
        }
      />
      <ExcelImportButton module="student" onImported={() => setRevision((v) => v + 1)} />
      <ImportHistory />
      {selectedStudent !== null && (
        <StudentHistory enrollment={selectedStudent} onClose={() => setSelectedStudent(null)} />
      )}
      {error && (
        <p role="alert">
          {error}{" "}
          <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
            Try again
          </Button>
        </p>
      )}
      {!rows && !error && <p role="status">Loading students…</p>}
      {rows && (
        <>
          <div className="mb-5 flex flex-wrap gap-2" role="group" aria-label="Placement status">
            {options.map((option) => (
              <button
                type="button"
                key={option}
                aria-pressed={filter === option}
                onClick={() => {
                  setFilter(option);
                  setPage(1);
                }}
                className={`rounded-full border px-4 py-2 text-sm ${filter === option ? "bg-brand text-brand-foreground border-brand" : "bg-card hover:bg-muted"}`}
              >
                {option}
              </button>
            ))}
          </div>
          <div className="mb-5 flex flex-wrap items-end gap-3">
            <label className="text-sm">
              Search name, Student ID, email, term or host
              <input
                type="search"
                className="mt-1 block w-full rounded border p-2"
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value);
                  setPage(1);
                }}
              />
            </label>
            <label className="text-sm">
              Program
              <select
                className="mt-1 block rounded border p-2"
                aria-label="Program"
                value={program}
                onChange={(e) => {
                  setProgram(e.target.value);
                  setPage(1);
                }}
              >
                <option value="">All assigned programs</option>
                {[...new Set(rows.map((r) => r.program_term.program.code))].sort().map((code) => (
                  <option key={code}>{code}</option>
                ))}
              </select>
            </label>
            <label className="text-sm">
              Academic term
              <select
                aria-label="Academic term"
                className="mt-1 block rounded border p-2"
                value={term}
                onChange={(e) => {
                  setTerm(e.target.value);
                  setPage(1);
                }}
              >
                <option value="">All terms</option>
                {[...new Set(rows.map((r) => r.program_term.academic_term.name))].map((t) => (
                  <option key={t}>{t}</option>
                ))}
              </select>
            </label>
            <label className="text-sm">
              Enrollment status
              <select
                aria-label="Enrollment status"
                className="mt-1 block rounded border p-2"
                value={enrollmentStatus}
                onChange={(e) => {
                  setEnrollmentStatus(e.target.value);
                  setPage(1);
                }}
              >
                <option value="">All enrollment statuses</option>
                {["enrolled", "withdrawn", "completed"].map((t) => (
                  <option key={t} value={t}>
                    {t[0]!.toUpperCase() + t.slice(1)}
                  </option>
                ))}
              </select>
            </label>
            <label className="text-sm">
              Sort by
              <select
                className="mt-1 block rounded border p-2"
                aria-label="Sort by"
                value={sort}
                onChange={(e) => {
                  setSort(e.target.value);
                  setPage(1);
                }}
              >
                <option value="name">Student name</option>
                <option value="program">Program, then name</option>
              </select>
            </label>
            <Button variant="outline" onClick={reset}>
              Clear filters
            </Button>
          </div>
          <p className="mb-3 text-sm text-muted-foreground" role="status">
            {filtered.length} of {rows.length} enrollment records. A student may have multiple
            academic terms.
          </p>
          <Card>
            {visible.length === 0 ? (
              <p>
                {rows.length
                  ? "No students match these filters."
                  : "No enrollment records in your assigned programs."}
              </p>
            ) : (
              <Table
                head={[
                  "Student",
                  "Program",
                  "Academic term",
                  "Enrollment",
                  "Placement",
                  "Host establishment",
                  "Actions",
                ]}
              >
                {visible.map((r) => (
                  <Row key={r.id}>
                    <td className="font-semibold">
                      <button
                        className="text-brand underline"
                        onClick={() => setSelectedStudent(r.id)}
                      >
                        {studentName(r.student.user)}
                      </button>
                      <span className="block text-xs font-normal text-muted-foreground">
                        {r.student.student_number}
                      </span>
                      {r.student.user.status === "pending" && (
                        <span className="mt-1 flex flex-wrap items-center gap-2 text-xs font-normal">
                          <Pill tone="warn">
                            {r.student.user.activation
                              ? `Not activated · ${activationLabel(r.student.user.activation)}`
                              : "Not activated"}
                          </Pill>
                          {r.student.user.email && (
                            <ConfirmButton
                              size="sm"
                              title={`${r.student.user.activation ? "Re-send" : "Send"} the activation link to ${studentName(r.student.user)}?`}
                              description={`An email with a one-time activation link goes to ${r.student.user.email}. Any earlier link stops working.`}
                              confirmLabel={
                                r.student.user.activation ? "Re-send link" : "Send link"
                              }
                              success="Activation link sent"
                              onConfirm={() =>
                                mutate(`/users/${r.student.user.id}/activation`, {}, "POST")
                              }
                              onDone={() => setRevision((v) => v + 1)}
                            >
                              {r.student.user.activation
                                ? "Re-send activation link"
                                : "Send activation link"}
                            </ConfirmButton>
                          )}
                        </span>
                      )}
                    </td>
                    <td>{r.program_term.program.code}</td>
                    <td>{r.program_term.academic_term.name}</td>
                    <td>
                      <Pill tone={statusTone(r.status)}>
                        {r.status[0]!.toUpperCase() + r.status.slice(1)}
                      </Pill>
                    </td>
                    <td>
                      <Pill tone={statusTone(r.current_placement?.status ?? "")}>{label(r)}</Pill>
                    </td>
                    <td>{r.current_placement?.host_establishment.name ?? "Not assigned"}</td>
                    <td>
                      {r.status === "enrolled" && (
                        <div className="flex flex-wrap gap-2">
                          <Button size="sm" variant="outline" onClick={() => setEditing(r)}>
                            Edit enrollment
                          </Button>
                          {!r.current_placement && (
                            <ReasonButton
                              size="sm"
                              title={`Withdraw ${studentName(r.student.user)} from ${r.program_term.program.code} · ${r.program_term.academic_term.name}`}
                              description="The enrollment becomes withdrawn. Its records and audit history are kept. Only students with no placement history can be withdrawn."
                              submitLabel="Withdraw"
                              success="Enrollment withdrawn"
                              onSubmit={(reason) =>
                                mutate(`/enrollments/${r.id}/withdraw`, { reason })
                              }
                              onDone={() => setRevision((v) => v + 1)}
                            >
                              Withdraw
                            </ReasonButton>
                          )}
                        </div>
                      )}
                    </td>
                  </Row>
                ))}
              </Table>
            )}
          </Card>
          {editing && (
            <EnrollmentEditor
              record={editing}
              onClose={() => setEditing(null)}
              onSaved={() => {
                setEditing(null);
                setRevision((v) => v + 1);
              }}
            />
          )}
          <p className="mt-3 text-xs text-muted-foreground">
            Approved placements await deployment. Not Placed can include completed or closed
            enrollments; check the enrollment status.
          </p>
          {pages > 1 && (
            <div className="mt-4 flex items-center justify-center gap-3">
              <Button disabled={page === 1} onClick={() => setPage((p) => p - 1)}>
                Previous
              </Button>
              <span>
                Page {page} of {pages}
              </span>
              <Button disabled={page === pages} onClick={() => setPage((p) => p + 1)}>
                Next
              </Button>
            </div>
          )}
        </>
      )}
    </>
  );
}
export function CoordinatorRecommendations() {
  const [reviewStatus, setReviewStatus] = useState("");

  const [notice, setNotice] = useState("");
  const [data, setData] = useState<{ data: Review[]; last_page: number } | null>(null);
  const [page, setPage] = useState(1),
    [revision, setRevision] = useState(0),
    [error, setError] = useState("");
  const [search, setSearch] = useState(""),
    [program, setProgram] = useState("");
  const [selected, setSelected] = useState<number | null>(null);
  useEffect(() => {
    let active = true;
    setData(null);
    setError("");
    setSelected(null);
    api<{ data: Review[]; last_page: number }>(`/recommendation-reviews?page=${page}`)
      .then((value) => {
        if (active) setData(value);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [page, revision]);
  const visible =
    data?.data
      .filter(
        (r) =>
          (!program || r.program === program) &&
          (!reviewStatus ||
            (reviewStatus === "unreviewed" ? r.history.length === 0 : r.history.length > 0)) &&
          `${r.student_name} ${r.explanation}`.toLowerCase().includes(search.trim().toLowerCase()),
      )
      .sort((a, b) => Number(a.history.length > 0) - Number(b.history.length > 0) || b.id - a.id) ??
    [];
  const record = visible.find((r) => r.id === selected);
  return (
    <>
      <PageHeader
        title="Recommendation Review"
        subtitle="Review saved matching explanations. Final placement approval and reassignment take place in Pending approvals."
        action={
          <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
            Refresh recommendations
          </Button>
        }
      />
      <a
        className="mb-5 inline-block font-semibold text-brand underline"
        href="/coordinator/approvals"
      >
        Go to pending approvals →
      </a>
      <RankingStatus />
      {error && (
        <p role="alert">
          {error} <Button onClick={() => setRevision((v) => v + 1)}>Try again</Button>
        </p>
      )}
      <RecommendationBatch done={() => setRevision((value) => value + 1)} />
      {!data && !error && <p role="status">Loading recommendations…</p>}
      {notice && (
        <p
          role="status"
          className="mb-4 rounded border border-success/30 bg-success-soft p-3 text-sm text-success"
        >
          {notice}
        </p>
      )}
      {data && (
        <>
          <div className="mb-4 space-y-4 rounded-xl border bg-card p-4">
            <div className="rounded-lg border border-brand/20 bg-brand-soft/30 p-3 text-sm">
              <strong>Review recommendations for your assigned programs.</strong>
              <p className="mt-1 text-muted-foreground">
                Reviewing a match does not change an approved placement. Host details, slots and MOA
                status below are saved at generation time. Similarity is not a placement
                probability; distance is straight-line kilometers.
              </p>
            </div>
            <div className="grid gap-3 sm:grid-cols-3">
              {[
                ["On this page", data.data.length],
                ["Needs review", data.data.filter((r) => !r.history.length).length],
                ["Reviewed", data.data.filter((r) => r.history.length).length],
              ].map(([label, count]) => (
                <div key={label} className="rounded-lg bg-muted/40 p-3">
                  <p className="text-xs text-muted-foreground">{label}</p>
                  <p className="text-2xl font-semibold">{count}</p>
                </div>
              ))}
            </div>
            <div className="flex flex-wrap gap-2" role="group" aria-label="Review status">
              {[
                ["", "All"],
                ["unreviewed", "Needs review"],
                ["reviewed", "Reviewed"],
              ].map(([value, label]) => (
                <button
                  key={value}
                  type="button"
                  aria-pressed={reviewStatus === value}
                  className={`rounded-full px-4 py-2 text-sm font-medium ${reviewStatus === value ? "bg-brand text-brand-foreground" : "bg-muted text-muted-foreground"}`}
                  onClick={() => setReviewStatus(value ?? "")}
                >
                  {label}
                </button>
              ))}
            </div>
            <div className="flex flex-wrap items-end gap-3">
              <label className="min-w-0 flex-1 text-sm">
                Find a recommendation
                <input
                  type="search"
                  placeholder="Student name or opportunity"
                  className="mt-1 block w-full rounded-lg border p-2.5"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </label>
              {[...new Set(data.data.map((r) => r.program))].length > 1 && (
                <label className="text-sm">
                  Program
                  <select
                    aria-label="Program"
                    className="mt-1 block rounded-lg border p-2.5"
                    value={program}
                    onChange={(e) => setProgram(e.target.value)}
                  >
                    <option value="">All assigned programs</option>
                    {[...new Set(data.data.map((r) => r.program))].map((code) => (
                      <option key={code}>{code}</option>
                    ))}
                  </select>
                </label>
              )}
              {(search || program || reviewStatus) && (
                <Button
                  variant="ghost"
                  onClick={() => {
                    setSearch("");
                    setProgram("");
                    setReviewStatus("");
                  }}
                >
                  Reset
                </Button>
              )}
            </div>
          </div>
          <p className="mb-3 text-xs text-muted-foreground">
            {visible.length} of {data.data.length} records on this page. Search and filters apply to
            this page. Unreviewed recommendations appear first.
          </p>
          <Card>
            {!visible.length ? (
              <p>No recommendations match this page and filters.</p>
            ) : (
              <Table
                head={[
                  "Student",
                  "Program",
                  "Recommended host",
                  "Similarity score",
                  "Distance",
                  "Slots",
                  "MOA",
                  "Review status",
                  "Action",
                ]}
              >
                {visible.map((r) => (
                  <Row key={r.id}>
                    <td className="font-semibold">{r.student_name}</td>
                    <td>{r.program}</td>
                    <td>
                      <span className="font-medium">{r.host_name ?? "Not recorded"}</span>
                      <p className="text-xs text-muted-foreground">{r.opportunity_title}</p>
                    </td>
                    <td>
                      <span className="rounded-full bg-muted px-2 py-1 font-semibold tabular-nums">
                        {formatSimilarity(r.similarity_score)}
                      </span>
                    </td>
                    <td>{r.distance_km == null ? "Unknown" : `${r.distance_km.toFixed(1)} km`}</td>
                    <td>{r.available_slots ?? "—"}</td>
                    <td>
                      <Pill>{r.moa_status ?? "Unknown"}</Pill>
                    </td>
                    <td>
                      <span
                        className={`rounded-full px-3 py-1 text-xs ${r.history.length ? "bg-success-soft text-success" : "bg-warn-soft text-warn"}`}
                      >
                        {r.history.length ? "Previously reviewed" : "Awaiting review"}
                      </span>
                      <p className="mt-2 text-xs text-muted-foreground">
                        {r.history.length} recorded judgments
                      </p>
                    </td>
                    <td>
                      <Button
                        variant="outline"
                        aria-label={`Review recommendation #${r.id}`}
                        onClick={() => setSelected(r.id)}
                      >
                        Review
                      </Button>
                    </td>
                  </Row>
                ))}
              </Table>
            )}
          </Card>
          {record && (
            <div>
              <RecommendationReview
                key={record.id}
                record={record}
                close={() => setSelected(null)}
                saved={() => {
                  setNotice(
                    "Judgment saved. Placement approval remains a separate action in Pending approvals.",
                  );
                  setRevision((v) => v + 1);
                }}
              />
            </div>
          )}
          {data.last_page > 1 && (
            <div className="mt-4 flex items-center gap-3">
              <Button disabled={page === 1} onClick={() => setPage((v) => v - 1)}>
                Previous
              </Button>
              <span>
                Page {page} of {data.last_page}
              </span>
              <Button disabled={page === data.last_page} onClick={() => setPage((v) => v + 1)}>
                Next
              </Button>
            </div>
          )}
        </>
      )}
    </>
  );
}
function RecommendationReview({
  record,
  saved,
  close,
}: {
  record: Review;
  saved: () => void;
  close: () => void;
}) {
  const fields = record.explanation
    .split("\n")
    .map((line) => {
      const split = line.indexOf(":");
      return split < 0 ? null : [line.slice(0, split).trim(), line.slice(split + 1).trim()];
    })
    .filter((field): field is string[] => !!field);
  const keyFields = fields.filter(([key]) =>
    [
      "Opportunity",
      "Host",
      "Straight-line distance",
      "Available slots at generation",
      "Agreement status at generation",
      "Tasks",
    ].includes(key ?? ""),
  );
  const [judgment, setJudgment] = useState("uncertain"),
    [reason, setReason] = useState(""),
    [confirm, setConfirm] = useState(false),
    [busy, setBusy] = useState(false),
    [error, setError] = useState<FormError | null>(null);
  return (
    <FormModal
      open
      size="lg"
      onClose={close}
      title={`Review recommendation for ${record.student_name}`}
      description={record.program}
      submitLabel="Save judgment"
      busy={busy}
      submitDisabled={!confirm || reason.trim().length < 10}
      error={error}
      onSubmit={async () => {
        setBusy(true);
        setError(null);
        try {
          await mutate(`/recommendation-reviews/${record.id}`, {
            judgment,
            reason: reason.trim(),
            confirm,
          });
          saved();
          toast.success("Judgment saved");
        } catch (cause) {
          setError(toFormError(cause, "Unable to save judgment."));
        } finally {
          setBusy(false);
        }
      }}
    >
      <FormSection title="Recommended placement">
        {keyFields.length > 0 && (
          <dl className="grid gap-4 rounded-xl bg-muted/40 p-4 sm:grid-cols-2">
            {keyFields.map(([key, value]) => (
              <div key={key} className={key === "Tasks" ? "sm:col-span-2" : ""}>
                <dt className="text-xs text-muted-foreground">
                  {key === "Available slots at generation"
                    ? "Slots when generated"
                    : key === "Agreement status at generation"
                      ? "MOA when generated"
                      : key}
                </dt>
                <dd className="mt-1 break-words text-sm font-medium">{value}</dd>
              </div>
            ))}
          </dl>
        )}
        <details className="rounded-lg border p-3">
          <summary className="cursor-pointer text-sm font-medium">Full match explanation</summary>
          <p className="mt-3 whitespace-pre-wrap break-words text-sm text-muted-foreground">
            {record.explanation || "No explanation recorded."}
          </p>
        </details>
        <p className="text-sm text-muted-foreground">
          These are saved recommendation details. Check current capacity and MOA coverage before
          approving a placement.
        </p>
      </FormSection>
      <FormSection title="Your judgment">
        <FormField label="Suitability judgment" required>
          <select
            aria-label="Suitability judgment"
            name="judgment"
            disabled={busy}
            className={inputClass}
            value={judgment}
            onChange={(e) => setJudgment(e.target.value)}
          >
            <option value="uncertain">Uncertain</option>
            <option value="suitable">Suitable</option>
            <option value="unsuitable">Unsuitable</option>
          </select>
        </FormField>
        <FormField
          label="Supporting reason"
          required
          helper={`${reason.trim().length} / 5000 characters. Minimum 10.`}
        >
          <textarea
            required
            name="reason"
            disabled={busy}
            minLength={10}
            maxLength={5000}
            className={inputClass}
            rows={4}
            placeholder="Explain why this recommendation is suitable, unsuitable, or needs more information."
            value={reason}
            onChange={(e) => setReason(e.target.value)}
          />
        </FormField>
        <label className="flex items-start gap-2 text-sm">
          <input
            type="checkbox"
            name="confirm"
            required
            disabled={busy}
            className="mt-1"
            checked={confirm}
            onChange={(e) => setConfirm(e.target.checked)}
          />
          I confirm this judgment and its supporting reason.
        </label>
      </FormSection>
      <details className="border-t pt-4">
        <summary className="cursor-pointer text-sm font-semibold">
          Previous reviews ({record.history.length})
        </summary>
        {record.history.length === 0 ? (
          <p className="mt-2 text-sm">No judgments recorded.</p>
        ) : (
          <ul className="mt-2 space-y-2 text-sm">
            {record.history.map((h) => (
              <li key={h.id}>
                <span className="font-semibold capitalize">{h.judgment}</span>
                <p className="whitespace-pre-wrap break-words">{h.reason}</p>
                <time className="text-xs text-muted-foreground">
                  {new Date(h.approved_at).toLocaleString("en-PH", { timeZone: "Asia/Manila" })} ·
                  Manila time
                </time>
              </li>
            ))}
          </ul>
        )}
      </details>
    </FormModal>
  );
}

function EnrollmentEditor({
  record,
  onClose,
  onSaved,
}: {
  record: Enrollment;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [value, setValue] = useState({
    year_level: String(record.year_level ?? ""),
    enrolled_on: record.enrolled_on?.slice(0, 10) ?? "",
    target_completion_on: record.target_completion_on?.slice(0, 10) ?? "",
  });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  return (
    <FormModal
      open
      onClose={onClose}
      title={`Edit enrollment: ${studentName(record.student.user)}`}
      description={`${record.program_term.program.code} · ${record.program_term.academic_term.name}. Identity, program and placement are not changed here.`}
      submitLabel="Save changes"
      busy={busy}
      error={error}
      onSubmit={async () => {
        setBusy(true);
        setError(null);
        try {
          await mutate(
            `/enrollments/${record.id}`,
            {
              year_level: Number(value.year_level),
              enrolled_on: value.enrolled_on,
              target_completion_on: value.target_completion_on || null,
            },
            "PATCH",
          );
          onSaved();
          toast.success("Enrollment updated");
        } catch (cause) {
          setError(toFormError(cause, "Unable to update the enrollment."));
        } finally {
          setBusy(false);
        }
      }}
    >
      <FormGrid>
        <FormField label="Year level" required>
          <input
            type="number"
            name="year_level"
            min="1"
            max="10"
            required
            className={inputClass}
            value={value.year_level}
            onChange={(event) => setValue((v) => ({ ...v, year_level: event.target.value }))}
          />
        </FormField>
        <FormField label="Enrolled on" required>
          <input
            type="date"
            name="enrolled_on"
            required
            className={inputClass}
            value={value.enrolled_on}
            onChange={(event) => setValue((v) => ({ ...v, enrolled_on: event.target.value }))}
          />
        </FormField>
        <FormField label="Target completion">
          <input
            type="date"
            name="target_completion_on"
            className={inputClass}
            value={value.target_completion_on}
            onChange={(event) =>
              setValue((v) => ({ ...v, target_completion_on: event.target.value }))
            }
          />
        </FormField>
      </FormGrid>
    </FormModal>
  );
}
