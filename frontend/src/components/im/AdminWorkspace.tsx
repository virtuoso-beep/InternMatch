import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Plus } from "lucide-react";
import { toast } from "sonner";
import { ConfirmButton } from "./actions";
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
import { inputClass, toFormError, type FormError } from "./formUtils";
import { RankingStatus } from "./RankingStatus";

type Term = {
  id: number;
  code: string;
  name: string;
  academic_year: string;
  starts_on: string;
  ends_on: string;
  is_active: boolean;
  archived_at?: string | null;
};
type Overview = {
  total_users: number;
  users_by_role: { role: string | null; total: number }[];
  programs: { id: number; code: string; name: string; cluster: string; is_active: boolean }[];
  programs_without_coordinator?: { id: number; code: string }[];
  terms: Term[];
  chat_enabled: boolean;
  recent_activity: { id: number; action: string; created_at: string }[];
};
const linkClass =
  "inline-block rounded-lg border border-brand/30 px-4 py-2 text-sm font-semibold text-brand hover:bg-brand-soft";
export function AdminLink({ section, children }: { section: string; children: React.ReactNode }) {
  return (
    <a className={linkClass} href={`/admin/${section}`}>
      {children}
    </a>
  );
}

export function AdminTerms() {
  const [creating, setCreating] = useState(false);
  const [terms, setTerms] = useState<Term[]>([]);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [revision, setRevision] = useState(0);
  const [formError, setFormError] = useState<FormError | null>(null);
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
        <Button
          onClick={() => {
            setFormError(null);
            setCreating(true);
          }}
        >
          <Plus size={16} aria-hidden="true" />
          Add term
        </Button>
      </div>
      <FormModal
        open={creating}
        onClose={() => {
          setCreating(false);
          setDraft(empty);
          setFormError(null);
        }}
        title="Add academic term"
        description="Set the term's code, name, academic year and dates."
        submitLabel="Add term"
        busy={busy}
        error={formError}
        onSubmit={async () => {
          setBusy(true);
          setFormError(null);
          try {
            await mutate("/academic-terms", draft);
            setDraft(empty);
            setCreating(false);
            setRevision((v) => v + 1);
            toast.success("Academic term added");
          } catch (cause) {
            setFormError(toFormError(cause, "Unable to add the term."));
          } finally {
            setBusy(false);
          }
        }}
      >
        <FormSection title="Term details">
          <FormGrid>
            {(
              [
                ["code", "Term code"],
                ["name", "Term name"],
                ["academic_year", "Academic year"],
                ["starts_on", "Start date"],
                ["ends_on", "End date"],
              ] as const
            ).map(([key, label]) => (
              <FormField key={key} label={label} required>
                <input
                  required
                  name={key}
                  type={key.endsWith("_on") ? "date" : "text"}
                  value={draft[key]}
                  onChange={(e) => setDraft({ ...draft, [key]: e.target.value })}
                  className={inputClass}
                />
              </FormField>
            ))}
          </FormGrid>
        </FormSection>
      </FormModal>
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
                    <td className="p-3">
                      <Pill tone={t.archived_at ? "muted" : t.is_active ? "success" : "muted"}>
                        {t.archived_at ? "Archived" : t.is_active ? "Active" : "Inactive"}
                      </Pill>
                    </td>
                    <td className="p-3">
                      <div className="flex flex-wrap gap-2">
                        {!t.archived_at && (
                          <ConfirmButton
                            size="sm"
                            tone={t.is_active ? "danger" : "primary"}
                            title={t.is_active ? `Deactivate ${t.name}?` : `Set ${t.name} active?`}
                            description={
                              t.is_active
                                ? "Students in this term are no longer offered opportunities or recommendations until it is active again. Records are kept."
                                : "The term becomes available for enrollment, opportunities and recommendations."
                            }
                            confirmLabel={t.is_active ? "Deactivate" : "Set active"}
                            success={t.is_active ? "Term deactivated" : "Term set active"}
                            onConfirm={() =>
                              mutate(`/admin/terms/${t.id}`, { is_active: !t.is_active }, "PATCH")
                            }
                            onDone={() => setRevision((v) => v + 1)}
                          >
                            {t.is_active ? "Deactivate" : "Set active"}
                          </ConfirmButton>
                        )}
                        {!t.archived_at && !t.is_active && (
                          <ConfirmButton
                            size="sm"
                            tone="danger"
                            title={`Archive ${t.name}?`}
                            description="The term is hidden from term choices and cannot be activated until it is restored. Its enrollments and history are kept."
                            confirmLabel="Archive"
                            success="Term archived"
                            onConfirm={() => mutate(`/academic-terms/${t.id}/archive`, {})}
                            onDone={() => setRevision((v) => v + 1)}
                          >
                            Archive
                          </ConfirmButton>
                        )}
                        {t.archived_at && (
                          <ConfirmButton
                            size="sm"
                            title={`Restore ${t.name}?`}
                            description="The term returns to term choices as inactive. Set it active separately when needed."
                            confirmLabel="Restore"
                            success="Term restored"
                            onConfirm={() => mutate(`/academic-terms/${t.id}/restore`, {})}
                            onDone={() => setRevision((v) => v + 1)}
                          >
                            Restore
                          </ConfirmButton>
                        )}
                        {!t.is_active && (
                          <ConfirmButton
                            size="sm"
                            variant="ghost"
                            tone="danger"
                            title={`Delete ${t.name}?`}
                            description="Only a term with no programs, opportunities or capacities can be deleted. This cannot be undone. A term with history must be archived instead."
                            confirmLabel="Delete"
                            success="Term deleted"
                            onConfirm={() => mutate(`/academic-terms/${t.id}`, undefined, "DELETE")}
                            onDone={() => setRevision((v) => v + 1)}
                          >
                            Delete
                          </ConfirmButton>
                        )}
                      </div>
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
      <RankingStatus />
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
