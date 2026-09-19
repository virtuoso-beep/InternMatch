import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, CardTitle, PageHeader } from "./ui";
import { AcademicSetup } from "./AcademicSetup";

type Program = {
  id: number; code: string; name: string; cluster: string | null;
  required_ojt_hours: number | null; internship_term: string | null; competencies_count: number;
};

export function ProgramRegistry() {
  const [programs, setPrograms] = useState<Program[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  useEffect(() => {
    let active = true;
    api<{ data: Program[] }>("/programs").then(({ data }) => { if (active) setPrograms(data); })
      .catch((cause: Error) => { if (active) setError(cause.message); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);
  return <>
    <PageHeader title="Programs & curriculum" subtitle="Enter department-confirmed requirements. Unconfirmed hours remain unset." />
    <AcademicSetup />
    {error && <p role="alert">{error}</p>}
    {loading && <p role="status">Loading programs…</p>}
    {!loading && !error && programs.length === 0 && <p>No programs have been configured.</p>}
    <div className="grid gap-4 lg:grid-cols-3">
      {programs.map(program => <ProgramForm key={program.id} program={program} onSaved={saved => setPrograms(current => current.map(p => p.id === saved.id ? saved : p))} />)}
    </div>
  </>;
}

function ProgramForm({ program, onSaved }: { program: Program; onSaved: (program: Program) => void }) {
  const [hours, setHours] = useState(program.required_ojt_hours?.toString() ?? "");
  const [term, setTerm] = useState(program.internship_term ?? "");
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState("");
  return <Card>
    <CardTitle>{program.code}</CardTitle>
    <p>{program.name}</p><p className="text-sm text-muted-foreground">{program.cluster} · {program.competencies_count} starter competencies</p>
    <form className="mt-4 space-y-3" onSubmit={async event => {
      event.preventDefault(); setSaving(true); setMessage("");
      try {
        const { data } = await mutate<{ data: Program }>(`/programs/${program.id}`, {
          required_ojt_hours: hours === "" ? null : Number(hours), internship_term: term.trim() || null,
        }, "PATCH");
        onSaved(data); setMessage("Saved to the program record.");
      } catch (cause) { setMessage(cause instanceof Error ? cause.message : "Unable to save."); }
      finally { setSaving(false); }
    }}>
      <label className="block text-sm">Confirmed required hours
        <input aria-label={`${program.code} confirmed required hours`} className="mt-1 w-full rounded border p-2" type="number" min="1" max="10000" step="1" placeholder="Awaiting confirmation" value={hours} onChange={event => setHours(event.target.value)} />
      </label>
      <label className="block text-sm">Internship year / semester
        <input aria-label={`${program.code} internship term`} className="mt-1 w-full rounded border p-2" maxLength={255} value={term} onChange={event => setTerm(event.target.value)} />
      </label>
      <Button type="submit" disabled={saving}>{saving ? "Saving…" : "Save requirements"}</Button>
      <p role="status" className="text-sm">{message || (program.required_ojt_hours === null ? "Required hours are not confirmed." : "")}</p>
    </form>
  </Card>;
}
