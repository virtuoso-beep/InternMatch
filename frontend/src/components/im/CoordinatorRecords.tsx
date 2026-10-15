import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, PageHeader, Row, Table, Pill, statusTone } from "./ui";

type Enrollment = {
  id: number;
  status: string;
  student: { user: { name: string } };
  program_term: { program: { code: string }; academic_term: { name: string } };
  current_placement: null | { status: string; host_establishment: { name: string } };
};
type Review = {
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
          : "No current placement";
  const options = ["All", "Deployed", "Pending approval", "Approved", "No current placement"];
  const filtered = (rows ?? [])
    .filter(
      (r) =>
        (filter === "All" || label(r) === filter) &&
        (!program || r.program_term.program.code === program) &&
        `${r.student.user.name} ${r.program_term.academic_term.name} ${r.current_placement?.host_establishment.name ?? ""}`
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
              Search students, hosts or terms
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
                    <td className="font-semibold">{r.student.user.name}</td>
                    <td>{r.program_term.program.code}</td>
                    <td>{r.program_term.academic_term.name}</td>
                    <td>
                      <Pill tone={statusTone(r.status)}>{r.status}</Pill>
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
            Approved placements await deployment. No current placement can include completed or
            closed enrollments; check the enrollment status.
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
    data?.data.filter(
      (r) =>
        (!program || r.program === program) &&
        `${r.student_name} ${r.explanation}`.toLowerCase().includes(search.trim().toLowerCase()),
    ) ?? [];
  const record = visible.find((r) => r.id === selected);
  return (
    <>
      <PageHeader
        title="Recommendation review"
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
      {!data && !error && <p role="status">Loading recommendations…</p>}
      {data && (
        <>
          <div className="mb-4 flex flex-wrap gap-3">
            <label className="text-sm">
              Search this page
              <input
                type="search"
                className="ml-2 rounded border p-2"
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value);
                  setSelected(null);
                }}
              />
            </label>
            <label className="text-sm">
              Program
              <select
                aria-label="Program"
                className="ml-2 rounded border p-2"
                value={program}
                onChange={(e) => {
                  setProgram(e.target.value);
                  setSelected(null);
                }}
              >
                <option value="">All programs on this page</option>
                {[...new Set(data.data.map((r) => r.program))].sort().map((code) => (
                  <option key={code}>{code}</option>
                ))}
              </select>
            </label>
            <Button
              variant="outline"
              onClick={() => {
                setSearch("");
                setProgram("");
                setSelected(null);
              }}
            >
              Clear filters
            </Button>
          </div>
          <p className="mb-3 text-xs text-muted-foreground">
            {visible.length} of {data.data.length} records on this page. Historical generations may
            contain multiple recommendations for a student.
          </p>
          <Card>
            {!visible.length ? (
              <p>No recommendations match this page and filters.</p>
            ) : (
              <Table head={["Student", "Program", "Judgment history", "Action"]}>
                {visible.map((r) => (
                  <Row key={r.id}>
                    <td className="font-semibold">{r.student_name}</td>
                    <td>{r.program}</td>
                    <td>{r.history.length} recorded judgments</td>
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
            <div className="mt-5">
              <Button variant="outline" onClick={() => setSelected(null)}>
                Close review
              </Button>
              <RecommendationReview
                key={record.id}
                record={record}
                saved={() => setRevision((v) => v + 1)}
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
function RecommendationReview({ record, saved }: { record: Review; saved: () => void }) {
  const [judgment, setJudgment] = useState("uncertain"),
    [reason, setReason] = useState(""),
    [confirm, setConfirm] = useState(false),
    [busy, setBusy] = useState(false),
    [error, setError] = useState("");
  return (
    <Card>
      <h2 className="font-semibold">
        {record.student_name} · {record.program}
      </h2>
      <p className="mt-3 whitespace-pre-wrap text-sm">{record.explanation}</p>
      <form
        className="mt-4 space-y-3"
        aria-label={`Review recommendation ${record.id}`}
        onSubmit={async (e) => {
          e.preventDefault();
          setBusy(true);
          setError("");
          try {
            await mutate(`/recommendation-reviews/${record.id}`, { judgment, reason, confirm });
            saved();
          } catch (cause) {
            setError(cause instanceof Error ? cause.message : "Unable to save judgment.");
          } finally {
            setBusy(false);
          }
        }}
      >
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
            value={reason}
            onChange={(e) => setReason(e.target.value)}
          />
        </label>
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
        <Button type="submit" disabled={busy || !confirm}>
          {busy ? "Saving…" : "Save judgment"}
        </Button>
      </form>
      <h3 className="mt-4 font-semibold">Judgment history</h3>
      {record.history.length === 0 ? (
        <p className="text-sm">No judgments recorded.</p>
      ) : (
        <ul className="space-y-2 text-sm">
          {record.history.map((h) => (
            <li key={h.id}>
              {h.judgment} · {h.reason} · {h.approved_at}
            </li>
          ))}
        </ul>
      )}
    </Card>
  );
}
