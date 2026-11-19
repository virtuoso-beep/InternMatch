import { useCallback, useEffect, useRef, useState } from "react";
import { api, mutate } from "@/lib/api";
import { SYNTHETIC_LABEL, withSynthetic } from "@/lib/synthetic";
import {
  Button,
  Card,
  Pill,
  downloadText,
  formatSimilarity,
  studentName,
  formatStatus,
} from "./ui";
import { SyntheticToggle } from "./SyntheticToggle";
type Student = {
  id: number;
  status: string;
  is_synthetic?: boolean | number;
  student: { user: { name: string } };
  program_term: { program: { code: string } };
  current_placement: { status: string } | null;
};
type Match = {
  id?: number;
  opportunity_id: number;
  similarity_score: number;
  rank: number;
  distance_km?: number | null;
  snapshot?: { host_name?: string; opportunity_title?: string };
};
type Comparison = { host: string | null; similarity: number; distance: number | null };
type Result = {
  enrollment_id?: number;
  before_id?: number | undefined;
  after_id?: number | undefined;
  previous?: Comparison | null;
  current?: Comparison | null;
  student: string;
  status: string;
  reason: string;
  synthetic?: boolean;
};
export function RecommendationBatch({ done }: { done: () => void }) {
  const [students, setStudents] = useState<Student[]>([]),
    [program, setProgram] = useState("");
  const [open, setOpen] = useState(false),
    [busy, setBusy] = useState(false),
    [error, setError] = useState("");
  const [results, setResults] = useState<Result[]>([]);
  // Synthetic students are left out of runs and history unless "Include synthetic data" is on.
  const [includeSynthetic, setIncludeSynthetic] = useState(false);
  const [search, setSearch] = useState("");
  const [resultFilter, setResultFilter] = useState("");
  const visibleResults = results.filter(
    (row) =>
      (!resultFilter || row.status === resultFilter) &&
      `${row.student} ${row.previous?.host ?? ""} ${row.current?.host ?? ""}`
        .toLowerCase()
        .includes(search.trim().toLowerCase()),
  );
  const [history, setHistory] = useState<
    { id: number; created_at: string; contains_synthetic?: boolean; results: Result[] }[]
  >([]);
  const [historyPage, setHistoryPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [historyError, setHistoryError] = useState("");
  const loadHistory = useCallback(
    async (page = historyPage) => {
      try {
        const response = await api<{ data: typeof history; last_page: number }>(
          withSynthetic(`/recommendation-batch-reports?page=${page}`, includeSynthetic),
        );
        setHistory(response.data);
        setLastPage(response.last_page);
        setHistoryError("");
      } catch (e) {
        setHistoryError(e instanceof Error ? e.message : "Cannot load history.");
      }
    },
    [historyPage, includeSynthetic],
  );
  useEffect(() => {
    void loadHistory(historyPage);
  }, [historyPage, loadHistory]);
  const dialog = useRef<HTMLDialogElement>(null);
  useEffect(() => {
    if (open) dialog.current?.showModal();
  }, [open]);
  const scope = students.filter(
    (s) => s.program_term.program.code === program && (includeSynthetic || !s.is_synthetic),
  );
  const protectedStudent = (s: Student) => !!s.current_placement || s.status !== "enrolled";
  async function preview() {
    setError("");
    try {
      const response = await api<{ data: Student[] }>("/enrollments");
      setStudents(response.data);
      setProgram(response.data[0]?.program_term.program.code ?? "");
      setOpen(true);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Unable to load batch scope.");
    }
  }
  async function run() {
    setBusy(true);
    setResults([]);
    const output: Result[] = [];
    for (const student of scope) {
      let row: Result;
      if (protectedStudent(student)) {
        row = {
          student: studentName(student.student.user),
          status: "Skipped",
          reason: "Existing placement or closed enrollment protected.",
        };
      } else {
        try {
          const before = await api<{ data: Match[] }>(`/enrollments/${student.id}/recommendations`);
          await mutate(`/enrollments/${student.id}/recommendations`, {});
          const after = await api<{ data: Match[] }>(`/enrollments/${student.id}/recommendations`);
          const signature = (rows: Match[]) =>
            JSON.stringify(rows.map((r) => [r.opportunity_id, r.rank, r.similarity_score]));
          const topBefore = [...before.data].sort((a, b) => a.rank - b.rank)[0];
          const topAfter = [...after.data].sort((a, b) => a.rank - b.rank)[0];
          const describe = (match: Match | undefined): Comparison | null =>
            match
              ? {
                  host: match.snapshot?.host_name ?? null,
                  similarity: match.similarity_score,
                  distance: match.distance_km ?? null,
                }
              : null;
          row = {
            before_id: topBefore?.id,
            after_id: topAfter?.id,
            previous: describe(topBefore),
            current: describe(topAfter),
            student: studentName(student.student.user),
            status: signature(before.data) === signature(after.data) ? "Unchanged" : "Updated",
            reason: after.data.length
              ? "Compared opportunity order and similarity with the previous generation."
              : "No eligible matches returned.",
          };
        } catch (e) {
          row = {
            student: studentName(student.student.user),
            status: "Failed",
            reason: e instanceof Error ? e.message : "Generation failed.",
          };
        }
      }
      row.enrollment_id = student.id;
      row.synthetic = !!student.is_synthetic;
      output.push(row);
      setResults([...output]);
      if (!protectedStudent(student) && output.length < scope.length)
        await new Promise((resolve) => setTimeout(resolve, 6500));
    }
    try {
      await mutate("/recommendation-batch-reports", { program, results: output });
      setHistoryPage(1);
      await loadHistory(1);
    } catch (e) {
      setError(
        "Recommendations processed, but history was not saved. Export these results before leaving. " +
          (e instanceof Error ? e.message : ""),
      );
    }
    setBusy(false);
    setOpen(false);
    done();
  }
  return (
    <div className="mb-5">
      <div className="flex flex-wrap items-center gap-3">
        <Button disabled={busy} onClick={() => void preview()}>
          Regenerate batch
        </Button>
        <SyntheticToggle
          checked={includeSynthetic}
          onChange={(value) => {
            setIncludeSynthetic(value);
            setHistoryPage(1);
          }}
          disabled={busy}
        />
      </div>
      {error && <p role="alert">{error}</p>}
      <details className="my-4 rounded-xl border bg-card p-4">
        <summary className="cursor-pointer font-semibold">Batch history</summary>
        <p className="my-3 text-sm text-muted-foreground">
          Saved coordinator run reports. These record the browser’s processing results, not
          placement approvals. Interrupted runs may be absent.
        </p>
        {historyError && <p role="alert">{historyError}</p>}
        <Button variant="outline" onClick={() => void loadHistory()}>
          Refresh history
        </Button>
        {history.length === 0 && !historyError && <p className="mt-3">No saved runs yet.</p>}
        <div className="mt-3 space-y-2">
          {history.map((batch) => (
            <div
              className="flex flex-wrap items-center justify-between gap-3 rounded border p-3"
              key={batch.id}
            >
              <span>
                Run #{batch.id} ·{" "}
                {new Date(batch.created_at.replace(" ", "T") + "Z").toLocaleString("en-PH", {
                  timeZone: "Asia/Manila",
                })}{" "}
                · {batch.results.length} records{" "}
                {batch.contains_synthetic && <Pill tone="warn">{SYNTHETIC_LABEL}</Pill>}
              </span>
              <Button variant="outline" disabled={busy} onClick={() => setResults(batch.results)}>
                View results
              </Button>
            </div>
          ))}
        </div>
        <div className="mt-3 flex items-center gap-3">
          <Button
            variant="outline"
            disabled={historyPage === 1}
            onClick={() => setHistoryPage((v) => v - 1)}
          >
            Previous runs
          </Button>
          <span>
            {historyPage} / {lastPage}
          </span>
          <Button
            variant="outline"
            disabled={historyPage >= lastPage}
            onClick={() => setHistoryPage((v) => v + 1)}
          >
            Next runs
          </Button>
        </div>
      </details>
      {open && (
        <dialog
          ref={dialog}
          onCancel={(e) => {
            if (busy) e.preventDefault();
            else setOpen(false);
          }}
          aria-labelledby="batch-title"
          className="fixed inset-0 m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-2xl overflow-auto rounded-xl border bg-card p-0 text-foreground shadow-xl backdrop:bg-black/40"
        >
          <h2 id="batch-title" className="bg-brand p-5 text-xl font-semibold text-white">
            Regenerate recommendation batch
          </h2>
          <div className="space-y-4 p-5">
            <label className="block text-sm">
              Program
              <select
                aria-label="Batch program"
                disabled={busy}
                value={program}
                onChange={(e) => setProgram(e.target.value)}
                className="mt-1 w-full rounded border p-2"
              >
                {[...new Set(students.map((s) => s.program_term.program.code))].map((code) => (
                  <option key={code}>{code}</option>
                ))}
              </select>
            </label>
            <p className="rounded-lg bg-brand-soft p-4 text-sm">
              Generate new recommendations for this program only. Existing placements are skipped
              and will not be reassigned by this action.
            </p>
            <div className="grid grid-cols-2 gap-3">
              <Card>{scope.filter((s) => !protectedStudent(s)).length} enrollments to process</Card>
              <Card>{scope.filter(protectedStudent).length} protected / skipped</Card>
            </div>
            <p className="text-sm text-muted-foreground">
              Matching checks competency embeddings, program eligibility, host capacity and MOA
              validity. Missing data may cause individual records to fail. Results do not approve
              placements.
            </p>
            {busy && (
              <p role="status">
                Processed {results.length} of {scope.length}. Keep this window open.
              </p>
            )}
            <div className="flex gap-3">
              <Button variant="outline" disabled={busy} onClick={() => setOpen(false)}>
                Cancel
              </Button>
              <Button disabled={busy || !scope.length} onClick={() => void run()}>
                {busy ? "Generating…" : "Regenerate recommendations"}
              </Button>
            </div>
          </div>
        </dialog>
      )}
      {!!results.length && (
        <Card className="mt-4">
          <h2 className="font-semibold">Batch results {busy ? "— in progress" : "— completed"}</h2>
          <p className="my-2 text-sm text-muted-foreground">
            {results.length} enrollments processed. Individual saved generations remain in
            recommendation history.
          </p>
          <div className="my-3 flex flex-wrap gap-3">
            {["Updated", "Unchanged", "Skipped", "Failed"].map((status) => (
              <span key={status} className="rounded border px-3 py-2 text-sm">
                {status}: {results.filter((r) => r.status === status).length}
              </span>
            ))}
          </div>
          <Button
            variant="outline"
            disabled={busy}
            onClick={() =>
              downloadText(
                results.some((r) => r.synthetic)
                  ? "recommendation-batch-results-contains-synthetic-data.json"
                  : "recommendation-batch-results.json",
                JSON.stringify(results, null, 2),
                "application/json",
              )
            }
          >
            Export results
          </Button>
          <div className="mt-3 overflow-auto">
            <div className="mb-4 flex flex-wrap items-end gap-3">
              <label className="min-w-0 flex-1 text-sm">
                Find student or host
                <input
                  type="search"
                  className="mt-1 block w-full rounded border p-2"
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                />
              </label>
              <label className="text-sm">
                Result
                <select
                  aria-label="Filter batch result"
                  className="mt-1 block rounded border p-2"
                  value={resultFilter}
                  onChange={(event) => setResultFilter(event.target.value)}
                >
                  <option value="">All results</option>
                  {["Updated", "Unchanged", "Skipped", "Failed"].map((value) => (
                    <option key={value}>{value}</option>
                  ))}
                </select>
              </label>
              <Button
                variant="outline"
                onClick={() => {
                  setSearch("");
                  setResultFilter("");
                }}
              >
                Reset comparison filters
              </Button>
            </div>
            <p className="mb-3 text-xs text-muted-foreground">
              Showing {visibleResults.length} of {results.length} results. Export includes the full
              run. Scores are cosine similarity, not placement probabilities.
            </p>
            <table className="w-full text-left text-sm">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Previous top match</th>
                  <th>New top match</th>
                  <th>Result</th>
                  <th>Reason</th>
                </tr>
              </thead>
              <tbody>
                {visibleResults.map((r, i) => (
                  <tr key={i} className="border-t">
                    <td className="p-2">
                      {r.student} {r.synthetic && <Pill tone="warn">Synthetic</Pill>}
                    </td>
                    {[r.previous, r.current].map((match, index) => (
                      <td key={index} className="p-2">
                        {match ? (
                          <>
                            <p className="font-medium">{match.host ?? "Host not recorded"}</p>
                            <p className="text-xs text-muted-foreground">
                              Similarity score {formatSimilarity(match.similarity)} ·{" "}
                              {match.distance == null
                                ? "Distance unknown"
                                : `${match.distance.toFixed(1)} km straight-line`}
                            </p>
                          </>
                        ) : (
                          "Not recorded / no match"
                        )}
                      </td>
                    ))}
                    <td className="p-2">{formatStatus(r.status)}</td>
                    <td className="p-2">{r.reason}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            {visibleResults.length === 0 && (
              <p role="status" className="py-5">
                No comparison results match these filters.
              </p>
            )}
          </div>
        </Card>
      )}
    </div>
  );
}
