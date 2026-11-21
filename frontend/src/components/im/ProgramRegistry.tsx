import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { toast } from "sonner";
import { Button, Card, CardTitle, FormField, FormModal, PageHeader } from "./ui";
import { inputClass, toFormError, type FormError } from "./formUtils";
import { AcademicSetup } from "./AcademicSetup";

type Program = {
  id: number;
  code: string;
  name: string;
  cluster: string | null;
  required_ojt_hours: number | null;
  internship_term: string | null;
  competencies_count: number;
};

export function ProgramRegistry() {
  const [programs, setPrograms] = useState<Program[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [revision, setRevision] = useState(0);
  const [search, setSearch] = useState("");
  const [confirmation, setConfirmation] = useState("");
  useEffect(() => {
    let active = true;
    setLoading(true);
    setError(null);
    api<{ data: Program[] }>("/programs")
      .then(({ data }) => {
        if (active) setPrograms(data);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [revision]);
  const visible = programs.filter(
    (program) =>
      `${program.code} ${program.name} ${program.cluster ?? ""}`
        .toLowerCase()
        .includes(search.trim().toLowerCase()) &&
      (!confirmation ||
        (confirmation === "confirmed"
          ? program.required_ojt_hours != null
          : program.required_ojt_hours == null)),
  );
  return (
    <>
      <PageHeader
        title="Programs & curriculum"
        subtitle="Enter department-confirmed requirements. Unconfirmed hours remain unset."
      />
      <div className="mb-5 flex flex-wrap items-end gap-3">
        <label className="text-sm">
          Find program
          <input
            className="mt-1 block rounded border p-2"
            placeholder="Code, name or cluster"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
          />
        </label>
        <label className="text-sm">
          Required hours
          <select
            aria-label="Filter required hours"
            className="mt-1 block rounded border p-2"
            value={confirmation}
            onChange={(event) => setConfirmation(event.target.value)}
          >
            <option value="">All programs</option>
            <option value="confirmed">Hours recorded</option>
            <option value="unconfirmed">Awaiting confirmation</option>
          </select>
        </label>
        <Button
          variant="outline"
          onClick={() => {
            setSearch("");
            setConfirmation("");
          }}
        >
          Clear filters
        </Button>
        <Button
          variant="outline"
          disabled={loading}
          onClick={() => setRevision((value) => value + 1)}
        >
          Refresh programs
        </Button>
      </div>
      {error && <p role="alert">{error}</p>}
      {loading && <p role="status">Loading programs…</p>}
      {!loading && !error && programs.length === 0 && <p>No programs have been configured.</p>}
      {!loading && !error && (
        <p role="status" className="mb-3 text-sm text-muted-foreground">
          {visible.length
            ? `Showing ${visible.length} of ${programs.length} programs.`
            : "No programs match these filters."}
        </p>
      )}
      <div className="grid gap-4 lg:grid-cols-3">
        {!loading &&
          !error &&
          visible.map((program) => (
            <ProgramForm
              key={`${program.id}:${revision}`}
              program={program}
              onSaved={(saved) =>
                setPrograms((current) => current.map((p) => (p.id === saved.id ? saved : p)))
              }
            />
          ))}
      </div>
      <details className="mt-6 rounded border bg-card p-4">
        <summary className="cursor-pointer font-semibold">Academic setup and enrollment</summary>
        <p className="my-3 text-sm text-muted-foreground">
          Configure requirements, create academic terms and enroll students.
        </p>
        <AcademicSetup />
      </details>
    </>
  );
}

function ProgramForm({
  program,
  onSaved,
}: {
  program: Program;
  onSaved: (program: Program) => void;
}) {
  const [hours, setHours] = useState(program.required_ojt_hours?.toString() ?? "");
  const [term, setTerm] = useState(program.internship_term ?? "");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  const [editing, setEditing] = useState(false);
  return (
    <Card>
      <CardTitle>{program.code}</CardTitle>
      <p className="mt-3 border-t pt-3 text-muted-foreground">{program.name}</p>
      <dl className="my-5 grid grid-cols-2 gap-4 text-sm">
        <div>
          <dt className="text-xs text-muted-foreground">Required hours</dt>
          <dd className="mt-1 font-semibold">
            {program.required_ojt_hours ?? "Awaiting confirmation"}
          </dd>
        </div>
        <div>
          <dt className="text-xs text-muted-foreground">Competencies</dt>
          <dd className="mt-1 font-semibold">{program.competencies_count} mapped</dd>
        </div>
        <div>
          <dt className="text-xs text-muted-foreground">Cluster</dt>
          <dd className="mt-1">{program.cluster || "Not recorded"}</dd>
        </div>
        <div>
          <dt className="text-xs text-muted-foreground">Internship term</dt>
          <dd className="mt-1">{program.internship_term || "Not recorded"}</dd>
        </div>
      </dl>
      <Button
        variant="outline"
        onClick={() => {
          setHours(program.required_ojt_hours?.toString() ?? "");
          setTerm(program.internship_term ?? "");
          setError(null);
          setEditing(true);
        }}
      >
        Edit requirements <span className="sr-only">{program.code}</span>
      </Button>
      {program.required_ojt_hours === null && (
        <p role="status" className="mt-3 text-sm">
          Required hours are not confirmed.
        </p>
      )}
      <FormModal
        open={editing}
        onClose={() => setEditing(false)}
        title={`Edit requirements: ${program.code}`}
        description={program.name}
        submitLabel="Save requirements"
        busy={saving}
        error={error}
        onSubmit={async () => {
          setSaving(true);
          setError(null);
          try {
            const { data } = await mutate<{ data: Program }>(
              `/programs/${program.id}`,
              {
                required_ojt_hours: hours === "" ? null : Number(hours),
                internship_term: term.trim() || null,
              },
              "PATCH",
            );
            onSaved(data);
            setEditing(false);
            toast.success("Program requirements saved");
          } catch (cause) {
            setError(toFormError(cause, "Unable to save."));
          } finally {
            setSaving(false);
          }
        }}
      >
        <FormField
          label="Confirmed required hours"
          helper="Leave empty while the hours are awaiting confirmation."
        >
          <input
            name="required_ojt_hours"
            className={inputClass}
            type="number"
            min="1"
            max="10000"
            step="1"
            placeholder="Awaiting confirmation"
            value={hours}
            onChange={(event) => setHours(event.target.value)}
          />
        </FormField>
        <FormField label="Internship year or semester">
          <input
            name="internship_term"
            className={inputClass}
            maxLength={255}
            value={term}
            onChange={(event) => setTerm(event.target.value)}
          />
        </FormField>
      </FormModal>
    </Card>
  );
}
