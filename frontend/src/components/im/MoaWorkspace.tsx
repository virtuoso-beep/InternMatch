import { useEffect, useState } from "react";
import { api, mutate, upload } from "@/lib/api";
import type { PlacementReference } from "@/lib/placement";
import { useSessionUser } from "./SessionGuard";
import { ExcelImportButton } from "./ExcelImportButton";
import { Plus, Upload } from "lucide-react";
import { ConfirmButton } from "./actions";
import { toast } from "sonner";
import {
  Button,
  Card,
  CardTitle,
  FormField,
  FormGrid,
  FormModal,
  FormSection,
  PageHeader,
  Pill,
  statusTone,
} from "./ui";
import { inputClass, toFormError, type FormError } from "./formUtils";

type Moa = {
  id: number;
  host_establishment_id: number;
  host_establishment: { name: string };
  reference_number: string;
  status: string;
  /** Spec §5 status computed on the server from the dates: pending, active, expiring, expired, terminated. */
  lifecycle_status?: string;
  effective_on: string | null;
  expires_on: string | null;
  is_institution_wide: boolean;
  programs: { id: number; code: string }[];
  notes: string | null;
  document: { original_name: string } | null;
  archived_at?: string | null;
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
  const [showArchived, setShowArchived] = useState(false);
  const today = new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Manila",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(new Date());
  const dateState = (item: Moa) => {
    if (item.lifecycle_status) return item.lifecycle_status;
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
      api<{ data: Moa[]; last_page: number }>(
        `/moas?page=${page}${showArchived ? "&include_archived=1" : ""}`,
      ),
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
  }, [page, revision, showArchived]);
  return (
    <>
      <PageHeader
        title="Memoranda of agreement"
        subtitle="Register executed agreements and their program coverage. Recording an agreement here does not sign it."
      />
      <ExcelImportButton module="agreement" onImported={() => setRevision((v) => v + 1)} />
      {message && <p role="alert">{message}</p>}
      <Button disabled={!reference} onClick={() => setEditing(null)}>
        <Plus size={16} aria-hidden="true" />
        Add MOA
      </Button>
      {user.role !== "supervisor" && (
        <label className="my-4 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={showArchived}
            onChange={(event) => {
              setSelected(null);
              setPage(1);
              setShowArchived(event.target.checked);
            }}
          />
          Show archived agreements
        </label>
      )}
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
                  className="mt-1 block w-64 max-w-full rounded border p-2"
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
                  <option value="pending">Pending</option>
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
                <thead className="border-b text-xs text-muted-foreground">
                  <tr>
                    {[
                      "Reference",
                      "Establishment",
                      "Effective / expiry",
                      "Status",
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
                          <p className="text-brand">Past expiry date</p>
                        )}
                        {dateState(item) === "expiring" && (
                          <p className="text-warn">Expiring within 90 days</p>
                        )}
                      </td>
                      <td className="p-3">
                        <Pill tone={statusTone(dateState(item))}>{dateState(item)}</Pill>
                        {item.status !== "active" && item.status !== dateState(item) && (
                          <p className="mt-1 text-xs text-muted-foreground">
                            Recorded: {item.status.replaceAll("_", " ")}
                          </p>
                        )}
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
        <MoaEditor
          key={editing?.id ?? "new"}
          record={editing}
          reference={reference}
          onClose={() => setEditing(undefined)}
          done={() => {
            setEditing(undefined);
            setRevision((value) => value + 1);
          }}
        />
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
                <div className="flex gap-2">
                  {item.archived_at && <Pill tone="muted">Archived</Pill>}
                  <Pill tone={statusTone(dateState(item))}>{dateState(item)}</Pill>
                </div>
              </div>
              <div className="my-5 grid gap-4 sm:grid-cols-2">
                <div>
                  <p className="text-xs text-muted-foreground">Effective date</p>
                  <p>{item.effective_on?.slice(0, 10) ?? "Not recorded"}</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Expiry date</p>
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
                <div className="mt-3 flex flex-wrap gap-2">
                  {!item.archived_at && (
                    <Button variant="outline" onClick={() => setEditing(item)}>
                      Edit agreement record
                    </Button>
                  )}
                  {!item.archived_at && item.status !== "active" && (
                    <AgreementUpload record={item} done={() => setRevision((value) => value + 1)} />
                  )}
                  {user.role !== "supervisor" && !item.archived_at && (
                    <ConfirmButton
                      tone="danger"
                      title={`Archive MOA ${item.reference_number}?`}
                      description={`The agreement with ${item.host_establishment.name} is hidden from lists and no longer covers new placements. Its history is kept, and you can restore it later. Agreements still used by pending, approved or active placements cannot be archived.`}
                      confirmLabel="Archive"
                      success="Agreement archived"
                      onConfirm={() => mutate(`/moas/${item.id}/archive`, {})}
                      onDone={() => setRevision((value) => value + 1)}
                    >
                      Archive
                    </ConfirmButton>
                  )}
                  {user.role !== "supervisor" && item.archived_at && (
                    <ConfirmButton
                      title={`Restore MOA ${item.reference_number}?`}
                      description={`The agreement with ${item.host_establishment.name} returns to the lists and can cover new placements while its dates are valid.`}
                      confirmLabel="Restore"
                      success="Agreement restored"
                      onConfirm={() => mutate(`/moas/${item.id}/restore`, {})}
                      onDone={() => setRevision((value) => value + 1)}
                    >
                      Restore
                    </ConfirmButton>
                  )}
                  {user.role !== "supervisor" && (
                    <ConfirmButton
                      tone="danger"
                      variant="ghost"
                      title={`Delete MOA ${item.reference_number}?`}
                      description="Only an agreement with no uploaded document and no placements can be deleted. This cannot be undone. Anything with history must be archived instead."
                      confirmLabel="Delete"
                      success="Agreement deleted"
                      onConfirm={() => mutate(`/moas/${item.id}`, undefined, "DELETE")}
                      onDone={() => setRevision((value) => value + 1)}
                    >
                      Delete
                    </ConfirmButton>
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

function AgreementUpload({ record, done }: { record: Moa; done: () => void }) {
  const [open, setOpen] = useState(false);
  const [file, setFile] = useState<File | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  return (
    <>
      <Button
        variant="outline"
        onClick={() => {
          setFile(null);
          setError(null);
          setOpen(true);
        }}
      >
        <Upload size={16} aria-hidden="true" />
        Upload executed agreement
      </Button>
      <FormModal
        open={open}
        onClose={() => setOpen(false)}
        title={`Upload executed agreement: ${record.reference_number}`}
        description={`Signed agreement with ${record.host_establishment.name}. PDF, JPG, PNG or DOCX, up to 10 MB.`}
        submitLabel="Upload document"
        busyLabel="Uploading…"
        busy={busy}
        error={error}
        onSubmit={async () => {
          if (!file || file.size === 0) {
            setError({
              message: "Choose a non-empty file.",
              fields: { file: ["Choose a non-empty file."] },
            });
            return;
          }
          if (file.size > 10 * 1024 * 1024) {
            setError({
              message: "Choose a file no larger than 10 MB.",
              fields: { file: ["Choose a file no larger than 10 MB."] },
            });
            return;
          }
          const payload = new FormData();
          payload.set("file", file);
          setBusy(true);
          setError(null);
          try {
            await upload(`/moas/${record.id}/document`, payload);
            setOpen(false);
            toast.success("Agreement document uploaded");
            done();
          } catch (cause) {
            setError(toFormError(cause, "Upload failed."));
          } finally {
            setBusy(false);
          }
        }}
      >
        <FormField
          label="Executed agreement document"
          required
          helper="PDF, JPG, PNG or DOCX. Maximum 10 MB."
        >
          <input
            type="file"
            name="file"
            required
            accept=".pdf,.jpg,.jpeg,.png,.docx"
            className={inputClass}
            onChange={(event) => setFile(event.target.files?.[0] ?? null)}
          />
        </FormField>
      </FormModal>
    </>
  );
}

function MoaEditor({
  record,
  reference,
  done,
  onClose,
}: {
  record: Moa | null;
  reference: PlacementReference;
  done: () => void;
  onClose: () => void;
}) {
  const user = useSessionUser();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
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
    <FormModal
      open
      onClose={onClose}
      title={record ? `Edit MOA for ${record.host_establishment.name}` : "Add MOA"}
      description="Save the draft, upload the executed agreement, then record active status with valid dates."
      submitLabel={record ? "Save changes" : "Save MOA"}
      busy={busy}
      error={error}
      onSubmit={async () => {
        setBusy(true);
        setError(null);
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
          toast.success(record ? "MOA updated" : "MOA added");
        } catch (cause) {
          setError(toFormError(cause, "Unable to save the agreement."));
        } finally {
          setBusy(false);
        }
      }}
    >
      <FormSection title="Agreement">
        <FormField label="Host establishment" required>
          <select
            required
            name="host_establishment_id"
            disabled={!!record}
            value={form.host_establishment_id}
            onChange={(event) =>
              setForm((current) => ({ ...current, host_establishment_id: event.target.value }))
            }
            className={inputClass}
          >
            <option value="">Select host</option>
            {reference.hosts.map((item) => (
              <option key={item.id} value={item.id}>
                {item.name}
              </option>
            ))}
          </select>
        </FormField>
        <FormGrid>
          <FormField label="Agreement reference" required>
            <input
              required
              name="reference_number"
              type="text"
              value={form.reference_number}
              onChange={(event) =>
                setForm((current) => ({ ...current, reference_number: event.target.value }))
              }
              className={inputClass}
            />
          </FormField>
          <FormField label="Agreement status">
            <select
              name="status"
              value={form.status}
              onChange={(event) =>
                setForm((current) => ({ ...current, status: event.target.value }))
              }
              className={inputClass}
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
          </FormField>
          <FormField label="Effective date">
            <input
              name="effective_on"
              type="date"
              value={form.effective_on}
              onChange={(event) =>
                setForm((current) => ({ ...current, effective_on: event.target.value }))
              }
              className={inputClass}
            />
          </FormField>
          <FormField label="Expiry date">
            <input
              name="expires_on"
              type="date"
              value={form.expires_on}
              onChange={(event) =>
                setForm((current) => ({ ...current, expires_on: event.target.value }))
              }
              className={inputClass}
            />
          </FormField>
        </FormGrid>
        <FormField label="Notes">
          <input
            name="notes"
            type="text"
            value={form.notes}
            onChange={(event) => setForm((current) => ({ ...current, notes: event.target.value }))}
            className={inputClass}
          />
        </FormField>
      </FormSection>
      <FormSection title="Coverage">
        {user.role === "admin" && (
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              name="is_institution_wide"
              checked={form.is_institution_wide}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  is_institution_wide: event.target.checked,
                  program_ids: [],
                }))
              }
            />
            Explicit institution-wide coverage
          </label>
        )}
        {!form.is_institution_wide && (
          <fieldset data-field="program_ids" className="grid gap-2 border-0 p-0 sm:grid-cols-3">
            <legend className="mb-2 text-sm font-medium">Covered programs</legend>
            {reference.programs.map((program) => (
              <label key={program.id} className="flex items-center gap-2 text-sm">
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
                />
                {program.code}
              </label>
            ))}
          </fieldset>
        )}
      </FormSection>
    </FormModal>
  );
}
