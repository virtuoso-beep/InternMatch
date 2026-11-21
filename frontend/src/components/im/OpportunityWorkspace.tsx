import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { ExcelImportButton } from "./ExcelImportButton";
import type { PlacementReference } from "@/lib/placement";
import { Plus } from "lucide-react";
import { toast } from "sonner";
import { ConfirmButton } from "./actions";
import { useSessionUser } from "./SessionGuard";
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

type Opportunity = {
  id: number;
  host_establishment_id: number;
  academic_term_id: number;
  title: string;
  description: string;
  tasks: string;
  status: string;
  starts_on: string | null;
  ends_on: string | null;
  programs: { id: number; code: string; pivot: { capacity: number | null } }[];
  competencies: { id: number }[];
  host_establishment: { name: string };
  archived_at?: string | null;
};
type Form = {
  id?: number;
  host_establishment_id: string;
  academic_term_id: string;
  title: string;
  description: string;
  tasks: string;
  status: string;
  starts_on: string;
  ends_on: string;
  programs: { program_id: string; capacity: string }[];
  competency_ids: number[];
};
const blank = (): Form => ({
  host_establishment_id: "",
  academic_term_id: "",
  title: "",
  description: "",
  tasks: "",
  status: "draft",
  starts_on: "",
  ends_on: "",
  programs: [{ program_id: "", capacity: "" }],
  competency_ids: [],
});

export function OpportunityWorkspace() {
  const user = useSessionUser();
  const [showArchived, setShowArchived] = useState(false);
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState("all");
  const [items, setItems] = useState<Opportunity[]>([]);
  const [hosts, setHosts] = useState<{ id: number; name: string }[]>([]);
  const [reference, setReference] = useState<PlacementReference | null>(null);
  const [form, setForm] = useState<Form | null>(null);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [formError, setFormError] = useState<FormError | null>(null);
  const [page, setPage] = useState(1);
  const [last, setLast] = useState(1);
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    Promise.all([
      api<{ data: Opportunity[]; last_page: number }>(
        `/opportunities?page=${page}${showArchived ? "&include_archived=1" : ""}`,
      ),
      api<PlacementReference>("/placement-reference"),
    ])
      .then(([opportunities, refs]) => {
        if (active) {
          setItems(opportunities.data);
          setLast(opportunities.last_page);
          setHosts(refs.hosts);
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
        title="Internship opportunities"
        subtitle="Manage tasks, required competencies, and slots for each eligible program."
        action={
          <Button
            onClick={() => {
              setForm(blank());
              setFormError(null);
              setMessage("");
            }}
          >
            <Plus size={16} aria-hidden="true" />
            Add opportunity
          </Button>
        }
      />
      <ExcelImportButton module="opportunity" onImported={() => setRevision((v) => v + 1)} />
      {user.role !== "supervisor" && (
        <label className="mb-4 flex items-center gap-2 text-sm">
          <input
            type="checkbox"
            checked={showArchived}
            onChange={(event) => {
              setPage(1);
              setShowArchived(event.target.checked);
            }}
          />
          Show archived opportunities
        </label>
      )}
      {message && (
        <p role="status" className="mb-4">
          {message}
        </p>
      )}
      {!reference && !message && <p role="status">Loading opportunities…</p>}
      {form && reference && (
        <FormModal
          open
          size="lg"
          onClose={() => setForm(null)}
          title={
            form.id
              ? `Edit opportunity: ${items.find((item) => item.id === form.id)?.title ?? ""}`
              : "Add opportunity"
          }
          description="Tasks, required competencies and slots for each eligible program."
          submitLabel={form.id ? "Save changes" : "Save opportunity"}
          busy={busy}
          error={formError}
          onSubmit={async () => {
            setBusy(true);
            setFormError(null);
            try {
              await mutate(
                form.id ? `/opportunities/${form.id}` : "/opportunities",
                {
                  ...form,
                  host_establishment_id: Number(form.host_establishment_id),
                  academic_term_id: Number(form.academic_term_id),
                  starts_on: form.starts_on || null,
                  ends_on: form.ends_on || null,
                  programs: form.programs.map((row) => ({
                    program_id: Number(row.program_id),
                    capacity: Number(row.capacity),
                  })),
                },
                form.id ? "PUT" : "POST",
              );
              setForm(null);
              setRevision((value) => value + 1);
              toast.success(form.id ? "Opportunity updated" : "Opportunity added");
            } catch (cause) {
              setFormError(toFormError(cause, "Unable to save the opportunity."));
            } finally {
              setBusy(false);
            }
          }}
        >
          <FormSection title="Opportunity">
            <FormGrid>
              <FormField label="Host establishment" required>
                <select
                  aria-label="Host establishment"
                  name="host_establishment_id"
                  required
                  disabled={Boolean(form.id)}
                  className={inputClass}
                  value={form.host_establishment_id}
                  onChange={(event) =>
                    setForm({ ...form, host_establishment_id: event.target.value })
                  }
                >
                  <option value="">Select host</option>
                  {hosts.map((host) => (
                    <option key={host.id} value={host.id}>
                      {host.name}
                    </option>
                  ))}
                </select>
              </FormField>
              <FormField label="Academic term" required>
                <select
                  aria-label="Academic term"
                  name="academic_term_id"
                  required
                  disabled={Boolean(form.id)}
                  className={inputClass}
                  value={form.academic_term_id}
                  onChange={(event) => setForm({ ...form, academic_term_id: event.target.value })}
                >
                  <option value="">Select term</option>
                  {reference.terms.map((term) => (
                    <option key={term.id} value={term.id}>
                      {term.code}
                    </option>
                  ))}
                </select>
              </FormField>
            </FormGrid>
            <FormField label="Position title" required>
              <input
                required
                name="title"
                maxLength={255}
                className={inputClass}
                value={form.title}
                onChange={(event) => setForm({ ...form, title: event.target.value })}
              />
            </FormField>
            <FormField label="Description" required>
              <textarea
                required
                name="description"
                maxLength={10000}
                rows={4}
                className={inputClass}
                value={form.description}
                onChange={(event) => setForm({ ...form, description: event.target.value })}
              />
            </FormField>
            <FormField label="Tasks and responsibilities" required>
              <textarea
                required
                name="tasks"
                maxLength={10000}
                rows={4}
                className={inputClass}
                value={form.tasks}
                onChange={(event) => setForm({ ...form, tasks: event.target.value })}
              />
            </FormField>
          </FormSection>
          <FormSection title="Schedule and status">
            <div className="grid gap-4 sm:grid-cols-3">
              <FormField label="Start date">
                <input
                  type="date"
                  name="starts_on"
                  className={inputClass}
                  value={form.starts_on}
                  onChange={(event) => setForm({ ...form, starts_on: event.target.value })}
                />
              </FormField>
              <FormField label="End date">
                <input
                  type="date"
                  name="ends_on"
                  className={inputClass}
                  value={form.ends_on}
                  onChange={(event) => setForm({ ...form, ends_on: event.target.value })}
                />
              </FormField>
              <FormField label="Status">
                <select
                  aria-label="Status"
                  name="status"
                  className={inputClass}
                  value={form.status}
                  onChange={(event) => setForm({ ...form, status: event.target.value })}
                >
                  <option value="draft">Draft</option>
                  <option value="published">Published</option>
                  <option value="closed">Closed</option>
                </select>
              </FormField>
            </div>
          </FormSection>
          <FormSection
            title="Eligible programs and capacity"
            description="Configure host capacity first. Each program has its own slot limit."
          >
            <div data-field="programs" className="space-y-2">
              {form.programs.map((row, index) => (
                <div key={index} className="flex flex-wrap items-center gap-3">
                  <select
                    aria-label={`Eligible program ${index + 1}`}
                    name={`programs.${index}.program_id`}
                    required
                    className={`${inputClass} w-auto min-w-[10rem] flex-1`}
                    value={row.program_id}
                    onChange={(event) =>
                      setForm({
                        ...form,
                        programs: form.programs.map((item, i) =>
                          i === index ? { ...item, program_id: event.target.value } : item,
                        ),
                      })
                    }
                  >
                    <option value="">Select program</option>
                    {reference.programs.map((program) => (
                      <option key={program.id} value={program.id}>
                        {program.code}
                      </option>
                    ))}
                  </select>
                  <input
                    aria-label={`Program ${index + 1} slots`}
                    name={`programs.${index}.capacity`}
                    required
                    type="number"
                    min="0"
                    max="100000"
                    className={`${inputClass} w-28`}
                    value={row.capacity}
                    onChange={(event) =>
                      setForm({
                        ...form,
                        programs: form.programs.map((item, i) =>
                          i === index ? { ...item, capacity: event.target.value } : item,
                        ),
                      })
                    }
                  />
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={form.programs.length === 1}
                    onClick={() =>
                      setForm({ ...form, programs: form.programs.filter((_, i) => i !== index) })
                    }
                  >
                    Remove program
                  </Button>
                </div>
              ))}
            </div>
            <Button
              variant="outline"
              size="sm"
              onClick={() =>
                setForm({
                  ...form,
                  programs: [...form.programs, { program_id: "", capacity: "" }],
                })
              }
            >
              <Plus size={14} aria-hidden="true" />
              Add program
            </Button>
          </FormSection>
          <FormSection title="Required competencies">
            <div
              data-field="competency_ids"
              className="grid max-h-56 gap-2 overflow-y-auto sm:grid-cols-2"
            >
              {reference.competencies.map((item) => (
                <label key={item.id} className="flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={form.competency_ids.includes(item.id)}
                    onChange={(event) =>
                      setForm({
                        ...form,
                        competency_ids: event.target.checked
                          ? [...form.competency_ids, item.id]
                          : form.competency_ids.filter((id) => id !== item.id),
                      })
                    }
                  />
                  {item.name}
                </label>
              ))}
            </div>
          </FormSection>
        </FormModal>
      )}
      {reference && items.length === 0 && (
        <Card>No opportunities are recorded in your access scope.</Card>
      )}
      <div className="mb-5 flex flex-wrap gap-3">
        <label className="text-sm">
          Search this page
          <input
            type="search"
            className="ml-2 rounded border p-2"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
          />
        </label>
        <label className="text-sm">
          Posting status
          <select
            className="ml-2 rounded border p-2"
            value={filter}
            onChange={(event) => setFilter(event.target.value)}
          >
            {["all", "draft", "published", "closed"].map((status) => (
              <option key={status} value={status}>
                {status === "all" ? "All statuses" : status[0]!.toUpperCase() + status.slice(1)}
              </option>
            ))}
          </select>
        </label>
        <Button
          variant="outline"
          onClick={() => {
            setSearch("");
            setFilter("all");
          }}
        >
          Clear filters
        </Button>
      </div>
      <p className="mb-3 text-xs text-muted-foreground">
        Filters apply to this page. Slot figures are configured program capacities, not remaining
        vacancies.
      </p>
      {items.length > 0 &&
        !items.some(
          (item) =>
            (filter === "all" || item.status === filter) &&
            `${item.title} ${item.host_establishment.name}`
              .toLowerCase()
              .includes(search.toLowerCase()),
        ) && <Card>No opportunities match these filters.</Card>}
      <div className="grid gap-5 lg:grid-cols-2">
        {items
          .filter(
            (item) =>
              (filter === "all" || item.status === filter) &&
              `${item.title} ${item.host_establishment.name}`
                .toLowerCase()
                .includes(search.toLowerCase()),
          )
          .map((item) => (
            <Card key={item.id}>
              <div className="flex items-start justify-between gap-3">
                <CardTitle>{item.title}</CardTitle>
                <div className="flex gap-2">
                  {item.archived_at && <Pill tone="muted">Archived</Pill>}
                  <Pill tone={statusTone(item.status)}>{item.status}</Pill>
                </div>
              </div>
              <p className="text-sm text-muted-foreground">{item.host_establishment.name}</p>
              <div className="my-3 flex flex-wrap gap-2">
                {item.competencies.map((skill) => (
                  <Pill key={skill.id}>
                    {reference?.competencies.find((entry) => entry.id === skill.id)?.name ??
                      "Competency"}
                  </Pill>
                ))}
              </div>
              <p>{item.description}</p>
              <p>
                {item.programs
                  .map(
                    (program) =>
                      `${program.code}: ${program.pivot.capacity ?? "unconfirmed"} ${program.pivot.capacity === 1 ? "slot" : "slots"}`,
                  )
                  .join(" · ")}
              </p>
              <div className="mt-3 flex flex-wrap gap-2">
                {!item.archived_at && (
                  <Button
                    variant="outline"
                    onClick={() =>
                      setForm({
                        id: item.id,
                        host_establishment_id: String(item.host_establishment_id),
                        academic_term_id: String(item.academic_term_id),
                        title: item.title,
                        description: item.description,
                        tasks: item.tasks ?? "",
                        status: item.status,
                        starts_on: item.starts_on?.slice(0, 10) ?? "",
                        ends_on: item.ends_on?.slice(0, 10) ?? "",
                        programs: item.programs.map((program) => ({
                          program_id: String(program.id),
                          capacity: program.pivot.capacity?.toString() ?? "",
                        })),
                        competency_ids: item.competencies.map((competency) => competency.id),
                      })
                    }
                  >
                    Edit opportunity
                  </Button>
                )}
                {user.role !== "supervisor" && !item.archived_at && (
                  <ConfirmButton
                    tone="danger"
                    title={`Archive ${item.title}?`}
                    description={`The opportunity at ${item.host_establishment.name} is hidden from lists and no longer offered for recommendations or placements. Its history is kept, and you can restore it later. Opportunities with pending, approved or active placements cannot be archived.`}
                    confirmLabel="Archive"
                    success="Opportunity archived"
                    onConfirm={() => mutate(`/opportunities/${item.id}/archive`, {})}
                    onDone={() => setRevision((value) => value + 1)}
                  >
                    Archive
                  </ConfirmButton>
                )}
                {user.role !== "supervisor" && item.archived_at && (
                  <ConfirmButton
                    title={`Restore ${item.title}?`}
                    description="The opportunity returns to the lists. It is offered to students again only while it is published and eligible."
                    confirmLabel="Restore"
                    success="Opportunity restored"
                    onConfirm={() => mutate(`/opportunities/${item.id}/restore`, {})}
                    onDone={() => setRevision((value) => value + 1)}
                  >
                    Restore
                  </ConfirmButton>
                )}
                {user.role !== "supervisor" && (
                  <ConfirmButton
                    tone="danger"
                    variant="ghost"
                    title={`Delete ${item.title}?`}
                    description="Only an opportunity that has never been recommended, received interest or been used by a placement can be deleted. This cannot be undone. Anything with history must be archived instead."
                    confirmLabel="Delete"
                    success="Opportunity deleted"
                    onConfirm={() => mutate(`/opportunities/${item.id}`, undefined, "DELETE")}
                    onDone={() => setRevision((value) => value + 1)}
                  >
                    Delete
                  </ConfirmButton>
                )}
              </div>
            </Card>
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
