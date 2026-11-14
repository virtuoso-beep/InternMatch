import { AdminFormDialog } from "./AdminFormDialog";
import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, CardTitle, PageHeader, Pill } from "./ui";

type Term = {
  id: number;
  code: string;
  name: string;
  academic_year: string;
  starts_on: string;
  ends_on: string;
  is_active: boolean;
};
type Overview = {
  total_users: number;
  users_by_role: { role: string | null; total: number }[];
  programs: { id: number; code: string; name: string; cluster: string; is_active: boolean }[];
  terms: Term[];
  chat_enabled: boolean;
  recent_activity: { id: number; action: string; created_at: string }[];
};
const linkClass =
  "inline-block rounded-lg border border-brand/30 px-4 py-2 text-sm font-semibold text-brand hover:bg-brand-soft";
function AdminLink({ section, children }: { section: string; children: React.ReactNode }) {
  return (
    <a className={linkClass} href={`/admin/${section}`}>
      {children}
    </a>
  );
}

export function AdminOverview() {
  const [data, setData] = useState<Overview | null>(null);
  const [error, setError] = useState("");
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    setError("");
    api<Overview>("/admin/overview")
      .then((d) => {
        if (active) setData(d);
      })
      .catch((e) => {
        if (active) setError(e.message);
      });
    return () => {
      active = false;
    };
  }, [revision]);
  const count = (role: string) => data?.users_by_role.find((r) => r.role === role)?.total ?? 0;
  return (
    <div className="space-y-5">
      <div className="flex items-start justify-between gap-3">
        <PageHeader title="Overview" subtitle="System-wide summary and platform status" />
        <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
          Refresh overview
        </Button>
      </div>
      {error && <p role="alert">{error}</p>}
      {!data && !error && <p role="status">Loading overview…</p>}
      {data && (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {[
              ["Total Users", data.total_users],
              ["Students", count("student")],
              ["Coordinators", count("coordinator")],
              ["Active Programs", data.programs.filter((p) => p.is_active).length],
            ].map(([label, value]) => (
              <Card key={label}>
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="my-2 text-3xl font-bold">{value}</p>
                <p className="text-xs text-muted-foreground">Current system records</p>
              </Card>
            ))}
          </div>
          <div className="grid gap-5 lg:grid-cols-[1.2fr_1fr]">
            <Card>
              <CardTitle>Users by Role</CardTitle>
              {["student", "coordinator", "supervisor", "dean", "admin"].map((role) => (
                <div key={role} className="flex justify-between border-b py-3 text-sm">
                  <span className="capitalize">{role}</span>
                  <strong>{count(role)}</strong>
                </div>
              ))}
              <div className="mt-4">
                <AdminLink section="users">Manage accounts</AdminLink>
              </div>
            </Card>
            <Card>
              <CardTitle>Programs</CardTitle>
              {data.programs
                .filter((p) => p.is_active)
                .map((p) => (
                  <div key={p.id} className="flex justify-between gap-3 border-b py-3 text-sm">
                    <span>
                      <strong>{p.code}</strong>
                      <small className="ml-2 text-muted-foreground">{p.cluster}</small>
                    </span>
                    <Pill tone={p.is_active ? "success" : "muted"}>
                      {p.is_active ? "Active" : "Inactive"}
                    </Pill>
                  </div>
                ))}
              <div className="mt-4">
                <AdminLink section="programs">Manage programs</AdminLink>
              </div>
            </Card>
          </div>
          <div className="grid gap-5 lg:grid-cols-[1.2fr_1fr]">
            <Card>
              <CardTitle>Messaging Status</CardTitle>
              <div className="my-4 flex justify-between">
                <span>Global chatbox</span>
                <Pill tone={data.chat_enabled ? "success" : "muted"}>
                  {data.chat_enabled ? "Active" : "Disabled"}
                </Pill>
              </div>
              <p className="mb-4 text-sm text-muted-foreground">
                Control availability without access to private conversations.
              </p>
              <AdminLink section="chat-settings">Manage Chatbox</AdminLink>
            </Card>
            <Card>
              <CardTitle>Recent Administrative Activity</CardTitle>
              {data.recent_activity.map((a) => (
                <div className="border-b py-3 text-sm" key={a.id}>
                  <p>{a.action.replaceAll("_", " ").replaceAll(".", " · ")}</p>
                  <small className="text-muted-foreground">{a.created_at}</small>
                </div>
              ))}
              {!data.recent_activity.length && <p>No recorded activity.</p>}
              <div className="mt-4">
                <AdminLink section="audit">View audit trail</AdminLink>
              </div>
            </Card>
          </div>
        </>
      )}
    </div>
  );
}

export function AdminTerms() {
  const [creating, setCreating] = useState(false);
  const [terms, setTerms] = useState<Term[]>([]);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [revision, setRevision] = useState(0);
  const empty = { code: "", name: "", academic_year: "", starts_on: "", ends_on: "" };
  const [draft, setDraft] = useState(empty);
  useEffect(() => {
    let active = true;
    api<Overview>("/admin/overview")
      .then((d) => {
        if (active) setTerms(d.terms);
      })
      .catch((e) => {
        if (active) setMessage(e.message);
      });
    return () => {
      active = false;
    };
  }, [revision]);
  return (
    <>
      <PageHeader
        title="Academic Terms"
        subtitle="Configure academic years and semesters. Availability controls selection for new records; existing history is retained."
      />
      {message && (
        <p role="status" className="mb-4">
          {message}
        </p>
      )}
      <div className="mb-5">
        <Button onClick={() => setCreating(true)}>+ Add Term</Button>
      </div>
      {creating && (
        <AdminFormDialog title="Add Term" onClose={() => setCreating(false)}>
          <form
            onSubmit={async (e) => {
              e.preventDefault();
              setBusy(true);
              try {
                await mutate("/academic-terms", draft);
                setDraft(empty);
                setCreating(false);
                setRevision((v) => v + 1);
                setMessage("Academic term saved.");
              } catch (e) {
                setMessage((e as Error).message);
              } finally {
                setBusy(false);
              }
            }}
          >
            <div className="grid gap-4 sm:grid-cols-2">
              {(
                [
                  ["code", "Term code"],
                  ["name", "Term name"],
                  ["academic_year", "Academic year"],
                  ["starts_on", "Start date"],
                  ["ends_on", "End date"],
                ] as const
              ).map(([key, label]) => (
                <label key={key} className="text-sm">
                  {label}
                  <input
                    required
                    type={key.endsWith("_on") ? "date" : "text"}
                    value={draft[key]}
                    onChange={(e) => setDraft({ ...draft, [key]: e.target.value })}
                    className="mt-1 block w-full rounded border p-2"
                  />
                </label>
              ))}
            </div>
            <div className="mt-4">
              <Button disabled={busy} type="submit">
                Add term
              </Button>
            </div>
          </form>
        </AdminFormDialog>
      )}
      <div className="mt-5">
        <Card>
          <div className="overflow-auto">
            <table className="w-full text-left text-sm">
              <thead>
                <tr>
                  {["Academic term", "Dates", "Availability", "Action"].map((x) => (
                    <th className="p-3" key={x}>
                      {x}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {terms.map((t) => (
                  <tr key={t.id} className="border-t">
                    <td className="p-3">
                      {t.name}
                      <small className="block">
                        {t.academic_year} · {t.code}
                      </small>
                    </td>
                    <td className="p-3">
                      {t.starts_on.slice(0, 10)} – {t.ends_on.slice(0, 10)}
                    </td>
                    <td className="p-3">{t.is_active ? "Active" : "Inactive"}</td>
                    <td className="p-3">
                      <Button
                        disabled={busy}
                        variant="outline"
                        onClick={async () => {
                          setBusy(true);
                          try {
                            await mutate(
                              `/admin/terms/${t.id}`,
                              { is_active: !t.is_active },
                              "PATCH",
                            );
                            setRevision((v) => v + 1);
                            setMessage("Term availability saved.");
                          } catch (e) {
                            setMessage((e as Error).message);
                          } finally {
                            setBusy(false);
                          }
                        }}
                      >
                        {t.is_active ? "Deactivate" : "Set active"}
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      </div>
    </>
  );
}

type Preferences = { system_name: string; institution: string; display_timezone: string };
export function AdminSettings() {
  const [draft, setDraft] = useState<Preferences | null>(null);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  useEffect(() => {
    let active = true;
    api<{ data: Preferences }>("/admin/settings")
      .then((r) => {
        if (active) setDraft(r.data);
      })
      .catch((e) => {
        if (active) setMessage(e.message);
      });
    return () => {
      active = false;
    };
  }, []);
  return (
    <>
      <PageHeader title="System Settings" subtitle="Platform identity and display preferences" />
      {message && (
        <p role="status" className="mb-4">
          {message}
        </p>
      )}
      {draft && (
        <Card>
          <CardTitle>General Settings</CardTitle>
          <form
            className="mt-5 grid max-w-2xl gap-4"
            onSubmit={async (e) => {
              e.preventDefault();
              setBusy(true);
              try {
                await mutate("/admin/settings", draft, "PUT");
                window.dispatchEvent(new Event("internmatch:branding-updated"));
                setMessage("System settings saved.");
              } catch (e) {
                setMessage((e as Error).message);
              } finally {
                setBusy(false);
              }
            }}
          >
            {(
              [
                ["system_name", "System Name"],
                ["institution", "Institution Name"],
              ] as const
            ).map(([key, label]) => (
              <label className="text-sm" key={key}>
                {label}
                <input
                  required
                  maxLength={key === "system_name" ? 80 : 160}
                  className="mt-1 block w-full rounded border p-2"
                  value={draft[key]}
                  onChange={(e) => setDraft({ ...draft, [key]: e.target.value })}
                />
              </label>
            ))}
            <label className="text-sm">
              Display time zone
              <select
                className="mt-1 block w-full rounded border p-2"
                value={draft.display_timezone}
                onChange={(e) => setDraft({ ...draft, display_timezone: e.target.value })}
              >
                <option>Asia/Manila</option>
                <option>UTC</option>
              </select>
            </label>
            <p className="text-sm text-muted-foreground">
              Applies to header notification timestamps. Attendance and certified-hour calculations
              retain their configured institutional time zone.
            </p>
            <Button type="submit" disabled={busy}>
              Save changes
            </Button>
          </form>
        </Card>
      )}
    </>
  );
}
