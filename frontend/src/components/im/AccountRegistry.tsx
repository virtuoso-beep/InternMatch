import { useEffect, useRef, useState } from "react";
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
  program_label: string;
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
const statusTone = (status: string) =>
  `rounded-full px-3 py-1 capitalize ${status === "active" ? "bg-emerald-50 text-emerald-700" : status === "pending" ? "bg-amber-50 text-amber-700" : "bg-red-50 text-red-700"}`;

export function AccountRegistry({ studentsOnly = false }: { studentsOnly?: boolean }) {
  const [creating, setCreating] = useState(false);
  const createDialog = useRef<HTMLElement>(null);
  // Move focus into the dialog when it opens so Escape and keyboard users work immediately.
  useEffect(() => {
    if (creating) createDialog.current?.focus();
  }, [creating]);
  const [items, setItems] = useState<Account[] | null>(null);
  const [reference, setReference] = useState<PlacementReference | null>(null);
  const [page, setPage] = useState(1);
  const [last, setLast] = useState(1);
  const [revision, setRevision] = useState(0);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [search, setSearch] = useState("");
  const [roleFilter, setRoleFilter] = useState(studentsOnly ? "student" : "");
  const [statusFilter, setStatusFilter] = useState("");
  const [selected, setSelected] = useState<number | null>(null);
  const [loadError, setLoadError] = useState("");
  const [draft, setDraft] = useState({
    name: "",
    email: "",
    password: "",
    role: "student",
    status: "pending",
  });
  useEffect(() => {
    let active = true;
    setItems(null);
    setLoadError("");
    Promise.all([
      api<{ data: Account[]; last_page: number }>(
        `/users?${new URLSearchParams({ page: String(page), search, role: studentsOnly ? "student" : roleFilter, status: statusFilter })}`,
      ),
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
        if (active) setLoadError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [page, revision, search, roleFilter, statusFilter, studentsOnly]);
  const visible = items;
  return (
    <>
      <PageHeader
        title={studentsOnly ? "Students" : "User accounts"}
        subtitle="Provision accounts and assign program or host access."
      />
      {message && (
        <p role="status" className="mb-3">
          {message}
        </p>
      )}
      <div className="mb-5 flex gap-3">
        <Button onClick={() => setCreating(true)}>
          {studentsOnly ? "+ Add Student" : "+ Add User"}
        </Button>
        {studentsOnly && (
          <a className="rounded border px-4 py-2 text-sm" href="/admin/student-import">
            Import Students
          </a>
        )}
      </div>
      {creating && (
        <div
          className="fixed inset-0 z-50 grid place-items-center bg-black/60 p-4"
          onClick={(e) => {
            if (e.target === e.currentTarget && !busy) setCreating(false);
          }}
        >
          <section
            ref={createDialog}
            tabIndex={-1}
            role="dialog"
            aria-modal="true"
            aria-label={studentsOnly ? "Add Student" : "Add User"}
            className="max-h-[90dvh] w-full max-w-xl overflow-auto rounded-2xl bg-card p-6 shadow-xl outline-none"
            onKeyDown={(e) => {
              if (e.key === "Escape" && !busy) setCreating(false);
            }}
          >
            <div className="flex items-center justify-between">
              <h2 className="text-xl font-bold">{studentsOnly ? "Add Student" : "Add User"}</h2>
              <Button variant="outline" disabled={busy} onClick={() => setCreating(false)}>
                Cancel
              </Button>
            </div>
            {message && (
              <p role="status" className="my-3">
                {message}
              </p>
            )}
            <form
              className="mt-3 grid gap-3 md:grid-cols-2"
              onSubmit={async (event) => {
                event.preventDefault();
                setBusy(true);
                setMessage("");
                try {
                  await mutate("/users", draft);
                  setDraft({
                    name: "",
                    email: "",
                    password: "",
                    role: "student",
                    status: "pending",
                  });
                  setRevision((value) => value + 1);
                  setMessage("Account created. Assign its program or host access as needed.");
                  setCreating(false);
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
          </section>
        </div>
      )}
      <Card>
        {!studentsOnly && (
          <div
            role="tablist"
            aria-label="Account roles"
            className="mb-5 flex overflow-auto border-b"
          >
            {[["", "All Users"], ...roles].map(([value, label]) => (
              <button
                key={value}
                role="tab"
                aria-selected={roleFilter === value}
                className={`whitespace-nowrap border-b-2 px-4 py-3 text-sm ${roleFilter === value ? "border-brand font-bold text-brand" : "border-transparent"}`}
                onClick={() => {
                  setRoleFilter(value ?? "");
                  setPage(1);
                }}
              >
                {label}
              </button>
            ))}
          </div>
        )}
        <div className="mb-4 flex flex-wrap items-end gap-3">
          <label className="text-sm">
            Find account
            <input
              className="mt-1 block rounded border p-2"
              value={search}
              onChange={(event) => {
                setPage(1);
                setSearch(event.target.value);
              }}
              placeholder="Name or email"
            />
          </label>
          <label className="text-sm">
            Filter role
            <select
              className="mt-1 block rounded border p-2"
              aria-label="Filter role"
              value={roleFilter}
              disabled={studentsOnly}
              onChange={(event) => {
                setPage(1);
                setRoleFilter(event.target.value);
              }}
            >
              <option value="">All roles</option>
              {roles.map(([value, label]) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </label>
          <label className="text-sm">
            Filter status
            <select
              className="mt-1 block rounded border p-2"
              aria-label="Filter status"
              value={statusFilter}
              onChange={(event) => {
                setPage(1);
                setStatusFilter(event.target.value);
              }}
            >
              <option value="">All statuses</option>
              <option value="active">Active</option>
              <option value="pending">Pending</option>
              <option value="disabled">Disabled</option>
            </select>
          </label>
          <Button
            onClick={() => {
              setSearch("");
              setRoleFilter(studentsOnly ? "student" : "");
              setPage(1);
              setStatusFilter("");
            }}
          >
            Clear filters
          </Button>
          <Button
            disabled={!items && !loadError}
            onClick={() => {
              setSelected(null);
              setRevision((value) => value + 1);
            }}
          >
            Refresh accounts
          </Button>
        </div>
        <p className="mb-3 text-sm text-slate-500">
          Search and filters apply to all account records.
        </p>
        {loadError && <p role="alert">{loadError} Use Refresh accounts to retry.</p>}
        {!items && !loadError && <p role="status">Loading accounts…</p>}
        {items && (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="border-b text-xs uppercase text-slate-500">
                  <tr>
                    <th className="p-3">Name / email</th>
                    <th className="p-3 max-sm:hidden">Role</th>
                    <th className="p-3 max-sm:hidden">Status</th>
                    <th className="p-3">Action</th>
                  </tr>
                </thead>
                <tbody>
                  {visible?.map((account) => (
                    <tr key={account.id} className="border-b">
                      <td className="p-3">
                        <span className="font-semibold">
                          {account.name} · {account.program_label}
                        </span>
                        <div className="text-slate-500">{account.email}</div>
                        <div className="mt-2 flex flex-wrap items-center gap-2 text-xs sm:hidden">
                          <span>
                            {roles.find(([value]) => value === account.role)?.[1] ?? "Unassigned"}
                          </span>
                          <span className={statusTone(account.status)}>{account.status}</span>
                        </div>
                      </td>
                      <td className="p-3 max-sm:hidden">
                        {roles.find(([value]) => value === account.role)?.[1] ?? "Unassigned"}
                      </td>
                      <td className="p-3 max-sm:hidden">
                        <span className={statusTone(account.status)}>{account.status}</span>
                      </td>
                      <td className="p-3">
                        <Button onClick={() => setSelected(account.id)}>
                          Edit <span className="sr-only">{account.email}</span>
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {!visible?.length && (
              <p className="py-5" role="status">
                No accounts match these filters on this page.
              </p>
            )}
            <p className="mt-3 text-sm text-slate-500">
              Showing {visible?.length} of {items.length} accounts on page {page}.
            </p>
          </>
        )}
      </Card>
      <div className="space-y-4">
        {reference &&
          items
            ?.filter((account) => account.id === selected)
            .map((account) => (
              <AccountForm
                key={`${account.id}:${revision}`}
                account={account}
                reference={reference}
                onClose={() => {
                  setSelected(null);
                  setRevision((value) => value + 1);
                }}
              />
            ))}
      </div>
      {last > 1 && (
        <div className="mt-4 flex gap-3">
          <Button
            disabled={!items || page === 1}
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
            disabled={!items || page === last}
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

function AccountForm({
  account,
  reference,
  onClose,
}: {
  account: Account;
  reference: PlacementReference;
  onClose: () => void;
}) {
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
      setMessage(
        path.endsWith("/activation")
          ? `Activation link sent to ${account.email ?? "the account e-mail"}. It works once and expires in 72 hours unless re-sent.`
          : "Access saved.",
      );
    } catch (cause) {
      setMessage(cause instanceof Error ? cause.message : "Unable to save access.");
    } finally {
      setBusy(false);
    }
  }
  return (
    <Card>
      <CardTitle>
        {account.name} · {account.program_label}
      </CardTitle>
      <p className="mb-3 text-sm">{account.email}</p>
      <Button disabled={busy} onClick={onClose}>
        Close editor
      </Button>
      <p className="my-3 text-sm text-slate-500">
        Save each access section separately. Closing discards unsaved changes.
      </p>
      {actor.id === account.id && (
        <p className="mb-3 text-sm">You cannot change your own role or account status.</p>
      )}
      <fieldset disabled={busy}>
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
            <legend>
              {savedRole === "coordinator" ? "Assigned program" : "Assigned programs"}
            </legend>
            {savedRole === "coordinator" && (
              <p className="mb-2 text-xs text-muted-foreground">
                A coordinator holds exactly one program at a time; a program can have several
                coordinators. Changing it ends the current assignment and keeps it in history.
              </p>
            )}
            <div className="mb-3 flex flex-wrap gap-3">
              {savedRole === "coordinator" && (
                <label className="text-sm">
                  <input
                    type="radio"
                    name={`program-${account.id}`}
                    checked={programs.length === 0}
                    onChange={() => setPrograms([])}
                  />{" "}
                  No program
                </label>
              )}
              {reference.programs.map((item) => (
                <label key={item.id} className="text-sm">
                  {savedRole === "coordinator" ? (
                    <input
                      type="radio"
                      name={`program-${account.id}`}
                      checked={programs.length === 1 && programs[0] === item.id}
                      onChange={() => setPrograms([item.id])}
                    />
                  ) : (
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
                    />
                  )}{" "}
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
        <fieldset className="mt-4 rounded border p-3">
          <legend>Account activation</legend>
          <p className="mb-3 text-xs text-muted-foreground">
            E-mail a one-time link so the person can set their own password. Sending a new link
            cancels any earlier unused link.
          </p>
          <Button
            variant="outline"
            disabled={busy || !account.email}
            onClick={() => void save(`/users/${account.id}/activation`, {}, "POST")}
          >
            Send activation link
          </Button>
        </fieldset>
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
      </fieldset>
    </Card>
  );
}
