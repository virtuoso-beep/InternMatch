import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import type { PlacementReference } from "@/lib/placement";
import { useSessionUser } from "./SessionGuard";
import { Button, Card, CardTitle, PageHeader } from "./ui";

type Account = {
  id: number;
  name: string;
  email: string;
  role: string | null;
  status: string;
  programs: { id: number }[];
  host_establishments: { id: number }[];
};
const roles = [
  ["student", "Student"],
  ["supervisor", "Host supervisor"],
  ["coordinator", "Practicum coordinator"],
  ["dean", "Program chair / dean"],
  ["admin", "System administrator"],
];
export function AccountRegistry() {
  const [items, setItems] = useState<Account[] | null>(null);
  const [reference, setReference] = useState<PlacementReference | null>(null);
  const [page, setPage] = useState(1);
  const [last, setLast] = useState(1);
  const [revision, setRevision] = useState(0);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [draft, setDraft] = useState({
    name: "",
    email: "",
    password: "",
    role: "student",
    status: "pending",
  });
  useEffect(() => {
    let active = true;
    Promise.all([
      api<{ data: Account[]; last_page: number }>(`/users?page=${page}`),
      api<PlacementReference>("/placement-reference"),
    ])
      .then(([accounts, refs]) => {
        if (active) {
          setItems(accounts.data);
          setLast(accounts.last_page);
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
        title="User accounts"
        subtitle="Provision accounts and assign program or host access."
      />
      {message && (
        <p role="status" className="mb-3">
          {message}
        </p>
      )}
      <details className="mb-5 rounded border p-4">
        <summary className="cursor-pointer font-semibold">Create account</summary>
        <form
          className="mt-3 grid gap-3 md:grid-cols-2"
          onSubmit={async (event) => {
            event.preventDefault();
            setBusy(true);
            setMessage("");
            try {
              await mutate("/users", draft);
              setDraft({ name: "", email: "", password: "", role: "student", status: "pending" });
              setRevision((value) => value + 1);
              setMessage("Account created. Assign its program or host access as needed.");
            } catch (cause) {
              setMessage(cause instanceof Error ? cause.message : "Unable to create account.");
            } finally {
              setBusy(false);
            }
          }}
        >
          <label className="text-sm">
            Full name
            <input
              required
              maxLength={255}
              className="mt-1 block w-full rounded border p-2"
              value={draft.name}
              onChange={(event) => setDraft({ ...draft, name: event.target.value })}
            />
          </label>
          <label className="text-sm">
            Institutional email
            <input
              type="email"
              required
              className="mt-1 block w-full rounded border p-2"
              value={draft.email}
              onChange={(event) => setDraft({ ...draft, email: event.target.value })}
            />
          </label>
          <label className="text-sm">
            Initial password (at least 12 characters)
            <input
              type="password"
              required
              minLength={12}
              autoComplete="new-password"
              className="mt-1 block w-full rounded border p-2"
              value={draft.password}
              onChange={(event) => setDraft({ ...draft, password: event.target.value })}
            />
          </label>
          <label className="text-sm">
            Role
            <select
              className="mt-1 block w-full rounded border p-2"
              value={draft.role}
              onChange={(event) => setDraft({ ...draft, role: event.target.value })}
            >
              {roles.map(([value, label]) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </label>
          <label className="text-sm">
            Account status
            <select
              className="mt-1 block w-full rounded border p-2"
              value={draft.status}
              onChange={(event) => setDraft({ ...draft, status: event.target.value })}
            >
              <option value="pending">Pending</option>
              <option value="active">Active</option>
              <option value="disabled">Disabled</option>
            </select>
          </label>
          <div className="flex items-end">
            <Button type="submit" disabled={busy}>
              Create account
            </Button>
          </div>
        </form>
      </details>
      {!items && !message && <p role="status">Loading accounts…</p>}
      <div className="space-y-4">
        {reference &&
          items?.map((account) => (
            <AccountForm
              key={`${account.id}:${revision}`}
              account={account}
              reference={reference}
            />
          ))}
      </div>
      {last > 1 && (
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

function AccountForm({ account, reference }: { account: Account; reference: PlacementReference }) {
  const actor = useSessionUser();
  const [role, setRole] = useState(account.role ?? "");
  const [savedRole, setSavedRole] = useState(account.role);
  const [status, setStatus] = useState(account.status);
  const [programs, setPrograms] = useState(account.programs.map((item) => item.id));
  const [hosts, setHosts] = useState(account.host_establishments.map((item) => item.id));
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  async function save(path: string, data: unknown, method: string) {
    setBusy(true);
    setMessage("");
    try {
      await mutate(path, data, method);
      if (path.endsWith("/access")) setSavedRole(role);
      setMessage("Access saved.");
    } catch (cause) {
      setMessage(cause instanceof Error ? cause.message : "Unable to save access.");
    } finally {
      setBusy(false);
    }
  }
  return (
    <Card>
      <CardTitle>{account.name}</CardTitle>
      <p className="mb-3 text-sm">{account.email}</p>
      <div className="flex flex-wrap items-end gap-3">
        <label className="text-sm">
          Role
          <select
            aria-label={`${account.email} role`}
            className="mt-1 block rounded border p-2"
            value={role}
            onChange={(event) => setRole(event.target.value)}
          >
            <option value="">Unassigned</option>
            {roles.map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm">
          Status
          <select
            aria-label={`${account.email} status`}
            className="mt-1 block rounded border p-2"
            value={status}
            onChange={(event) => setStatus(event.target.value)}
          >
            <option value="pending">Pending</option>
            <option value="active">Active</option>
            <option value="disabled">Disabled</option>
          </select>
        </label>
        <Button
          disabled={busy || actor.id === account.id || !role}
          onClick={() => void save(`/users/${account.id}/access`, { role, status }, "PATCH")}
        >
          Save role and status
        </Button>
      </div>
      {(savedRole === "coordinator" || savedRole === "dean") && (
        <fieldset className="mt-4 rounded border p-3">
          <legend>Assigned programs</legend>
          <div className="mb-3 flex flex-wrap gap-3">
            {reference.programs.map((item) => (
              <label key={item.id} className="text-sm">
                <input
                  type="checkbox"
                  checked={programs.includes(item.id)}
                  onChange={(event) =>
                    setPrograms(
                      event.target.checked
                        ? [...programs, item.id]
                        : programs.filter((id) => id !== item.id),
                    )
                  }
                />{" "}
                {item.code}
              </label>
            ))}
          </div>
          <Button
            disabled={busy}
            onClick={() =>
              void save(`/users/${account.id}/programs`, { program_ids: programs }, "PUT")
            }
          >
            Save program access
          </Button>
        </fieldset>
      )}
      {savedRole === "supervisor" && (
        <fieldset className="mt-4 rounded border p-3">
          <legend>Assigned establishments</legend>
          <div className="mb-3 flex flex-wrap gap-3">
            {reference.hosts.map((item) => (
              <label key={item.id} className="text-sm">
                <input
                  type="checkbox"
                  checked={hosts.includes(item.id)}
                  onChange={(event) =>
                    setHosts(
                      event.target.checked
                        ? [...hosts, item.id]
                        : hosts.filter((id) => id !== item.id),
                    )
                  }
                />{" "}
                {item.name}
              </label>
            ))}
          </div>
          <Button
            disabled={busy}
            onClick={() => void save(`/users/${account.id}/hosts`, { host_ids: hosts }, "PUT")}
          >
            Save host access
          </Button>
        </fieldset>
      )}
      <p role="status" className="mt-3 text-sm">
        {message}
      </p>
    </Card>
  );
}
