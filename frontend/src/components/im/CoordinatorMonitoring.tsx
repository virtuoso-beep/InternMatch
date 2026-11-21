import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button, Card, PageHeader, Pill, Row, Table, studentName } from "./ui";
import { PlacementMonitoring, type Placement, type Monitoring } from "./MonitoringWorkspace";

type Assessment = {
  id: number;
  period: string;
  status: string;
  weighted_percentage: number | null;
  evaluator: { name: string };
};
type RecordRow = {
  placement: Placement;
  progress?: Monitoring["progress"];
  evaluations?: Assessment[];
  error?: string;
};
export function CoordinatorMonitoring({ mode }: { mode: "progress" | "evaluations" }) {
  const [rows, setRows] = useState<RecordRow[] | null>(null);
  const [page, setPage] = useState(1),
    [last, setLast] = useState(1),
    [revision, setRevision] = useState(0);
  const [error, setError] = useState(""),
    [search, setSearch] = useState(""),
    [filter, setFilter] = useState("all");
  const [selected, setSelected] = useState<Placement | null>(null);
  useEffect(() => {
    let active = true;
    setRows(null);
    setError("");
    setSelected(null);
    async function load() {
      // One request per page: the server includes progress or submitted evaluations for each row.
      const result = await api<{
        data: (Placement & { progress?: Monitoring["progress"]; evaluations?: Assessment[] })[];
        last_page: number;
      }>(`/placements?page=${page}&include=${mode}`);
      const records: RecordRow[] = result.data.map((placement) =>
        mode === "progress"
          ? { placement, ...(placement.progress ? { progress: placement.progress } : {}) }
          : { placement, evaluations: placement.evaluations ?? [] },
      );
      if (active) {
        setRows(records);
        setLast(result.last_page);
      }
    }
    void load().catch((cause: Error) => {
      if (active) setError(cause.message);
    });
    return () => {
      active = false;
    };
  }, [page, revision, mode]);
  const visible = (rows ?? []).filter(
    (row) =>
      `${studentName(row.placement.student_enrollment.student.user)} ${row.placement.student_enrollment.program_term.program.code} ${row.placement.host_establishment.name}`
        .toLowerCase()
        .includes(search.trim().toLowerCase()) &&
      (filter === "all" ||
        (filter === "attention"
          ? !!row.progress?.flags.length
          : filter === "missing"
            ? !row.error && row.evaluations?.length === 0
            : !!row.error)),
  );
  return (
    <>
      <PageHeader
        title={mode === "progress" ? "Internship progress" : "Evaluations"}
        subtitle={
          mode === "progress"
            ? "Certified hours and recorded monitoring flags for your assigned programs."
            : "Submitted supervisor assessments, using each program’s configured rubric."
        }
        action={
          <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
            Refresh records
          </Button>
        }
      />
      {error && (
        <p role="alert">
          {error} <Button onClick={() => setRevision((v) => v + 1)}>Try again</Button>
        </p>
      )}
      {!rows && !error && <p role="status">Loading placement details…</p>}
      {rows && (
        <>
          <div className="mb-5 flex flex-wrap gap-3">
            <label className="text-sm">
              Search this page
              <input
                className="ml-2 rounded border p-2"
                type="search"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </label>
            <label className="text-sm">
              Show
              <select
                aria-label="Show records"
                className="ml-2 rounded border p-2"
                value={filter}
                onChange={(e) => setFilter(e.target.value)}
              >
                <option value="all">All records</option>
                {mode === "progress" ? (
                  <option value="attention">Attention flags</option>
                ) : (
                  <option value="missing">No submitted evaluation</option>
                )}
                <option value="error">Could not load</option>
              </select>
            </label>
            <Button
              variant="outline"
              onClick={() => {
                setSearch("");
                setFilter("all");
              }}
            >
              Clear filters
            </Button>
          </div>
          <p className="mb-3 text-xs text-muted-foreground">
            {visible.length} of {rows.length} placement records on this page. Hours include prior
            placements in the same enrollment.
          </p>
          <Card>
            {!visible.length ? (
              <p>No records match these filters.</p>
            ) : (
              <Table
                head={
                  mode === "progress"
                    ? [
                        "Student / program",
                        "Host",
                        "Certified hours",
                        "Completion",
                        "Monitoring",
                        "Action",
                      ]
                    : ["Intern / program", "Host", "Period / submitted score", "Action"]
                }
              >
                {visible.map((row) => {
                  const p = row.progress;
                  const percent = p?.required_hours
                    ? Math.min(100, (p.completed_minutes / 60 / p.required_hours) * 100)
                    : null;
                  return (
                    <Row key={row.placement.id}>
                      <td>
                        <strong>
                          {studentName(row.placement.student_enrollment.student.user)}
                        </strong>
                        <p>
                          {row.placement.student_enrollment.program_term.program.code} ·{" "}
                          {row.placement.status}
                        </p>
                      </td>
                      <td>{row.placement.host_establishment.name}</td>
                      {mode === "progress" ? (
                        <>
                          <td>
                            {p
                              ? `${(p.completed_minutes / 60).toFixed(1)} / ${p.required_hours ?? "Unconfirmed"}`
                              : "Unavailable"}
                          </td>
                          <td>
                            {percent === null ? (
                              "Not configured"
                            ) : (
                              <>
                                <progress
                                  aria-label={`${studentName(row.placement.student_enrollment.student.user)} completion`}
                                  max={100}
                                  value={percent}
                                  className="h-2 w-28 accent-success"
                                />
                                <p>{percent.toFixed(0)}%</p>
                              </>
                            )}
                          </td>
                          <td>
                            {row.error ? (
                              <span role="alert">{row.error}</span>
                            ) : (
                              <>
                                <Pill tone={p?.flags.length ? "warn" : "muted"}>
                                  {p?.flags.length
                                    ? `${p.flags.length} attention flags`
                                    : p?.hours_rule_configured
                                      ? "No current flags"
                                      : "Rules pending"}
                                </Pill>
                                {p?.flags.map((f) => (
                                  <p className="mt-1 text-xs" key={f.code}>
                                    {f.description}
                                  </p>
                                ))}
                              </>
                            )}
                          </td>
                        </>
                      ) : (
                        <td>
                          {row.error ? (
                            <span role="alert">{row.error}</span>
                          ) : row.evaluations?.length ? (
                            row.evaluations.map((e) => (
                              <p key={e.id}>
                                {e.period} ·{" "}
                                {e.weighted_percentage === null
                                  ? "Score unavailable"
                                  : `${Number(e.weighted_percentage).toFixed(1)}%`}{" "}
                                · {e.evaluator.name}
                              </p>
                            ))
                          ) : (
                            "No submitted evaluation"
                          )}
                        </td>
                      )}
                      <td>
                        <Button variant="outline" onClick={() => setSelected(row.placement)}>
                          {mode === "progress" ? "View logs and journals" : "View rubric details"}
                        </Button>
                      </td>
                    </Row>
                  );
                })}
              </Table>
            )}
          </Card>
          {selected && (
            <div className="mt-5">
              <Button variant="outline" onClick={() => setSelected(null)}>
                Close details
              </Button>
              <PlacementMonitoring
                key={`${selected.id}-${mode}`}
                placement={selected}
                section={mode === "evaluations" ? "Evaluations" : "all"}
              />
            </div>
          )}
          {last > 1 && (
            <div className="mt-4 flex items-center gap-3">
              <Button disabled={page === 1} onClick={() => setPage((p) => p - 1)}>
                Previous
              </Button>
              <span>
                Page {page} of {last}
              </span>
              <Button disabled={page === last} onClick={() => setPage((p) => p + 1)}>
                Next
              </Button>
            </div>
          )}
        </>
      )}
    </>
  );
}
