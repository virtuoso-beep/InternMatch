import { AdminFormDialog } from "./AdminFormDialog";
import { useEffect, useState } from "react";
import { api, mutate, upload } from "@/lib/api";
import type { PlacementReference } from "@/lib/placement";
import { useSessionUser } from "./SessionGuard";
import { ExcelImportButton } from "./ExcelImportButton";
import { Button, Card, CardTitle, PageHeader, Pill, statusTone } from "./ui";

type Moa = {
  id: number;
  host_establishment_id: number;
  host_establishment: { name: string };
  reference_number: string;
  status: string;
  effective_on: string | null;
  expires_on: string | null;
  is_institution_wide: boolean;
  programs: { id: number; code: string }[];
  notes: string | null;
  document: { original_name: string } | null;
};
export function MoaWorkspace() {
  const [data, setData] = useState<{ data: Moa[]; last_page: number } | null>(null);
  const [reference, setReference] = useState<PlacementReference | null>(null);
  const [page, setPage] = useState(1);
  const [revision, setRevision] = useState(0);
  const [message, setMessage] = useState("");
  const [editing, setEditing] = useState<Moa | null | undefined>(undefined);
  const user = useSessionUser();
  const isAdmin = user.role === "admin";
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState("");
  const [selected, setSelected] = useState<number | null>(null);
  const today = new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Manila",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(new Date());
  const dateState = (item: Moa) => {
    const expiry = item.expires_on?.slice(0, 10);
    if (expiry && expiry < today) return "expired";
    if (item.status !== "active") return item.status;
    if (!item.effective_on || item.effective_on.slice(0, 10) > today || !expiry) return "other";
    return (Date.parse(`${expiry}T00:00:00Z`) - Date.parse(`${today}T00:00:00Z`)) / 86400000 <= 90
      ? "expiring"
      : "active";
  };
  const visible = data?.data.filter(
    (item) =>
      `${item.reference_number} ${item.host_establishment.name}`
        .toLowerCase()
        .includes(search.trim().toLowerCase()) &&
      (!filter || (filter === "missing-document" ? !item.document : dateState(item) === filter)),
  );
  useEffect(() => {
    let active = true;
    setData(null);
    setMessage("");
    Promise.all([
      api<{ data: Moa[]; last_page: number }>(`/moas?page=${page}`),
      api<PlacementReference>("/placement-reference"),
    ])
      .then(([records, refs]) => {
        if (active) {
          setData(records);
          setReference(refs);
        }
      })
      .catch((cause: Error) => {
        if (active) setMessage(cause.message);
      });
    return () => {
      active = false;
    };
  }, [page, revision]);
  return (
    <>
      <PageHeader
        title="Memoranda of agreement"
        subtitle="Register executed agreements and their program coverage. Recording an agreement here does not sign it."
      />
      <ExcelImportButton module="agreement" onImported={() => setRevision((v) => v + 1)} />
      {message && <p role="alert">{message}</p>}
      <Button disabled={!reference} onClick={() => setEditing(null)}>
        + Add MOA
      </Button>
      {isAdmin && (
        <>
          <div className="my-5 grid gap-4 sm:grid-cols-3">
            {[
              ["active", "Active beyond 90 days"],
              ["expiring", "Active, expiring within 90 days"],
              ["expired", "Past expiry date"],
            ].map(([value, label]) => (
              <Card key={value}>
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="mt-2 text-2xl font-semibold">
                  {data ? data.data.filter((item) => dateState(item) === value).length : "—"}
                </p>
                <p className="text-xs text-muted-foreground">On this page</p>
              </Card>
            ))}
          </div>
          <Card>
            <div className="mb-4 flex flex-wrap items-end gap-3">
              <label className="text-sm">
                Find agreement
                <input
                  className="mt-1 block rounded border p-2"
                  placeholder="Reference or establishment"
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                />
              </label>
              <label className="text-sm">
                Agreement filter
                <select
                  aria-label="Agreement filter"
                  className="mt-1 block rounded border p-2"
                  value={filter}
                  onChange={(event) => setFilter(event.target.value)}
                >
                  <option value="">All agreements</option>
                  <option value="active">Active beyond 90 days</option>
                  <option value="expiring">Expiring within 90 days</option>
                  <option value="expired">Past expiry date</option>
                  <option value="draft">Draft</option>
                  <option value="missing-document">Missing document</option>
                </select>
              </label>
              <Button
                onClick={() => {
                  setSearch("");
                  setFilter("");
                }}
              >
                Clear filters
              </Button>
              <Button
                disabled={!data && !message}
                onClick={() => {
                  setSelected(null);
                  setEditing(undefined);
                  setRevision((value) => value + 1);
                }}
              >
                Refresh agreements
              </Button>
            </div>
            <p className="mb-3 text-sm text-muted-foreground">
              Counts and filters apply to this page. Date summaries use Manila dates; document and
              program coverage checks still apply to placement eligibility.
            </p>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="border-b text-xs uppercase text-muted-foreground">
                  <tr>
                    {[
                      "Reference",
                      "Establishment",
                      "Effective / expiry",
                      "Recorded status",
                      "Document",
                      "Action",
                    ].map((label) => (
                      <th className="p-3" key={label}>
                        {label}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {visible?.map((item) => (
                    <tr className="border-b" key={item.id}>
                      <td className="p-3 font-semibold">{item.reference_number}</td>
                      <td className="p-3">{item.host_establishment.name}</td>
                      <td className="p-3">
                        {item.effective_on?.slice(0, 10) ?? "Not recorded"}
                        <p>Until {item.expires_on?.slice(0, 10) ?? "not recorded"}</p>
                        {dateState(item) === "expired" && (
                          <p className="text-red-700">Past expiry date</p>
                        )}
                        {dateState(item) === "expiring" && (
                          <p className="text-amber-700">Expiring within 90 days</p>
                        )}
                      </td>
                      <td className="p-3">
                        <Pill tone={statusTone(item.status)}>{item.status}</Pill>
                      </td>
                      <td className="p-3">{item.document ? "Uploaded" : "Missing"}</td>
                      <td className="p-3">
                        <Button variant="outline" onClick={() => setSelected(item.id)}>
                          View agreement <span className="sr-only">{item.reference_number}</span>
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {data && (
              <p role="status" className="mt-3 text-sm">
                {visible?.length
                  ? `Showing ${visible.length} of ${data.data.length} agreements on page ${page}.`
                  : "No agreements match these filters on this page."}
              </p>
            )}
          </Card>
          {selected !== null && (
            <div className="mt-3">
              <Button variant="outline" onClick={() => setSelected(null)}>
                Close agreement details
              </Button>
            </div>
          )}
        </>
      )}
      {!data && !message && (
        <p role="status" className="mt-4">
          Loading agreements…
        </p>
      )}
      {editing !== undefined && reference && (
        <AdminFormDialog
          title={editing ? "Edit MOA" : "Add MOA"}
          onClose={() => setEditing(undefined)}
        >
          <MoaEditor
            key={editing?.id ?? "new"}
            record={editing}
            reference={reference}
            done={() => {
              setEditing(undefined);
              setRevision((value) => value + 1);
            }}
          />
        </AdminFormDialog>
      )}
      {data?.data.length === 0 && (
        <p className="mt-4">No agreements are available in your scope.</p>
      )}
      <div className="mt-5 space-y-4">
        {data?.data
          .filter((item) => !isAdmin || item.id === selected)
          .map((item) => (
            <Card key={item.id}>
              <div className="flex flex-wrap items-start justify-between gap-3">
                <CardTitle>
                  {item.reference_number} · {item.host_establishment.name}
                </CardTitle>
                <Pill tone={statusTone(item.status)}>{item.status}</Pill>
              </div>
              <div className="my-5 grid gap-4 sm:grid-cols-2">
                <div>
                  <p className="text-xs uppercase text-muted-foreground">Effective date</p>
                  <p>{item.effective_on?.slice(0, 10) ?? "Not recorded"}</p>
                </div>
                <div>
                  <p className="text-xs uppercase text-muted-foreground">Expiry date</p>
                  <p>{item.expires_on?.slice(0, 10) ?? "Not recorded"}</p>
                </div>
              </div>
              {item.expires_on &&
                item.expires_on.slice(0, 10) <
                  new Intl.DateTimeFormat("en-CA", {
                    timeZone: "Asia/Manila",
                    year: "numeric",
                    month: "2-digit",
                    day: "2-digit",
                  }).format(new Date()) && (
                  <p className="my-3 rounded border border-warn p-3 text-sm">
                    This agreement is past its expiry date and is not eligible for new placements.
                  </p>
                )}
              {!item.document && (
                <p className="my-3 text-sm text-muted-foreground">
                  No executed document has been uploaded.
                </p>
              )}
              <p>
                Coverage:{" "}
                {item.is_institution_wide
                  ? "Institution-wide"
                  : item.programs.map((program) => program.code).join(", ") ||
                    "No programs recorded"}
              </p>
              <p>{item.notes}</p>
              {item.document && (
                <a
                  className="my-2 block text-sm underline"
                  href={`/api/v1/moas/${item.id}/document`}
                >
                  Download agreement · {item.document.original_name}
                </a>
              )}
              {(user.role === "admin" ||
                (user.role === "coordinator" &&
                  !item.is_institution_wide &&
                  item.programs.every((program) =>
                    reference?.programs.some((own) => own.id === program.id),
                  )) ||
                (user.role === "supervisor" && item.status === "draft")) && (
                <div className="mt-3">
                  <Button variant="outline" onClick={() => setEditing(item)}>
                    Edit agreement record
                  </Button>
                  {item.status !== "active" && (
                    <AgreementUpload id={item.id} done={() => setRevision((value) => value + 1)} />
                  )}
                </div>
              )}
            </Card>
          ))}
      </div>
      {data && data.last_page > 1 && (
        <div className="mt-4 flex gap-3">
          <Button
            disabled={page === 1}
            onClick={() => {
              setSelected(null);
              setEditing(undefined);
              setPage(page - 1);
            }}
          >
            Previous
          </Button>
          <span>
            {page} / {data.last_page}
          </span>
          <Button
            disabled={page === data.last_page}
            onClick={() => {
              setSelected(null);
              setEditing(undefined);
              setPage(page + 1);
            }}
          >
            Next
          </Button>
        </div>
      )}
    </>
  );
}

function AgreementUpload({ id, done }: { id: number; done: () => void }) {
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  return (
    <form
      className="mt-3 flex flex-wrap items-center gap-3"
      onSubmit={async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        setBusy(true);
        setMessage("");
        try {
          await upload(`/moas/${id}/document`, new FormData(form));
          form.reset();
          done();
        } catch (cause) {
          setMessage(cause instanceof Error ? cause.message : "Upload failed.");
        } finally {
          setBusy(false);
        }
      }}
    >
      <input
        type="file"
        name="file"
        aria-label="Executed agreement document"
        accept=".pdf,.jpg,.jpeg,.png,.docx"
        required
      />
      <Button type="submit" disabled={busy}>
        Upload document
      </Button>
      {message && <p role="alert">{message}</p>}
    </form>
  );
}

function MoaEditor({
  record,
  reference,
  done,
}: {
  record: Moa | null;
  reference: PlacementReference;
  done: () => void;
}) {
  const user = useSessionUser();
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  const [form, setForm] = useState({
    host_establishment_id: record ? String(record.host_establishment_id) : "",
    reference_number: record?.reference_number ?? "",
    status: record?.status ?? "draft",
    effective_on: record?.effective_on?.slice(0, 10) ?? "",
    expires_on: record?.expires_on?.slice(0, 10) ?? "",
    notes: record?.notes ?? "",
    is_institution_wide: record?.is_institution_wide ?? false,
    program_ids: record?.programs.map((item) => item.id) ?? ([] as number[]),
  });
  return (
    <Card className="my-5">
      <CardTitle>{record ? "Edit agreement" : "Register draft agreement"}</CardTitle>
      <form
        className="space-y-3"
        onSubmit={async (event) => {
          event.preventDefault();
          setBusy(true);
          setMessage("");
          try {
            await mutate(
              record ? `/moas/${record.id}` : "/moas",
              {
                ...form,
                host_establishment_id: Number(form.host_establishment_id),
                effective_on: form.effective_on || null,
                expires_on: form.expires_on || null,
              },
              record ? "PUT" : "POST",
            );
            done();
          } catch (cause) {
            setMessage(cause instanceof Error ? cause.message : "Unable to save agreement.");
          } finally {
            setBusy(false);
          }
        }}
      >
        <label className="block text-sm">
          Host establishment
          <select
            required
            disabled={!!record}
            value={form.host_establishment_id}
            onChange={(event) =>
              setForm((current) => ({ ...current, host_establishment_id: event.target.value }))
            }
            className="mt-1 block w-full rounded border p-2"
          >
            <option value="">Select host</option>
            {reference.hosts.map((item) => (
              <option key={item.id} value={item.id}>
                {item.name}
              </option>
            ))}
          </select>
        </label>
        {(
          [
            ["reference_number", "Agreement reference"],
            ["effective_on", "Effective date"],
            ["expires_on", "Expiry date"],
            ["notes", "Notes"],
          ] as const
        ).map(([key, label]) => (
          <label key={key} className="block text-sm">
            {label}
            <input
              required={key === "reference_number"}
              type={key.endsWith("_on") ? "date" : "text"}
              value={form[key]}
              onChange={(event) =>
                setForm((current) => ({ ...current, [key]: event.target.value }))
              }
              className="mt-1 block w-full rounded border p-2"
            />
          </label>
        ))}
        <label className="block text-sm">
          Agreement status
          <select
            value={form.status}
            onChange={(event) => setForm((current) => ({ ...current, status: event.target.value }))}
            className="ml-2 rounded border p-2"
          >
            {(user.role === "supervisor"
              ? ["draft"]
              : ["draft", "pending_signature", "active", "expired", "terminated"]
            ).map((status) => (
              <option key={status} value={status}>
                {status.replaceAll("_", " ")}
              </option>
            ))}
          </select>
        </label>
        {user.role === "admin" && (
          <label className="block text-sm">
            <input
              type="checkbox"
              checked={form.is_institution_wide}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  is_institution_wide: event.target.checked,
                  program_ids: [],
                }))
              }
            />{" "}
            Explicit institution-wide coverage
          </label>
        )}
        {!form.is_institution_wide && (
          <fieldset className="grid gap-2 sm:grid-cols-3">
            <legend className="mb-2 text-sm font-semibold">Covered programs</legend>
            {reference.programs.map((program) => (
              <label key={program.id} className="text-sm">
                <input
                  type="checkbox"
                  checked={form.program_ids.includes(program.id)}
                  onChange={(event) =>
                    setForm((current) => ({
                      ...current,
                      program_ids: event.target.checked
                        ? [...current.program_ids, program.id]
                        : current.program_ids.filter((id) => id !== program.id),
                    }))
                  }
                />{" "}
                {program.code}
              </label>
            ))}
          </fieldset>
        )}
        <p className="text-sm text-muted-foreground">
          Save the draft, upload the executed agreement, then record active status with valid dates.
        </p>
        {message && <p role="alert">{message}</p>}
        <div className="flex gap-2">
          <Button type="submit" disabled={busy}>
            Save agreement
          </Button>
          <Button variant="outline" disabled={busy} onClick={done}>
            Close
          </Button>
        </div>
      </form>
    </Card>
  );
}
