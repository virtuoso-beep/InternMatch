import { useEffect, useRef, useState } from "react";
import { api, mutate } from "@/lib/api";
import type { PlacementReference } from "@/lib/placement";
import { useSessionUser } from "./SessionGuard";
import { Plus } from "lucide-react";
import { toast } from "sonner";
import { Button, Card, FormField, FormGrid, FormModal, FormSection, Modal, PageHeader } from "./ui";
import { ConfirmButton } from "./actions";
import { inputClass, toFormError, type FormError } from "./formUtils";

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
  `rounded-full px-3 py-1 capitalize ${status === "active" ? "bg-success-soft text-success" : status === "pending" ? "bg-warn-soft text-warn" : "bg-brand-soft text-brand"}`;

export function AccountRegistry({ studentsOnly = false }: { studentsOnly?: boolean }) {
  const actor = useSessionUser();
  const [creating, setCreating] = useState(false);
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
  const emptyDraft = {
    name: "",
    email: "",
    password: "",
    role: "student",
    status: "pending",
  };
  const [draft, setDraft] = useState(emptyDraft);
  const [formError, setFormError] = useState<FormError | null>(null);
  const closeCreate = () => {
    setCreating(false);
    setDraft(emptyDraft);
    setFormError(null);
  };
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
        <Button
          onClick={() => {
            setFormError(null);
            setCreating(true);
          }}
        >
          <Plus size={16} aria-hidden="true" />
          {studentsOnly ? "Add student" : "Add user"}
        </Button>
        {studentsOnly && (
          <a className="rounded border px-4 py-2 text-sm" href="/admin/student-import">
            Import Students
          </a>
        )}
      </div>
      <FormModal
        open={creating}
        onClose={closeCreate}
        title={studentsOnly ? "Add student" : "Add user"}
        description="Provision an account. Program or host access is assigned afterwards."
        submitLabel="Create account"
        busyLabel="Creating…"
        busy={busy}
        error={formError}
        onSubmit={async () => {
          setBusy(true);
          setMessage("");
          setFormError(null);
          try {
            await mutate("/users", draft);
            setDraft(emptyDraft);
            setRevision((value) => value + 1);
            setCreating(false);
            toast.success("Account created", {
              description: "Assign its program or host access as needed.",
            });
          } catch (cause) {
            setFormError(toFormError(cause, "Unable to create the account."));
          } finally {
            setBusy(false);
          }
        }}
      >
        <FormSection title="Account">
          <FormGrid>
            <FormField label="Full name" required>
              <input
                required
                name="name"
                maxLength={255}
                className={inputClass}
                value={draft.name}
                onChange={(event) => setDraft({ ...draft, name: event.target.value })}
              />
            </FormField>
            <FormField label="Institutional email" required>
              <input
                type="email"
                name="email"
                required
                className={inputClass}
                value={draft.email}
                onChange={(event) => setDraft({ ...draft, email: event.target.value })}
              />
            </FormField>
          </FormGrid>
          <FormField label="Initial password" required helper="At least 12 characters.">
            <input
              type="password"
              name="password"
              required
              minLength={12}
              autoComplete="new-password"
              className={inputClass}
              value={draft.password}
              onChange={(event) => setDraft({ ...draft, password: event.target.value })}
            />
          </FormField>
        </FormSection>
        <FormSection title="Role and status">
          <FormGrid>
            <FormField label="Role">
              <select
                name="role"
                className={inputClass}
                value={draft.role}
                onChange={(event) => setDraft({ ...draft, role: event.target.value })}
              >
                {roles.map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </FormField>
            <FormField label="Account status">
              <select
                name="status"
                className={inputClass}
                value={draft.status}
                onChange={(event) => setDraft({ ...draft, status: event.target.value })}
              >
                <option value="pending">Pending</option>
                <option value="active">Active</option>
                <option value="disabled">Disabled</option>
              </select>
            </FormField>
          </FormGrid>
        </FormSection>
      </FormModal>
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
        <p className="mb-3 text-sm text-muted-foreground">
          Search and filters apply to all account records.
        </p>
        {loadError && <p role="alert">{loadError} Use Refresh accounts to retry.</p>}
        {!items && !loadError && <p role="status">Loading accounts…</p>}
        {items && (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="border-b text-xs text-muted-foreground">
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
                        <div className="text-muted-foreground">{account.email}</div>
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
                        <div className="flex flex-wrap gap-2">
                          <Button size="sm" onClick={() => setSelected(account.id)}>
                            Manage access <span className="sr-only">{account.email}</span>
                          </Button>
                          {account.role &&
                            actor.id !== account.id &&
                            account.status === "active" && (
                              <ConfirmButton
                                size="sm"
                                tone="danger"
                                title={`Disable ${account.name}?`}
                                description={`${account.email} can no longer sign in. Records are kept, and you can enable the account again.`}
                                confirmLabel="Disable"
                                success="Account disabled"
                                onConfirm={() =>
                                  mutate(
                                    `/users/${account.id}/access`,
                                    { role: account.role, status: "disabled" },
                                    "PATCH",
                                  )
                                }
                                onDone={() => setRevision((value) => value + 1)}
                              >
                                Disable
                              </ConfirmButton>
                            )}
                          {account.role &&
                            actor.id !== account.id &&
                            account.status === "disabled" && (
                              <ConfirmButton
                                size="sm"
                                title={`Enable ${account.name}?`}
                                description={`${account.email} can sign in again with the same role and access.`}
                                confirmLabel="Enable"
                                success="Account enabled"
                                onConfirm={() =>
                                  mutate(
                                    `/users/${account.id}/access`,
                                    { role: account.role, status: "active" },
                                    "PATCH",
                                  )
                                }
                                onDone={() => setRevision((value) => value + 1)}
                              >
                                Enable
                              </ConfirmButton>
                            )}
                          {account.status === "pending" && account.email && (
                            <ConfirmButton
                              size="sm"
                              title={`Send an activation link to ${account.name}?`}
                              description={`A one-time link goes to ${account.email} so the person can set their own password. It expires in 72 hours, and any earlier unused link stops working.`}
                              confirmLabel="Send link"
                              success="Activation link sent"
                              onConfirm={() =>
                                mutate(`/users/${account.id}/activation`, {}, "POST")
                              }
                              onDone={() => setRevision((value) => value + 1)}
                            >
                              Send activation link
                            </ConfirmButton>
                          )}
                        </div>
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
            <p className="mt-3 text-sm text-muted-foreground">
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
                onClose={(changed) => {
                  setSelected(null);
                  // Refetch only after a save, so the opener keeps focus when nothing changed.
                  if (changed) setRevision((value) => value + 1);
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
  onClose: (changed: boolean) => void;
}) {
  const actor = useSessionUser();
  const changed = useRef(false);
  const close = () => onClose(changed.current);
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
      changed.current = true;
      if (path.endsWith("/access")) setSavedRole(role);
      toast.success(
        path.endsWith("/access")
          ? "Role and status saved"
          : path.endsWith("/programs")
            ? "Program access saved"
            : "Host access saved",
      );
    } catch (cause) {
      setMessage(cause instanceof Error ? cause.message : "Unable to save access.");
    } finally {
      setBusy(false);
    }
  }
  return (
    <Modal
      open
      size="lg"
      onClose={close}
      dismissible={!busy}
      title={`Manage access: ${account.name}`}
      description={`${account.email} · ${account.program_label}. Save each section separately; closing discards unsaved changes.`}
      footer={
        <Button variant="outline" disabled={busy} onClick={close}>
          Close
        </Button>
      }
    >
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
        {message && (
          <p
            role="alert"
            className="mt-3 rounded-lg border border-brand/20 bg-brand-soft px-3 py-2 text-sm text-brand"
          >
            {message}
          </p>
        )}
      </fieldset>
    </Modal>
  );
}
