import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, CardTitle, PageHeader } from "./ui";
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
      <details className="mt-6 rounded border bg-white p-4">
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
  const [message, setMessage] = useState("");
  const [editing, setEditing] = useState(false);
  return (
    <Card>
      <CardTitle>{program.code}</CardTitle>
      <p className="mt-3 border-t pt-3 text-muted-foreground">{program.name}</p>
      <dl className="my-5 grid grid-cols-2 gap-4 text-sm">
        <div>
          <dt className="text-xs uppercase text-muted-foreground">Required hours</dt>
          <dd className="mt-1 font-semibold">
            {program.required_ojt_hours ?? "Awaiting confirmation"}
          </dd>
        </div>
        <div>
          <dt className="text-xs uppercase text-muted-foreground">Competencies</dt>
          <dd className="mt-1 font-semibold">{program.competencies_count} mapped</dd>
        </div>
        <div>
          <dt className="text-xs uppercase text-muted-foreground">Cluster</dt>
          <dd className="mt-1">{program.cluster || "Not recorded"}</dd>
        </div>
        <div>
          <dt className="text-xs uppercase text-muted-foreground">Internship term</dt>
          <dd className="mt-1">{program.internship_term || "Not recorded"}</dd>
        </div>
      </dl>
      {!editing && (
        <Button
          variant="outline"
          onClick={() => {
            setHours(program.required_ojt_hours?.toString() ?? "");
            setTerm(program.internship_term ?? "");
            setMessage("");
            setEditing(true);
          }}
        >
          Edit requirements <span className="sr-only">{program.code}</span>
        </Button>
      )}
      {editing && (
        <form
          className="mt-4 space-y-3"
          onSubmit={async (event) => {
            event.preventDefault();
            setSaving(true);
            setMessage("");
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
              setMessage("Saved to the program record.");
              setEditing(false);
            } catch (cause) {
              setMessage(cause instanceof Error ? cause.message : "Unable to save.");
            } finally {
              setSaving(false);
            }
          }}
        >
          <fieldset disabled={saving} className="space-y-3">
            <label className="block text-sm">
              Confirmed required hours
              <input
                aria-label={`${program.code} confirmed required hours`}
                className="mt-1 w-full rounded border p-2"
                type="number"
                min="1"
                max="10000"
                step="1"
                placeholder="Awaiting confirmation"
                value={hours}
                onChange={(event) => setHours(event.target.value)}
              />
            </label>
            <label className="block text-sm">
              Internship year / semester
              <input
                aria-label={`${program.code} internship term`}
                className="mt-1 w-full rounded border p-2"
                maxLength={255}
                value={term}
                onChange={(event) => setTerm(event.target.value)}
              />
            </label>
            <Button type="submit" disabled={saving}>
              {saving ? "Saving…" : "Save requirements"}
            </Button>
            <Button
              variant="outline"
              disabled={saving}
              onClick={() => {
                setEditing(false);
                setMessage("");
              }}
            >
              Cancel
            </Button>
          </fieldset>
        </form>
      )}
      <p role="status" className="mt-3 text-sm">
        {message ||
          (program.required_ojt_hours === null ? "Required hours are not confirmed." : "")}
      </p>
    </Card>
  );
}
