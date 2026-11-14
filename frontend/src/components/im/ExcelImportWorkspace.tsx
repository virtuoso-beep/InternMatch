import { useEffect, useState } from "react";
import { api, mutate, upload } from "@/lib/api";
import { Button, Card } from "./ui";
import { useSessionUser } from "./SessionGuard";

import { downloadImportCredentials } from "@/lib/importCredentials";

type Row = Partial<
  Record<
    | "enrolled_on"
    | "year_level"
    | "host_id"
    | "source"
    | "student_id"
    | "name"
    | "host_name"
    | "skip"
    | "keep_existing"
    | "fill_missing"
    | "kind"
    | "opportunity_id",
    string | number | boolean | null
  >
> &
  Record<string, string | number | boolean | null>;
type Sheet = {
  name: string;
  headers: string[];
  mapping: Record<string, number>;
  rows: { source_row: number; cells: string[] }[];
};
type Reference = {
  programs: { id: number; code: string; required_ojt_hours: number | null }[];
  terms: { id: number; code: string; name: string }[];
  hosts: { id: number; name: string; address: string; city: string }[];
  opportunities: {
    id: number;
    title: string;
    host_establishment_id: number;
    academic_term_id: number;
    status: string;
    programs: { id: number }[];
  }[];
  fields: string[];
};
type Preview = {
  counts?: Record<string, number>;
  revision: string;
  rows: {
    row: Row;
    errors: string[];
    warnings: string[];
    action: string;
    matched: Record<string, number>;
  }[];
};
type Result = {
  new_accounts?: number;
  existing_accounts?: number;
  enrollments_created?: number;
  enrollments_updated?: number;
  successful: number;
  updated: number;
  skipped: number;
  failed: number;
  rows: { source: string; student_id: string | null; status: string; message: string }[];
};
const labels: Record<string, string> = {
  first_name: "First Name",
  middle_name: "Middle Name (optional)",
  last_name: "Last Name",
  academic_term: "Academic Term",
  student_id: "Student ID (6 digits)",
  name: "Student full name",
  email: "Student email",
  program: "Program in file",
  year_level: "Year level",
  student_address: "Student address",
  host_code: "Source HTE ID",
  host_name: "HTE name",
  host_address: "HTE address",
  city: "HTE city / location",
  latitude: "HTE latitude",
  longitude: "HTE longitude",
  enrolled_on: "Enrollment date",
  starts_on: "Placement start date",
  ends_on: "Placement end date",
  opportunity_id: "Existing opportunity",
  source_opportunity: "Source opportunity ID",
  placement_status: "Placement status",
  opportunity_title: "New opportunity title",
  tasks: "New opportunity duties",
  capacity: "New opportunity capacity",
  host_capacity: "Confirmed HTE capacity",
  reference_number: "Agreement reference number",
  effective_on: "Agreement effective date",
  expires_on: "Agreement expiry date",
  notes: "Notes",
  source_status: "Status reported in workbook",
};
export type ImportModule = "student" | "host" | "opportunity" | "agreement";
const hostFields = [
  "host_code",
  "host_name",
  "host_address",
  "city",
  "latitude",
  "longitude",
  "program",
];
const moduleFields: Record<ImportModule, string[]> = {
  student: [
    "student_id",
    "first_name",
    "middle_name",
    "last_name",
    "program",
    "academic_term",
    "year_level",
    "name",
    "email",
    "enrolled_on",
    "student_address",
  ],
  host: [...hostFields, "host_capacity"],
  opportunity: [
    ...hostFields,
    "opportunity_title",
    "tasks",
    "capacity",
    "host_capacity",
    "starts_on",
    "ends_on",
    "source_status",
  ],
  agreement: [
    ...hostFields,
    "reference_number",
    "effective_on",
    "expires_on",
    "notes",
    "source_status",
  ],
};

export function ExcelImportWorkspace({ module }: { module: ImportModule }) {
  const user = useSessionUser();
  const allowedFields = moduleFields[module];
  const editable = allowedFields.filter(
    (key) => !["opportunity_id", "source_opportunity", "host_code"].includes(key),
  );
  const [reference, setReference] = useState<Reference | null>(null);
  const [batch, setBatch] = useState<{ id: string; sheets: Sheet[] } | null>(null);
  const [sheet, setSheet] = useState(0);
  const [mapping, setMapping] = useState<Record<string, number | null>>({});
  const [program, setProgram] = useState("");
  const [term, setTerm] = useState("");
  const [rows, setRows] = useState<Row[]>([]);
  const [preview, setPreview] = useState<Preview | null>(null);
  const [result, setResult] = useState<Result | null>(null);
  const [credentialsDownloaded, setCredentialsDownloaded] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [file, setFile] = useState<File | null>(null);
  const [confirmed, setConfirmed] = useState(false);
  const [defaultDate, setDefaultDate] = useState("");
  const [defaultYear, setDefaultYear] = useState("");
  const [page, setPage] = useState(0);
  useEffect(() => {
    api<Reference>("/coordinator-imports/reference")
      .then((value) => {
        setReference(value);
        if (value.programs.length === 1) setProgram(String(value.programs[0]!.id));
      })
      .catch((e) => setError(e.message));
  }, []);
  const invalidate = () => {
    setPreview(null);
    setConfirmed(false);
  };
  const change = (index: number, key: string, value: Row[string]) => {
    setRows((old) => old.map((r, i) => (i === index ? { ...r, [key]: value } : r)));
    invalidate();
  };
  async function run(action: () => Promise<void>) {
    setBusy(true);
    setError("");
    try {
      await action();
    } catch (e) {
      setError(e instanceof Error ? e.message : "Import failed. Please retry.");
    } finally {
      setBusy(false);
    }
  }
  const failures = preview?.rows.filter((row) => row.errors.length > 0).length ?? 0;
  return (
    <>
      <h2 className="text-xl font-semibold">
        Import{" "}
        {module === "host"
          ? "HTEs"
          : module === "opportunity"
            ? "opportunities"
            : module === "agreement"
              ? "agreements"
              : "students"}{" "}
        from Excel
      </h2>
      <p className="mb-4 mt-1 text-sm text-muted-foreground">
        Upload, map columns, resolve conflicts, then confirm within this workflow.
      </p>
      {error && (
        <p role="alert" className="mb-4 rounded border border-red-300 bg-red-50 p-3">
          {error}
        </p>
      )}
      <fieldset disabled={busy} className="space-y-5">
        <Card>
          <h2 className="font-semibold">1. Upload workbook</h2>
          <p className="my-2 text-sm">
            Excel .xlsx or .xls, up to 10 MB. Up to 1,000 records per import. Files stay private to
            the uploading coordinator.
          </p>
          {module === "student" && (
            <p className="my-3 rounded border bg-muted/30 p-3 text-sm">
              Recommended columns: Student ID · First Name · Middle Name (optional) · Last Name ·
              Program · Academic Term · Year Level. Format Student ID as Text in Excel (for example,
              001234). Email is optional. Full-name columns from older files remain supported.{" "}
              <a
                href="/api/v1/coordinator-imports/student-template"
                className="font-semibold underline"
              >
                Download Excel template
              </a>
            </p>
          )}
          <label className="block text-sm">
            Excel file
            <input
              type="file"
              accept=".xlsx,.xls"
              className="my-3 block"
              onChange={(e) => setFile(e.target.files?.[0] ?? null)}
            />
          </label>
          <Button
            disabled={!file || busy}
            onClick={() =>
              run(async () => {
                const form = new FormData();
                form.append("file", file!);
                form.append("module", module);
                const response = await upload<{ id: string; sheets: Sheet[] }>(
                  "/coordinator-imports",
                  form,
                );
                const primaryFields =
                  module === "student"
                    ? [
                        "student_id",
                        "first_name",
                        "last_name",
                        "name",
                        "academic_term",
                        "year_level",
                        "program",
                      ]
                    : module === "host"
                      ? ["host_name", "host_address", "host_capacity"]
                      : module === "opportunity"
                        ? ["opportunity_title", "tasks", "capacity"]
                        : ["reference_number", "effective_on", "expires_on"];
                const selected = response.sheets.reduce(
                  (best, candidate, index, sheets) =>
                    primaryFields.filter((field) => candidate.mapping[field] !== undefined).length >
                    primaryFields.filter((field) => sheets[best]?.mapping[field] !== undefined)
                      .length
                      ? index
                      : best,
                  0,
                );
                setBatch(response);
                setSheet(selected);
                setMapping(response.sheets[selected]?.mapping ?? {});
                setRows([]);
                setResult(null);
                setCredentialsDownloaded(false);
                invalidate();
                setPage(0);
              })
            }
          >
            {busy ? "Working…" : "Upload Excel"}
          </Button>
        </Card>
        {batch && !result && reference && (
          <>
            <Card>
              <h2 className="font-semibold">2. Select sheet and map columns</h2>
              <p className="my-2 text-sm">
                Mappings are detected automatically. Check them before continuing. Unique Student ID
                and HTE ID reference sheets are joined automatically; ambiguous references require
                review.
              </p>
              <label>
                Worksheet
                <select
                  className="ml-3 rounded border p-2"
                  value={sheet}
                  onChange={(e) => {
                    const next = Number(e.target.value);
                    setSheet(next);
                    setMapping(batch.sheets[next]?.mapping ?? {});
                    setRows([]);
                    invalidate();
                  }}
                >
                  {batch.sheets.map((s, i) => (
                    <option key={i} value={i}>
                      {s.name} ({s.rows.length} rows)
                    </option>
                  ))}
                </select>
              </label>
              <div className="my-4 grid gap-3 md:grid-cols-3">
                {reference.fields
                  .filter((field) => allowedFields.includes(field))
                  .map((field) => (
                    <label key={field} className="text-sm">
                      {labels[field] ?? field}
                      <select
                        className="mt-1 block w-full rounded border p-2"
                        value={mapping[field] ?? ""}
                        onChange={(e) => {
                          setMapping((old) => ({
                            ...old,
                            [field]: e.target.value === "" ? null : Number(e.target.value),
                          }));
                          setRows([]);
                          invalidate();
                        }}
                      >
                        <option value="">Not in this sheet</option>
                        {batch.sheets[sheet]?.headers.map((header, index) => (
                          <option key={index} value={index}>
                            {index + 1}: {header || "Unnamed column"}
                          </option>
                        ))}
                      </select>
                    </label>
                  ))}
              </div>
              <Button
                onClick={() =>
                  run(async () => {
                    const response = await mutate<{ rows: Row[] }>(
                      `/coordinator-imports/${batch.id}/map`,
                      {
                        sheet,
                        mapping: Object.fromEntries(
                          Object.entries(mapping).filter(([field]) =>
                            allowedFields.includes(field),
                          ),
                        ),
                      },
                    );
                    setRows(
                      response.rows.map(
                        (row) =>
                          Object.fromEntries(
                            Object.entries(row).filter(
                              ([key]) =>
                                allowedFields.includes(key) ||
                                ["source", "kind", "reference_warning"].includes(key),
                            ),
                          ) as Row,
                      ),
                    );
                    setPage(0);
                    invalidate();
                  })
                }
              >
                Build editable preview
              </Button>
            </Card>
            {rows.length > 0 && (
              <Card>
                <h2 className="font-semibold">3. Resolve and validate records</h2>
                <div className="my-4 grid gap-3 md:grid-cols-2">
                  <label>
                    Assigned program
                    <select
                      className="mt-1 block w-full rounded border p-2"
                      value={program}
                      onChange={(e) => {
                        setProgram(e.target.value);
                        invalidate();
                      }}
                    >
                      <option value="">Select program</option>
                      {reference.programs.map((p) => (
                        <option key={p.id} value={p.id}>
                          {p.code} — {p.required_ojt_hours ?? "Unconfirmed"} hours
                        </option>
                      ))}
                    </select>
                  </label>
                  {module !== "agreement" && (
                    <label>
                      Academic term
                      <select
                        className="mt-1 block w-full rounded border p-2"
                        aria-label="Import academic term"
                        value={term}
                        onChange={(e) => {
                          setTerm(e.target.value);
                          invalidate();
                        }}
                      >
                        <option value="">Select term</option>
                        {reference.terms.map((t) => (
                          <option key={t.id} value={t.id}>
                            {t.code} — {t.name}
                          </option>
                        ))}
                      </select>
                    </label>
                  )}
                </div>
                <p className="text-sm">
                  {module === "student"
                    ? "Import one program and academic term at a time; values in the workbook must match your selections. New students receive active accounts and temporary passwords. Email is optional. Existing passwords and history are preserved. Hosts and placements are not imported. Enrollment date defaults to today if omitted."
                    : module === "agreement"
                      ? "Use the real agreement reference and dates. New agreements are drafts until the executed document is attached and verified."
                      : module === "opportunity"
                        ? "Select the actual term and confirmed host capacity. New opportunities are drafts for review before posting."
                        : "Addresses are retained as supplied. Coordinates are optional; hosts without coordinates appear in the map list without a pin."}
                </p>
                {module === "student" && (
                  <details className="my-4 rounded border p-3">
                    <summary>Apply confirmed enrollment details to blank cells</summary>
                    <div className="mt-3 flex flex-wrap items-end gap-3">
                      <label>
                        Enrollment date
                        <input
                          aria-label="Default enrollment date"
                          type="date"
                          value={defaultDate}
                          onChange={(e) => setDefaultDate(e.target.value)}
                          className="block rounded border p-2"
                        />
                      </label>
                      <label>
                        Year level
                        <input
                          aria-label="Default year level"
                          type="number"
                          min="1"
                          max="10"
                          value={defaultYear}
                          onChange={(e) => setDefaultYear(e.target.value)}
                          className="block rounded border p-2"
                        />
                      </label>
                      <Button
                        variant="outline"
                        onClick={() => {
                          setRows((old) =>
                            old.map((r) => ({
                              ...r,
                              enrolled_on: r.enrolled_on || defaultDate,
                              year_level: r.year_level || defaultYear,
                            })),
                          );
                          invalidate();
                        }}
                      >
                        Fill blank enrollment fields
                      </Button>
                    </div>
                  </details>
                )}
                <p className="my-3 text-sm" role="status">
                  {rows.length} records.{" "}
                  {preview
                    ? `${failures} rows need correction or skipping.`
                    : "Validate after editing to see errors and duplicate warnings."}
                </p>
                <div className="space-y-3">
                  {rows.slice(page * 20, page * 20 + 20).map((row, offset) => {
                    const index = page * 20 + offset;
                    const report = preview?.rows[index];
                    return (
                      <details
                        key={index}
                        className="rounded border p-3"
                        open={!!report?.errors.length}
                      >
                        <summary className="cursor-pointer font-medium">
                          {String(row.source)} —{" "}
                          {module === "student"
                            ? `${String(row.student_id || "No Student ID")} — `
                            : ""}
                          {String(
                            row.name ||
                              [row["first_name"], row["middle_name"], row["last_name"]]
                                .filter(Boolean)
                                .join(" ") ||
                              row.host_name ||
                              "Needs review",
                          )}{" "}
                          {row.skip
                            ? "(Skipped)"
                            : report
                              ? `(${report.errors.length ? "Needs review" : report.action.replaceAll("_", " ")})`
                              : ""}
                        </summary>
                        <div className="my-3 flex flex-wrap gap-4 text-sm">
                          <label>
                            <input
                              type="checkbox"
                              checked={row.skip === true}
                              onChange={(e) => change(index, "skip", e.target.checked)}
                            />{" "}
                            Skip this row
                          </label>
                          {module !== "student" && (
                            <>
                              <label>
                                <input
                                  type="checkbox"
                                  checked={row.keep_existing === true}
                                  onChange={(e) => change(index, "keep_existing", e.target.checked)}
                                />{" "}
                                Keep existing identity/details when different
                              </label>
                              <label>
                                <input
                                  type="checkbox"
                                  checked={row.fill_missing === true}
                                  onChange={(e) => change(index, "fill_missing", e.target.checked)}
                                />{" "}
                                Fill missing address/coordinates only
                              </label>
                            </>
                          )}
                        </div>
                        {report?.errors.map((message, i) => (
                          <p key={`e${i}`} className="text-sm text-red-700">
                            {message}
                          </p>
                        ))}
                        {report?.warnings.map((message, i) => (
                          <p key={`w${i}`} className="text-sm text-amber-800">
                            {message}
                          </p>
                        ))}
                        <div className="mt-3 grid gap-3 md:grid-cols-3">
                          {editable.map((field) => (
                            <label key={field} className="text-sm">
                              {labels[field]}
                              <input
                                className="mt-1 block w-full rounded border p-2"
                                aria-label={`${labels[field]} row ${index + 1}`}
                                type={field.endsWith("_on") ? "date" : "text"}
                                value={String(row[field] ?? "")}
                                onChange={(e) => change(index, field, e.target.value)}
                              />
                            </label>
                          ))}
                          {module !== "student" && (
                            <label className="text-sm">
                              Match existing HTE
                              <select
                                className="mt-1 block w-full rounded border p-2"
                                value={String(row.host_id ?? "")}
                                onChange={(e) => change(index, "host_id", e.target.value)}
                              >
                                <option value="">Auto-detect by name</option>
                                {reference.hosts.map((h) => (
                                  <option key={h.id} value={h.id}>
                                    {h.name} — {h.city}
                                  </option>
                                ))}
                              </select>
                            </label>
                          )}
                        </div>
                      </details>
                    );
                  })}
                </div>
                <div className="my-4 flex items-center gap-3">
                  <Button
                    variant="outline"
                    disabled={page === 0}
                    onClick={() => setPage((p) => p - 1)}
                  >
                    Previous rows
                  </Button>
                  <span>
                    Page {page + 1} of {Math.ceil(rows.length / 20)}
                  </span>
                  <Button
                    variant="outline"
                    disabled={(page + 1) * 20 >= rows.length}
                    onClick={() => setPage((p) => p + 1)}
                  >
                    Next rows
                  </Button>
                </div>
                <Button
                  disabled={!program || (!term && module !== "agreement") || rows.length > 1000}
                  onClick={() =>
                    run(async () => {
                      const response = await mutate<Preview>(
                        `/coordinator-imports/${batch.id}/preview`,
                        {
                          program_id: Number(program),
                          academic_term_id: term ? Number(term) : null,
                          rows,
                        },
                      );
                      setPreview(response);
                      setConfirmed(false);
                    })
                  }
                >
                  Validate and detect duplicates
                </Button>
                {preview && (
                  <div className="mt-5 border-t pt-4">
                    <h2 className="font-semibold">4. Confirm import</h2>
                    <p className="my-2 text-sm">
                      {rows.filter((r) => !r.skip).length} selected rows;{" "}
                      {rows.filter((r) => r.skip).length} skipped. Resolve every error or mark that
                      row skipped. Saving rechecks permissions and database conflicts.
                    </p>
                    <label className="my-3 block">
                      <input
                        type="checkbox"
                        checked={confirmed}
                        onChange={(e) => setConfirmed(e.target.checked)}
                      />{" "}
                      I reviewed the records, program, term, and warnings.
                    </label>
                    <Button
                      disabled={!confirmed || failures > 0 || rows.every((r) => r.skip)}
                      onClick={() =>
                        run(async () => {
                          const response = await mutate<Result>(
                            `/coordinator-imports/${batch.id}/confirm`,
                            { revision: preview.revision },
                          );
                          setResult(response);
                          window.dispatchEvent(new Event("internmatch:records-imported"));
                          localStorage.setItem("internmatch:records-imported", String(Date.now()));
                        })
                      }
                    >
                      Confirm import
                    </Button>
                  </div>
                )}
              </Card>
            )}
          </>
        )}
        {preview?.counts && (
          <Card>
            <h2 className="font-semibold">Preview summary</h2>
            <dl className="grid gap-3 sm:grid-cols-3">
              {Object.entries(preview.counts).map(([key, count]) => (
                <div key={key}>
                  <dt>{key.replaceAll("_", " ")}</dt>
                  <dd className="text-xl font-semibold">{count}</dd>
                </div>
              ))}
            </dl>
          </Card>
        )}
        {result && (
          <Card>
            <h2 className="font-semibold">Import results</h2>
            {!!result.new_accounts && !credentialsDownloaded && batch && (
              <div className="my-3 rounded border p-3">
                <p>
                  Download credentials once within 15 minutes. Keep this file private and give each
                  student only their own credentials. Closing this screen does not reset passwords;
                  available downloads also appear in Import History.
                </p>
                <Button
                  disabled={busy}
                  onClick={() =>
                    run(async () => {
                      await downloadImportCredentials(batch.id);
                      setCredentialsDownloaded(true);
                    })
                  }
                >
                  Download temporary credentials
                </Button>
              </div>
            )}
            <p role="status" className="my-3">
              Successful: {result.successful} · Updated: {result.updated} · Skipped:{" "}
              {result.skipped} · Failed: {result.failed}
            </p>
            {module === "student" && (
              <p className="mb-3 text-sm">
                New accounts: {result.new_accounts ?? 0} · Existing accounts recognized:{" "}
                {result.existing_accounts ?? 0} · Enrollments created:{" "}
                {result.enrollments_created ?? 0} · Enrollments updated:{" "}
                {result.enrollments_updated ?? 0}
              </p>
            )}
            <p className="text-sm">
              Counts refer to source rows. Records below have been saved to this module. Existing
              credentials are preserved. New student accounts require a password change at first
              login. Student imports create no placements. New opportunities and agreements from
              their separate imports remain drafts.
            </p>
            <div className="my-4 flex flex-wrap gap-4">
              {(
                [
                  ["students", "Student Profiles"],
                  ["hosts", "Host Establishments"],
                  ["progress", "Internship Progress"],
                  ["map", "Accessibility Map"],
                ] as const
              )
                .filter(
                  ([section]) => user.role !== "admin" || ["students", "hosts"].includes(section),
                )
                .map(([section, label]) => (
                  <a
                    key={section}
                    className="underline"
                    href={`/${user.role === "admin" ? "admin" : "coordinator"}/${section}`}
                  >
                    {label}
                  </a>
                ))}
            </div>
            <div className="overflow-auto">
              <table className="w-full text-left text-sm">
                <thead>
                  <tr>
                    <th>Source</th>
                    <th>Student ID</th>
                    <th>Result</th>
                    <th>Details</th>
                  </tr>
                </thead>
                <tbody>
                  {result.rows.map((r, i) => (
                    <tr key={i} className="border-t">
                      <td className="p-2">{r.source}</td>
                      <td>{r.student_id || "—"}</td>
                      <td>{r.status}</td>
                      <td>{r.message}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>
        )}
      </fieldset>
    </>
  );
}
