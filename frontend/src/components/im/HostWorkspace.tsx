import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, CardTitle, PageHeader } from "./ui";
import type { PlacementReference } from "@/lib/placement";
import { useSessionUser } from "./SessionGuard";

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
};
export function HostWorkspace() {
  const user = useSessionUser();
  const [hosts, setHosts] = useState<Host[] | null>(null);
  const [reference, setReference] = useState<PlacementReference | null>(null);
  const [revision, setRevision] = useState(0);
  const [page, setPage] = useState(1);
  const [last, setLast] = useState(1);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    setHosts(null);
    setError("");
    Promise.all([
      api<{ data: Host[]; last_page: number }>(`/hosts?page=${page}`),
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
  }, [page, revision]);
  return (
    <>
      <PageHeader
        title={user.role === "supervisor" ? "Company profile" : "Host establishments"}
        subtitle="Contact details and coordinates for establishments in your access scope."
      />
      {reference && (user.role === "admin" || user.role === "coordinator") && (
        <CreateHost reference={reference} onCreated={() => setRevision((value) => value + 1)} />
      )}
      {error && <p role="alert">{error}</p>}
      {!hosts && !error && <p role="status">Loading establishments…</p>}
      {hosts?.length === 0 && <Card>No host establishments are assigned to your account.</Card>}
      <div className="space-y-4">
        {hosts?.map((host) => (
          <div key={host.id}>
            <div className="grid items-start gap-5 xl:grid-cols-[2fr_1fr]">
              <HostForm host={host} />
              {reference && (
                <Card>
                  <CardTitle>Program capacity</CardTitle>
                  <HostCapacity host={host} reference={reference} />
                </Card>
              )}
            </div>
          </div>
        ))}
      </div>
      {hosts && last > 1 && (
        <div className="mt-4 flex gap-3">
          <Button disabled={page === 1} onClick={() => setPage(page - 1)}>
            Previous
          </Button>
          <span>
            {page} / {last}
          </span>
          <Button disabled={page === last} onClick={() => setPage(page + 1)}>
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
  const [value, setValue] = useState({
    code: "",
    name: "",
    address: "",
    city: "",
    program_id: "",
    academic_term_id: "",
    capacity: "",
  });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  return (
    <details className="mb-5 rounded border p-4">
      <summary className="cursor-pointer font-semibold">Add host establishment</summary>
      <form
        className="mt-4 grid gap-3 md:grid-cols-2"
        onSubmit={async (event) => {
          event.preventDefault();
          setBusy(true);
          setError("");
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
          } catch (cause) {
            setError(cause instanceof Error ? cause.message : "Unable to create host.");
          } finally {
            setBusy(false);
          }
        }}
      >
        {(
          [
            ["code", "Host reference code"],
            ["name", "Host name"],
            ["address", "Host address"],
            ["city", "Host city"],
          ] as const
        ).map(([key, label]) => (
          <label className="text-sm" key={key}>
            {label}
            <input
              required
              className="mt-1 block w-full rounded border p-2"
              value={value[key]}
              onChange={(event) =>
                setValue((current) => ({ ...current, [key]: event.target.value }))
              }
            />
          </label>
        ))}
        <ProgramTermFields
          reference={reference}
          program={value.program_id}
          term={value.academic_term_id}
          onProgram={(program_id) => setValue((current) => ({ ...current, program_id }))}
          onTerm={(academic_term_id) => setValue((current) => ({ ...current, academic_term_id }))}
        />
        <label className="text-sm">
          Program capacity
          <input
            required
            className="mt-1 block w-full rounded border p-2"
            type="number"
            min="0"
            max="100000"
            value={value.capacity}
            onChange={(event) =>
              setValue((current) => ({ ...current, capacity: event.target.value }))
            }
          />
        </label>
        <div className="flex items-end">
          <Button disabled={busy} type="submit">
            Save establishment
          </Button>
        </div>
        {error && <p role="alert">{error}</p>}
      </form>
    </details>
  );
}

function ProgramTermFields({
  reference,
  program,
  term,
  onProgram,
  onTerm,
}: {
  reference: PlacementReference;
  program: string;
  term: string;
  onProgram: (value: string) => void;
  onTerm: (value: string) => void;
}) {
  return (
    <>
      <label className="text-sm">
        Program
        <select
          required
          className="mt-1 block w-full rounded border p-2"
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
      </label>
      <label className="text-sm">
        Academic term
        <select
          required
          className="mt-1 block w-full rounded border p-2"
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
      </label>
    </>
  );
}

function HostCapacity({ host, reference }: { host: Host; reference: PlacementReference }) {
  const [rows, setRows] = useState<
    { program_id: number; academic_term_id: number; capacity: number }[]
  >([]);
  const [program, setProgram] = useState("");
  const [term, setTerm] = useState("");
  const [capacity, setCapacity] = useState("");
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
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
      <form
        className="grid gap-3 md:grid-cols-2"
        onSubmit={async (event) => {
          event.preventDefault();
          setBusy(true);
          setMessage("");
          try {
            await mutate(
              `/hosts/${host.id}/capacities`,
              {
                capacities: [
                  {
                    program_id: Number(program),
                    academic_term_id: Number(term),
                    capacity: Number(capacity),
                  },
                ],
              },
              "PUT",
            );
            await load();
            setMessage("Program capacity saved.");
          } catch (cause) {
            setMessage(cause instanceof Error ? cause.message : "Unable to save capacity.");
          } finally {
            setBusy(false);
          }
        }}
      >
        <ProgramTermFields
          reference={reference}
          program={program}
          term={term}
          onProgram={setProgram}
          onTerm={setTerm}
        />
        <label className="text-sm">
          Total slots for this program
          <input
            required
            className="mt-1 block w-full rounded border p-2"
            type="number"
            min="0"
            max="100000"
            value={capacity}
            onChange={(event) => setCapacity(event.target.value)}
          />
        </label>
        <div className="flex items-end">
          <Button disabled={busy} type="submit">
            Save capacity
          </Button>
        </div>
        <p role="status">{message}</p>
      </form>
    </details>
  );
}

function HostForm({ host }: { host: Host }) {
  const [saved, setSaved] = useState(host);
  const [value, setValue] = useState(host);
  const [editing, setEditing] = useState(false);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
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
  const dirty = JSON.stringify(value) !== JSON.stringify(saved);
  return (
    <Card>
      <div className="flex items-start justify-between gap-3">
        <CardTitle>Establishment details · {saved.code}</CardTitle>
        {!editing && (
          <Button
            onClick={() => {
              setEditing(true);
              setMessage("");
            }}
          >
            Edit profile
          </Button>
        )}
      </div>
      {!editing ? (
        <dl className="grid gap-6 sm:grid-cols-2">
          {fields.map(([key, label]) => (
            <div key={key}>
              <dt className="mb-1 text-xs uppercase text-muted-foreground">{label}</dt>
              <dd className="break-words">{saved[key] ?? "Not provided"}</dd>
            </div>
          ))}
          <div className="sm:col-span-2">
            <dt className="text-xs uppercase text-muted-foreground">About the establishment</dt>
            <dd className="mt-1 whitespace-pre-wrap">
              {saved.description || "No description provided."}
            </dd>
          </div>
        </dl>
      ) : (
        <form
          onSubmit={async (event) => {
            event.preventDefault();
            if (busy) return;
            if (Boolean(value.latitude) !== Boolean(value.longitude)) {
              setMessage("Provide both coordinates, or clear both.");
              return;
            }
            setBusy(true);
            setMessage("");
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
              setMessage("Company profile saved.");
            } catch (cause) {
              setMessage(cause instanceof Error ? cause.message : "Unable to save profile.");
            } finally {
              setBusy(false);
            }
          }}
        >
          <fieldset disabled={busy} className="grid gap-4 sm:grid-cols-2">
            {fields.map(([key, label]) => (
              <label key={key} className="text-xs uppercase text-muted-foreground">
                {label}
                <input
                  aria-label={`${host.code} ${label}`}
                  className="mt-1 block w-full rounded border p-2 text-sm text-foreground"
                  required={["name", "address", "city"].includes(key)}
                  type={
                    key === "contact_email"
                      ? "email"
                      : ["latitude", "longitude"].includes(key)
                        ? "number"
                        : "text"
                  }
                  step="any"
                  min={key === "latitude" ? -90 : key === "longitude" ? -180 : undefined}
                  max={key === "latitude" ? 90 : key === "longitude" ? 180 : undefined}
                  value={value[key] ?? ""}
                  onChange={(event) =>
                    setValue((current) => ({ ...current, [key]: event.target.value }))
                  }
                />
              </label>
            ))}
            <label className="sm:col-span-2 text-sm">
              About the establishment
              <textarea
                className="mt-1 min-h-28 w-full rounded border p-2"
                maxLength={10000}
                value={value.description ?? ""}
                onChange={(event) =>
                  setValue((current) => ({ ...current, description: event.target.value }))
                }
              />
            </label>
          </fieldset>
          <div className="mt-4 flex flex-wrap gap-3">
            <Button type="submit" disabled={busy || !dirty}>
              {busy ? "Saving…" : "Save profile"}
            </Button>
            <Button
              variant="outline"
              disabled={busy}
              onClick={() => {
                setValue(saved);
                setEditing(false);
                setMessage("");
              }}
            >
              Cancel
            </Button>
            <span className="self-center text-xs text-muted-foreground">
              {dirty ? "Unsaved changes" : "No changes"}
            </span>
          </div>
        </form>
      )}
      {message && (
        <p role="status" className="mt-4">
          {message}
        </p>
      )}
    </Card>
  );
}
