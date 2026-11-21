import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Plus } from "lucide-react";
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
} from "./ui";
import { ConfirmButton } from "./actions";
import { inputClass, toFormError, type FormError } from "./formUtils";
import type { PlacementReference } from "@/lib/placement";
import { useSessionUser } from "./SessionGuard";
import { ExcelImportButton } from "./ExcelImportButton";

type Host = {
  id: number;
  code: string;
  name: string;
  address: string;
  city: string;
  description: string | null;
  contact_name: string | null;
  contact_email: string | null;
  contact_number: string | null;
  latitude: string | null;
  longitude: string | null;
  archived_at?: string | null;
};
export function HostWorkspace() {
  const user = useSessionUser();
  const [hosts, setHosts] = useState<Host[] | null>(null);
  const [reference, setReference] = useState<PlacementReference | null>(null);
  const [revision, setRevision] = useState(0);
  const [page, setPage] = useState(1);
  const [last, setLast] = useState(1);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [coordinates, setCoordinates] = useState("");
  const [selected, setSelected] = useState<number | null>(null);
  const [showArchived, setShowArchived] = useState(false);
  const isAdmin = user.role === "admin";
  const visible = hosts?.filter(
    (host) =>
      `${host.name} ${host.code} ${host.city}`
        .toLowerCase()
        .includes(search.trim().toLowerCase()) &&
      (!coordinates ||
        (coordinates === "missing"
          ? host.latitude == null || host.longitude == null
          : host.latitude != null && host.longitude != null)),
  );
  useEffect(() => {
    let active = true;
    setHosts(null);
    setError("");
    Promise.all([
      api<{ data: Host[]; last_page: number }>(
        `/hosts?page=${page}${showArchived ? "&include_archived=1" : ""}`,
      ),
      api<PlacementReference>("/placement-reference"),
    ])
      .then(([result, refs]) => {
        if (active) {
          setHosts(result.data);
          setLast(result.last_page);
          setReference(refs);
        }
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [page, revision, showArchived]);
  return (
    <>
      <PageHeader
        title={user.role === "supervisor" ? "Company profile" : "Host establishments"}
        subtitle="Contact details and coordinates for establishments in your access scope."
      />
      <ExcelImportButton module="host" onImported={() => setRevision((v) => v + 1)} />
      {reference && (user.role === "admin" || user.role === "coordinator") && (
        <CreateHost reference={reference} onCreated={() => setRevision((value) => value + 1)} />
      )}
      {error && <p role="alert">{error}</p>}
      {(user.role === "admin" || user.role === "coordinator") && (
        <label className="mb-4 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={showArchived}
            onChange={(event) => {
              setSelected(null);
              setPage(1);
              setShowArchived(event.target.checked);
            }}
          />
          Show archived establishments
        </label>
      )}
      {isAdmin && (
        <Card>
          <div className="mb-4 flex flex-wrap items-end gap-3">
            <label className="text-sm">
              Find establishment
              <input
                className="mt-1 block rounded border p-2"
                placeholder="Name, code or city"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
              />
            </label>
            <label className="text-sm">
              Coordinates
              <select
                aria-label="Filter coordinates"
                className="mt-1 block rounded border p-2"
                value={coordinates}
                onChange={(event) => setCoordinates(event.target.value)}
              >
                <option value="">All locations</option>
                <option value="recorded">Coordinates recorded</option>
                <option value="missing">Coordinates missing</option>
              </select>
            </label>
            <Button
              onClick={() => {
                setSearch("");
                setCoordinates("");
              }}
            >
              Clear filters
            </Button>
            <Button
              disabled={!hosts && !error}
              onClick={() => {
                setSelected(null);
                setRevision((value) => value + 1);
              }}
            >
              Refresh establishments
            </Button>
          </div>
          <p className="mb-3 text-sm text-muted-foreground">
            Search and filters apply to this page. Open an establishment to view its program and
            term capacities.
          </p>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="border-b text-xs text-muted-foreground">
                <tr>
                  {["Establishment", "City", "Contact", "Coordinates", "Action"].map((label) => (
                    <th key={label} className="p-3">
                      {label}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {visible?.map((host) => (
                  <tr className="border-b" key={host.id}>
                    <td className="p-3">
                      <strong>{host.name}</strong>{" "}
                      {host.archived_at && <Pill tone="muted">Archived</Pill>}
                      <p>{host.code}</p>
                    </td>
                    <td className="p-3">{host.city || "Not recorded"}</td>
                    <td className="p-3">
                      {host.contact_name || "Not recorded"}
                      <p>{host.contact_email}</p>
                    </td>
                    <td className="p-3">
                      {host.latitude != null && host.longitude != null ? "Recorded" : "Missing"}
                    </td>
                    <td className="p-3">
                      <Button variant="outline" onClick={() => setSelected(host.id)}>
                        View details <span className="sr-only">{host.name}</span>
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {hosts && (
            <p role="status" className="mt-3 text-sm">
              {visible?.length
                ? `Showing ${visible.length} of ${hosts.length} establishments on page ${page}.`
                : "No establishments match these filters on this page."}
            </p>
          )}
        </Card>
      )}
      {!hosts && !error && <p role="status">Loading establishments…</p>}
      {hosts?.length === 0 && <Card>No host establishments are assigned to your account.</Card>}
      <div className="space-y-4">
        {hosts
          ?.filter((host) => !isAdmin || host.id === selected)
          .map((host) => (
            <div key={host.id}>
              {isAdmin && (
                <div className="my-3">
                  <Button
                    variant="outline"
                    onClick={() => {
                      setSelected(null);
                      setRevision((value) => value + 1);
                    }}
                  >
                    Close establishment details
                  </Button>
                </div>
              )}
              <div className="grid items-start gap-5 xl:grid-cols-[2fr_1fr]">
                <HostForm
                  key={`${host.id}:${host.archived_at ?? ""}`}
                  host={host}
                  onChanged={() => {
                    setSelected(null);
                    setRevision((value) => value + 1);
                  }}
                />
                {reference && (
                  <Card>
                    <CardTitle>Program capacity</CardTitle>
                    <HostCapacity
                      host={host}
                      reference={reference}
                      readOnly={Boolean(host.archived_at)}
                    />
                  </Card>
                )}
              </div>
            </div>
          ))}
      </div>
      {hosts && last > 1 && (
        <div className="mt-4 flex gap-3">
          <Button
            disabled={page === 1}
            onClick={() => {
              setSelected(null);
              setPage(page - 1);
            }}
          >
            Previous
          </Button>
          <span>
            {page} / {last}
          </span>
          <Button
            disabled={page === last}
            onClick={() => {
              setSelected(null);
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

function CreateHost({
  reference,
  onCreated,
}: {
  reference: PlacementReference;
  onCreated: () => void;
}) {
  const [creating, setCreating] = useState(false);
  const empty = {
    code: "",
    name: "",
    address: "",
    city: "",
    program_id: "",
    academic_term_id: "",
    capacity: "",
  };
  const [value, setValue] = useState(empty);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  const close = () => {
    setCreating(false);
    setValue(empty);
    setError(null);
  };
  return (
    <>
      <div className="mb-5">
        <Button
          onClick={() => {
            setError(null);
            setCreating(true);
          }}
        >
          <Plus size={16} aria-hidden="true" />
          Add host
        </Button>
      </div>
      <FormModal
        open={creating}
        onClose={close}
        title="Add host establishment"
        description="Record the establishment and its first program capacity."
        submitLabel="Save establishment"
        busy={busy}
        error={error}
        onSubmit={async () => {
          setBusy(true);
          setError(null);
          try {
            await mutate("/hosts", {
              code: value.code,
              name: value.name,
              address: value.address,
              city: value.city,
              capacities: [
                {
                  program_id: Number(value.program_id),
                  academic_term_id: Number(value.academic_term_id),
                  capacity: Number(value.capacity),
                },
              ],
            });
            onCreated();
            close();
            toast.success("Host establishment added");
          } catch (cause) {
            setError(toFormError(cause, "Unable to create the host."));
          } finally {
            setBusy(false);
          }
        }}
      >
        <FormSection title="Establishment">
          <FormGrid>
            {(
              [
                ["code", "Host reference code"],
                ["name", "Host name"],
                ["address", "Host address"],
                ["city", "Host city"],
              ] as const
            ).map(([key, label]) => (
              <FormField key={key} label={label} required>
                <input
                  required
                  name={key}
                  className={inputClass}
                  value={value[key]}
                  onChange={(event) =>
                    setValue((current) => ({ ...current, [key]: event.target.value }))
                  }
                />
              </FormField>
            ))}
          </FormGrid>
        </FormSection>
        <FormSection
          title="Program capacity"
          description="Slots this establishment offers to one program in one term."
        >
          <FormGrid>
            <ProgramTermFields
              reference={reference}
              program={value.program_id}
              term={value.academic_term_id}
              programName="capacities.0.program_id"
              termName="capacities.0.academic_term_id"
              onProgram={(program_id) => setValue((current) => ({ ...current, program_id }))}
              onTerm={(academic_term_id) =>
                setValue((current) => ({ ...current, academic_term_id }))
              }
            />
            <FormField label="Program capacity" required>
              <input
                required
                name="capacities.0.capacity"
                className={inputClass}
                type="number"
                min="0"
                max="100000"
                value={value.capacity}
                onChange={(event) =>
                  setValue((current) => ({ ...current, capacity: event.target.value }))
                }
              />
            </FormField>
          </FormGrid>
        </FormSection>
      </FormModal>
    </>
  );
}

function ProgramTermFields({
  reference,
  program,
  term,
  onProgram,
  onTerm,
  programName = "program_id",
  termName = "academic_term_id",
}: {
  reference: PlacementReference;
  program: string;
  term: string;
  onProgram: (value: string) => void;
  onTerm: (value: string) => void;
  programName?: string;
  termName?: string;
}) {
  return (
    <>
      <FormField label="Program" required>
        <select
          required
          name={programName}
          className={inputClass}
          value={program}
          onChange={(event) => onProgram(event.target.value)}
        >
          <option value="">Select program</option>
          {reference.programs.map((item) => (
            <option key={item.id} value={item.id}>
              {item.code}
            </option>
          ))}
        </select>
      </FormField>
      <FormField label="Academic term" required>
        <select
          required
          name={termName}
          className={inputClass}
          value={term}
          onChange={(event) => onTerm(event.target.value)}
        >
          <option value="">Select term</option>
          {reference.terms.map((item) => (
            <option key={item.id} value={item.id}>
              {item.code}
            </option>
          ))}
        </select>
      </FormField>
    </>
  );
}

function HostCapacity({
  host,
  reference,
  readOnly,
}: {
  host: Host;
  reference: PlacementReference;
  readOnly: boolean;
}) {
  const [rows, setRows] = useState<
    { program_id: number; academic_term_id: number; capacity: number }[]
  >([]);
  const [message, setMessage] = useState("");
  const [open, setOpen] = useState(false);
  const [value, setValue] = useState({ program: "", term: "", capacity: "" });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  const load = () =>
    api<{ data: typeof rows }>(`/hosts/${host.id}/capacities`)
      .then((result) => setRows(result.data))
      .catch((cause: Error) => setMessage(cause.message));
  return (
    <details
      className="rounded border p-4"
      onToggle={(event) => {
        if (event.currentTarget.open) void load();
      }}
    >
      <summary className="cursor-pointer font-semibold">{host.code} program capacities</summary>
      <ul className="my-3 text-sm">
        {rows.map((row) => (
          <li key={`${row.program_id}:${row.academic_term_id}`}>
            {reference.programs.find((item) => item.id === row.program_id)?.code ?? row.program_id}{" "}
            ·{" "}
            {reference.terms.find((item) => item.id === row.academic_term_id)?.code ??
              row.academic_term_id}
            : {row.capacity} slots
          </li>
        ))}
      </ul>
      {message && <p role="status">{message}</p>}
      {!readOnly && (
        <Button
          variant="outline"
          onClick={() => {
            setValue({ program: "", term: "", capacity: "" });
            setError(null);
            setOpen(true);
          }}
        >
          Set program capacity
        </Button>
      )}
      <FormModal
        open={open}
        onClose={() => setOpen(false)}
        title={`Set program capacity: ${host.name}`}
        description="Total slots this establishment offers one program in one term. Saving replaces the current value."
        submitLabel="Save capacity"
        busy={busy}
        error={error}
        onSubmit={async () => {
          setBusy(true);
          setError(null);
          try {
            await mutate(
              `/hosts/${host.id}/capacities`,
              {
                capacities: [
                  {
                    program_id: Number(value.program),
                    academic_term_id: Number(value.term),
                    capacity: Number(value.capacity),
                  },
                ],
              },
              "PUT",
            );
            await load();
            setOpen(false);
            toast.success("Program capacity saved");
          } catch (cause) {
            setError(toFormError(cause, "Unable to save capacity."));
          } finally {
            setBusy(false);
          }
        }}
      >
        <FormGrid>
          <ProgramTermFields
            reference={reference}
            program={value.program}
            term={value.term}
            programName="capacities.0.program_id"
            termName="capacities.0.academic_term_id"
            onProgram={(program) => setValue((v) => ({ ...v, program }))}
            onTerm={(term) => setValue((v) => ({ ...v, term }))}
          />
          <FormField label="Total slots for this program" required>
            <input
              required
              name="capacities.0.capacity"
              className={inputClass}
              type="number"
              min="0"
              max="100000"
              value={value.capacity}
              onChange={(event) => setValue((v) => ({ ...v, capacity: event.target.value }))}
            />
          </FormField>
        </FormGrid>
      </FormModal>
    </details>
  );
}

function HostForm({ host, onChanged }: { host: Host; onChanged: () => void }) {
  const user = useSessionUser();
  const [saved, setSaved] = useState(host);
  const [value, setValue] = useState(host);
  const [editing, setEditing] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  const fields = [
    ["name", "Establishment name"],
    ["address", "Address"],
    ["city", "City"],
    ["contact_name", "Contact person"],
    ["contact_email", "Contact email"],
    ["contact_number", "Contact number"],
    ["latitude", "Latitude"],
    ["longitude", "Longitude"],
  ] as const;
  const archived = Boolean(saved.archived_at);
  const manages = user.role === "admin" || user.role === "coordinator";
  return (
    <Card>
      <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
        <CardTitle>
          Establishment details · {saved.code} {archived && <Pill tone="muted">Archived</Pill>}
        </CardTitle>
        <div className="flex flex-wrap gap-2">
          {!archived && (
            <Button
              onClick={() => {
                setValue(saved);
                setError(null);
                setEditing(true);
              }}
            >
              Edit profile
            </Button>
          )}
          {manages && !archived && (
            <ConfirmButton
              tone="danger"
              title={`Archive ${saved.name}?`}
              description="The establishment is hidden from lists and is no longer offered for new recommendations or placements. Its history is kept, and you can restore it later. Establishments with pending, approved or active placements cannot be archived."
              confirmLabel="Archive"
              success="Establishment archived"
              onConfirm={() => mutate(`/hosts/${saved.id}/archive`, {})}
              onDone={onChanged}
            >
              Archive
            </ConfirmButton>
          )}
          {manages && archived && (
            <ConfirmButton
              title={`Restore ${saved.name}?`}
              description="The establishment returns to the lists and can be used for new opportunities and placements."
              confirmLabel="Restore"
              success="Establishment restored"
              onConfirm={() => mutate(`/hosts/${saved.id}/restore`, {})}
              onDone={onChanged}
            >
              Restore
            </ConfirmButton>
          )}
          {manages && (
            <ConfirmButton
              tone="danger"
              variant="ghost"
              title={`Delete ${saved.name}?`}
              description="Only an establishment that has never had opportunities, agreements or placements can be deleted. This cannot be undone. Anything with history must be archived instead."
              confirmLabel="Delete"
              success="Establishment deleted"
              onConfirm={() => mutate(`/hosts/${saved.id}`, undefined, "DELETE")}
              onDone={onChanged}
            >
              Delete
            </ConfirmButton>
          )}
        </div>
      </div>
      <dl className="grid gap-6 sm:grid-cols-2">
        {fields.map(([key, label]) => (
          <div key={key}>
            <dt className="mb-1 text-xs text-muted-foreground">{label}</dt>
            <dd className="break-words">{saved[key] ?? "Not provided"}</dd>
          </div>
        ))}
        <div className="sm:col-span-2">
          <dt className="text-xs text-muted-foreground">About the establishment</dt>
          <dd className="mt-1 whitespace-pre-wrap">
            {saved.description || "No description provided."}
          </dd>
        </div>
      </dl>
      <FormModal
        open={editing}
        size="lg"
        onClose={() => setEditing(false)}
        title={`Edit profile: ${saved.name}`}
        description="Contact details and coordinates for this establishment."
        submitLabel="Save profile"
        busy={busy}
        error={error}
        onSubmit={async () => {
          if (Boolean(value.latitude) !== Boolean(value.longitude)) {
            setError({
              message: "Provide both coordinates, or clear both.",
              fields: { latitude: ["Provide both coordinates, or clear both."] },
            });
            return;
          }
          setBusy(true);
          setError(null);
          const payload = {
            ...Object.fromEntries(
              fields.map(([key]) => [key, value[key] === "" ? null : value[key]]),
            ),
            description: value.description || null,
          };
          try {
            const result = await mutate<{ data: Host }>(`/hosts/${host.id}`, payload, "PATCH");
            setSaved(result.data);
            setValue(result.data);
            setEditing(false);
            toast.success("Company profile saved");
          } catch (cause) {
            setError(toFormError(cause, "Unable to save profile."));
          } finally {
            setBusy(false);
          }
        }}
      >
        <FormGrid>
          {fields.map(([key, label]) => (
            <FormField key={key} label={label} required={["name", "address", "city"].includes(key)}>
              <input
                name={key}
                className={inputClass}
                required={["name", "address", "city"].includes(key)}
                type={
                  key === "contact_email"
                    ? "email"
                    : ["latitude", "longitude"].includes(key)
                      ? "number"
                      : "text"
                }
                step="any"
                {...(key === "latitude" ? { min: -90, max: 90 } : {})}
                {...(key === "longitude" ? { min: -180, max: 180 } : {})}
                value={value[key] ?? ""}
                onChange={(event) =>
                  setValue((current) => ({ ...current, [key]: event.target.value }))
                }
              />
            </FormField>
          ))}
        </FormGrid>
        <FormField label="About the establishment">
          <textarea
            name="description"
            className={inputClass}
            rows={5}
            maxLength={10000}
            value={value.description ?? ""}
            onChange={(event) =>
              setValue((current) => ({ ...current, description: event.target.value }))
            }
          />
        </FormField>
      </FormModal>
    </Card>
  );
}
