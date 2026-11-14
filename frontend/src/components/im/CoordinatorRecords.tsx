import { useEffect, useRef, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, PageHeader, Row, Table, Pill, statusTone } from "./ui";
import { RecommendationBatch } from "./RecommendationBatch";
import { ExcelImportButton } from "./ExcelImportButton";

import { ImportHistory } from "./ImportHistory";
import { StudentHistory } from "./StudentHistory";

type Enrollment = {
  id: number;
  status: string;
  student: { student_number: string; user: { name: string; email: string | null } };
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
        `${r.student.user.name} ${r.student.student_number} ${r.student.user.email ?? ""} ${r.program_term.academic_term.name} ${r.current_placement?.host_establishment.name ?? ""}`
          .toLowerCase()
          .includes(search.trim().toLowerCase()),
    )
    .sort((a, b) =>
      sort === "program"
        ? a.program_term.program.code.localeCompare(b.program_term.program.code) ||
          a.student.user.name.localeCompare(b.student.user.name)
        : a.student.user.name.localeCompare(b.student.user.name),
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
                className={`rounded-full border px-4 py-2 text-sm ${filter === option ? "bg-brand text-white border-brand" : "bg-card hover:bg-muted"}`}
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
                ]}
              >
                {visible.map((r) => (
                  <Row key={r.id}>
                    <td className="font-semibold">
                      <button
                        className="text-brand underline"
                        onClick={() => setSelectedStudent(r.id)}
                      >
                        {r.student.user.name}
                      </button>
                      <span className="block text-xs font-normal text-muted-foreground">
                        {r.student.student_number}
                      </span>
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
                  </Row>
                ))}
              </Table>
            )}
          </Card>
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
          className="mb-4 rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"
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
                  className={`rounded-full px-4 py-2 text-sm font-medium ${reviewStatus === value ? "bg-brand text-white" : "bg-muted text-muted-foreground"}`}
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
                  "Similarity",
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
                      <span className="rounded-full bg-emerald-50 px-2 py-1 font-semibold text-emerald-800">
                        {r.similarity_score == null
                          ? "—"
                          : `${(r.similarity_score * 100).toFixed(1)}%`}
                      </span>
                    </td>
                    <td>{r.distance_km == null ? "Unknown" : `${r.distance_km.toFixed(1)} km`}</td>
                    <td>{r.available_slots ?? "—"}</td>
                    <td>
                      <Pill>{r.moa_status ?? "Unknown"}</Pill>
                    </td>
                    <td>
                      <span
                        className={`rounded-full px-3 py-1 text-xs ${r.history.length ? "bg-emerald-50 text-emerald-800" : "bg-amber-50 text-amber-800"}`}
                      >
                        {r.history.length ? "Previously reviewed" : "Awaiting review"}
                      </span>
                      <p className="mt-2 text-xs text-muted-foreground">
                        {r.history.length} recorded judgments
                      </p>
                    </td>
                    <td>
                      <Button variant="outline" onClick={() => setSelected(r.id)}>
                        Review recommendation #{r.id}
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
  const dialog = useRef<HTMLDialogElement>(null);
  useEffect(() => {
    const element = dialog.current;
    const previous = document.activeElement as HTMLElement | null;
    element?.showModal();
    return () => {
      element?.close();
      previous?.focus();
    };
  }, []);
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
    [error, setError] = useState("");
  return (
    <dialog
      ref={dialog}
      aria-labelledby="review-panel-title"
      onCancel={(event) => {
        event.preventDefault();
        if (!busy) close();
      }}
      className="fixed inset-y-0 left-auto right-0 m-0 h-dvh max-h-none w-full max-w-2xl overflow-y-auto border-l border-border bg-card p-0 text-foreground shadow-2xl backdrop:bg-black/40"
    >
      <div className="sticky top-0 z-10 flex items-start justify-between gap-3 border-b bg-card p-5">
        <div>
          <p className="text-xs uppercase text-muted-foreground">Recommendation review</p>
          <h2 id="review-panel-title" className="mt-1 text-xl font-semibold">
            {record.student_name}
          </h2>
          <p className="text-sm text-muted-foreground">{record.program}</p>
        </div>
      </div>
      <div className="p-5">
        <h3 className="font-semibold">Recommended placement</h3>
        {keyFields.length > 0 && (
          <dl className="mt-3 grid gap-4 rounded-xl bg-muted/40 p-4 sm:grid-cols-2">
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
        <details className="mt-3 rounded-lg border p-3">
          <summary className="cursor-pointer text-sm font-medium">Full match explanation</summary>
          <p className="mt-3 whitespace-pre-wrap break-words text-sm text-muted-foreground">
            {record.explanation || "No explanation recorded."}
          </p>
        </details>
        <p className="mt-3 text-sm text-muted-foreground">
          These are saved recommendation details. Check current capacity and MOA coverage before
          approving a placement.
        </p>
        <form
          className="mt-4 space-y-3"
          aria-label={`Review recommendation ${record.id}`}
          onSubmit={async (e) => {
            e.preventDefault();
            setBusy(true);
            setError("");
            try {
              await mutate(`/recommendation-reviews/${record.id}`, {
                judgment,
                reason: reason.trim(),
                confirm,
              });
              saved();
            } catch (cause) {
              setError(cause instanceof Error ? cause.message : "Unable to save judgment.");
            } finally {
              setBusy(false);
            }
          }}
        >
          <fieldset disabled={busy} className="space-y-3">
            <label className="block text-sm">
              Suitability judgment
              <select
                aria-label="Suitability judgment"
                className="mt-1 block rounded border p-2"
                value={judgment}
                onChange={(e) => setJudgment(e.target.value)}
              >
                <option value="uncertain">Uncertain</option>
                <option value="suitable">Suitable</option>
                <option value="unsuitable">Unsuitable</option>
              </select>
            </label>
            <label className="block text-sm">
              Supporting reason
              <textarea
                required
                minLength={10}
                maxLength={5000}
                className="mt-1 block w-full rounded border p-2"
                rows={4}
                placeholder="Explain why this recommendation is suitable, unsuitable, or needs more information."
                value={reason}
                onChange={(e) => setReason(e.target.value)}
              />
            </label>
            <p className="text-xs text-muted-foreground">
              {reason.trim().length} / 5000 characters · minimum 10
            </p>
            <label className="block text-sm">
              <input
                type="checkbox"
                required
                checked={confirm}
                onChange={(e) => setConfirm(e.target.checked)}
              />{" "}
              I confirm this judgment and its supporting reason.
            </label>
            {error && <p role="alert">{error}</p>}
            <div className="flex flex-wrap gap-3 pt-2">
              <Button type="submit" disabled={busy || !confirm || reason.trim().length < 10}>
                {busy ? "Saving…" : "Save judgment"}
              </Button>
              <Button variant="outline" disabled={busy} onClick={close}>
                Cancel
              </Button>
            </div>
          </fieldset>
        </form>
        <details className="mt-5 border-t pt-4">
          <summary className="cursor-pointer text-sm font-semibold">
            Previous reviews ({record.history.length})
          </summary>
          {record.history.length === 0 ? (
            <p className="text-sm">No judgments recorded.</p>
          ) : (
            <ul className="space-y-2 text-sm">
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
      </div>
    </dialog>
  );
}
