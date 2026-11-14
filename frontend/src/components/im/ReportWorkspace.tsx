import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { useSessionUser } from "./SessionGuard";
import { Button, Card, CardTitle, PageHeader, Pill, Row, Table } from "./ui";

type Report = {
  id: number;
  kind: string;
  program_term_id: number;
  approved_at: string | null;
  created_at: string;
  content_hash: string;
  payload?: {
    program: string;
    term: string;
    columns: string[];
    rows: (string | number | null)[][];
  };
};
type Index = {
  cohorts: { id: number; program: { code: string }; academic_term: { name: string } }[];
  kinds: string[];
  reports: { data: Report[]; last_page: number };
};
const labels: Record<string, string> = {
  placement: "Placements",
  progress: "Student progress",
  hours: "Internship hours",
  requirements: "Requirements",
  evaluation: "Supervisor evaluations",
  recommendation: "Recommendations",
  summary: "Program summary",
};
export function ReportWorkspace({
  title = "Reports",
  presentation = "cards",
}: {
  title?: string;
  presentation?: "cards" | "accreditation" | "export";
}) {
  const user = useSessionUser();
  const [data, setData] = useState<Index | null>(null),
    [cohort, setCohort] = useState(""),
    [kind, setKind] = useState("progress"),
    [page, setPage] = useState(1),
    [selected, setSelected] = useState<Report | null>(null),
    [error, setError] = useState(""),
    [busy, setBusy] = useState(false),
    [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    setError("");
    setData(null);
    api<Index>(`/reports?page=${page}&kind=${kind}${cohort ? `&program_term_id=${cohort}` : ""}`)
      .then((d) => {
        if (active) setData(d);
      })
      .catch((e: Error) => {
        if (active) setError(e.message);
      });
    return () => {
      active = false;
    };
  }, [cohort, kind, page, revision]);
  async function run(action: () => Promise<{ data: Report }>) {
    setBusy(true);
    setError("");
    try {
      const result = await action();
      setSelected(result.data);
      setRevision((v) => v + 1);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Unable to load report.");
    } finally {
      setBusy(false);
    }
  }
  return (
    <>
      <PageHeader
        title={title}
        subtitle="Program-scoped report snapshots. Deans can access coordinator-approved reports."
      />
      {error && (
        <p role="alert" className="text-destructive">
          {error}
        </p>
      )}
      {!data && !error && <p role="status">Loading reports…</p>}
      <div className="flex flex-wrap items-end gap-3">
        <label className="text-sm">
          Program cohort
          <select
            aria-label="Report cohort"
            className="mt-1 block rounded-lg border border-input bg-background p-2"
            disabled={busy}
            value={cohort}
            onChange={(e) => {
              setCohort(e.target.value);
              setPage(1);
              setSelected(null);
            }}
          >
            <option value="">All assigned cohorts</option>
            {data?.cohorts.map((c) => (
              <option key={c.id} value={c.id}>
                {c.program.code} · {c.academic_term.name}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm">
          Report type
          <select
            aria-label="Report type"
            className="mt-1 block rounded-lg border border-input bg-background p-2"
            disabled={busy}
            value={kind}
            onChange={(e) => {
              setKind(e.target.value);
              setPage(1);
              setSelected(null);
            }}
          >
            {Object.entries(labels).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </select>
        </label>
        {user.role === "coordinator" && (
          <Button
            disabled={busy || !cohort}
            onClick={() =>
              void run(() => mutate("/reports", { program_term_id: Number(cohort), kind }))
            }
          >
            Generate report
          </Button>
        )}
        <Button
          variant="outline"
          disabled={busy}
          onClick={() => {
            setSelected(null);
            setRevision((v) => v + 1);
          }}
        >
          Refresh reports
        </Button>
        <Button
          variant="outline"
          disabled={busy}
          onClick={() => {
            setCohort("");
            setKind("progress");
            setPage(1);
            setSelected(null);
          }}
        >
          Reset filters
        </Button>
      </div>
      {user.role === "dean" && (
        <p className="my-4 text-sm text-muted-foreground">
          Only coordinator-approved snapshots in your assigned programs are available. CSV is the
          supported export format. Select a report to inspect its records before downloading.
        </p>
      )}
      {presentation === "accreditation" && data && data.reports.data.length > 0 ? (
        <Card className="mt-5">
          <Table head={["Report", "Coverage", "Approved", "Action"]}>
            {data.reports.data.map((report) => {
              const scope = data.cohorts.find((c) => c.id === report.program_term_id);
              return (
                <Row key={report.id}>
                  <td className="font-semibold">
                    {labels[report.kind]} · #{report.id}
                  </td>
                  <td>
                    {scope
                      ? `${scope.program.code} · ${scope.academic_term.name}`
                      : "Assigned cohort"}
                  </td>
                  <td>
                    {report.approved_at
                      ? new Date(report.approved_at).toLocaleDateString("en-PH")
                      : "Not approved"}
                  </td>
                  <td>
                    <Button
                      variant="outline"
                      disabled={busy}
                      onClick={() => void run(() => api(`/reports/${report.id}`))}
                    >
                      View report {report.id}
                    </Button>
                  </td>
                </Row>
              );
            })}
          </Table>
        </Card>
      ) : (
        <div className="mt-5 grid gap-4 md:grid-cols-2">
          {data?.reports.data.map((report) => (
            <Card key={report.id}>
              <div className="flex items-center justify-between gap-3">
                <div>
                  <p className="font-semibold">
                    {labels[report.kind]} · #{report.id}
                  </p>
                  <p className="mt-1 text-sm text-muted-foreground">
                    {new Date(report.created_at).toLocaleString()}
                  </p>
                  <p className="mt-1 text-sm text-muted-foreground">
                    {data?.cohorts.find((c) => c.id === report.program_term_id)?.program.code} ·{" "}
                    {data?.cohorts.find((c) => c.id === report.program_term_id)?.academic_term.name}
                  </p>
                  {presentation === "export" && (
                    <p className="text-xs text-muted-foreground">CSV · saved snapshot</p>
                  )}
                  <Pill tone={report.approved_at ? "success" : "warn"}>
                    {report.approved_at ? "Approved" : "Draft"}
                  </Pill>
                </div>
                <Button
                  variant="outline"
                  disabled={busy}
                  onClick={() => void run(() => api(`/reports/${report.id}`))}
                >
                  View report {report.id}
                </Button>
              </div>
            </Card>
          ))}
        </div>
      )}
      {data?.reports.data.length === 0 && (
        <Card>No {user.role === "dean" ? "approved " : ""}reports match these filters.</Card>
      )}
      {data && data.reports.last_page > 1 && (
        <div className="flex gap-3">
          <Button
            disabled={busy || page === 1}
            onClick={() => {
              setSelected(null);
              setPage((p) => p - 1);
            }}
          >
            Previous
          </Button>
          <span>
            {page} / {data.reports.last_page}
          </span>
          <Button
            disabled={busy || page === data.reports.last_page}
            onClick={() => {
              setSelected(null);
              setPage((p) => p + 1);
            }}
          >
            Next
          </Button>
        </div>
      )}
      {selected?.payload && (
        <Card>
          <CardTitle>
            {labels[selected.kind]} · {selected.payload.program} · {selected.payload.term}
          </CardTitle>
          <p className="mb-3 text-sm">
            {selected.payload.rows.length} records ·{" "}
            {selected.approved_at ? "Approved snapshot" : "Draft for coordinator review"}
          </p>
          <div className="mb-4 flex flex-wrap gap-3">
            <a
              className="inline-flex items-center rounded-lg border border-border px-4 py-2 text-sm font-semibold"
              href={`/api/v1/reports/${selected.id}/download`}
            >
              Download CSV
            </a>
            {user.role === "coordinator" && !selected.approved_at && (
              <Button
                disabled={busy}
                onClick={() => void run(() => mutate(`/reports/${selected.id}/approve`, {}))}
              >
                Approve report for dean
              </Button>
            )}
          </div>
          <Button variant="outline" disabled={busy} onClick={() => setSelected(null)}>
            Close preview
          </Button>
          <Table head={selected.payload.columns.map((c) => c.replaceAll("_", " "))}>
            {selected.payload.rows.slice(0, 100).map((row, i) => (
              <Row key={i}>
                {row.map((value, j) => (
                  <td key={j}>{value === null ? "Not recorded" : String(value)}</td>
                ))}
              </Row>
            ))}
          </Table>
          {selected.payload.rows.length > 100 && (
            <p className="mt-3 text-sm">
              Preview shows the first 100 records; the CSV contains all records.
            </p>
          )}
        </Card>
      )}
    </>
  );
}
