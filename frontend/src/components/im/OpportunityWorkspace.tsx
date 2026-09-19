import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import type { PlacementReference } from "@/lib/placement";
import { Button, Card, CardTitle, PageHeader } from "./ui";

type Opportunity = { id: number; host_establishment_id: number; academic_term_id: number; title: string; description: string; tasks: string; status: string; starts_on: string | null; ends_on: string | null; programs: { id: number; code: string; pivot: { capacity: number | null } }[]; competencies: { id: number }[]; host_establishment: { name: string } };
type Form = { id?: number; host_establishment_id: string; academic_term_id: string; title: string; description: string; tasks: string; status: string; starts_on: string; ends_on: string; programs: { program_id: string; capacity: string }[]; competency_ids: number[] };
const blank = (): Form => ({ host_establishment_id: "", academic_term_id: "", title: "", description: "", tasks: "", status: "draft", starts_on: "", ends_on: "", programs: [{ program_id: "", capacity: "" }], competency_ids: [] });

export function OpportunityWorkspace() {
  const [items, setItems] = useState<Opportunity[]>([]);
  const [hosts, setHosts] = useState<{ id: number; name: string }[]>([]);
  const [reference, setReference] = useState<PlacementReference | null>(null);
  const [form, setForm] = useState<Form | null>(null);
  const [message, setMessage] = useState(""); const [busy, setBusy] = useState(false);
  const [page, setPage] = useState(1); const [last, setLast] = useState(1); const [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    Promise.all([api<{ data: Opportunity[]; last_page: number }>(`/opportunities?page=${page}`), api<PlacementReference>("/placement-reference")])
      .then(([opportunities, refs]) => { if (active) { setItems(opportunities.data); setLast(opportunities.last_page); setHosts(refs.hosts); setReference(refs); } })
      .catch((cause: Error) => { if (active) setMessage(cause.message); });
    return () => { active = false; };
  }, [page, revision]);
  return <>
    <PageHeader title="Internship opportunities" subtitle="Manage tasks, required competencies, and slots for each eligible program." action={<Button onClick={() => { setForm(blank()); setMessage(""); }}>Create opportunity</Button>} />
    {message && <p role="status" className="mb-4">{message}</p>}
    {!reference && !message && <p role="status">Loading opportunities…</p>}
    {form && reference && <Card className="mb-5"><CardTitle>{form.id ? "Edit opportunity" : "New opportunity"}</CardTitle>
      <form className="space-y-3" onSubmit={async event => {
        event.preventDefault(); setBusy(true); setMessage("");
        try {
          await mutate(form.id ? `/opportunities/${form.id}` : "/opportunities", {
            ...form, host_establishment_id: Number(form.host_establishment_id), academic_term_id: Number(form.academic_term_id),
            starts_on: form.starts_on || null, ends_on: form.ends_on || null,
            programs: form.programs.map(row => ({ program_id: Number(row.program_id), capacity: Number(row.capacity) })),
          }, form.id ? "PUT" : "POST");
          setForm(null); setRevision(value => value + 1); setMessage("Opportunity saved.");
        } catch (cause) { setMessage(cause instanceof Error ? cause.message : "Unable to save opportunity."); }
        finally { setBusy(false); }
      }}>
        <div className="grid gap-3 md:grid-cols-2">
          <label className="text-sm">Host establishment<select required disabled={Boolean(form.id)} className="mt-1 block w-full rounded border p-2" value={form.host_establishment_id} onChange={event => setForm({ ...form, host_establishment_id: event.target.value })}><option value="">Select host</option>{hosts.map(host => <option key={host.id} value={host.id}>{host.name}</option>)}</select></label>
          <label className="text-sm">Academic term<select required disabled={Boolean(form.id)} className="mt-1 block w-full rounded border p-2" value={form.academic_term_id} onChange={event => setForm({ ...form, academic_term_id: event.target.value })}><option value="">Select term</option>{reference.terms.map(term => <option key={term.id} value={term.id}>{term.code}</option>)}</select></label>
        </div>
        <label className="block text-sm">Position title<input required maxLength={255} className="mt-1 block w-full rounded border p-2" value={form.title} onChange={event => setForm({ ...form, title: event.target.value })} /></label>
        <label className="block text-sm">Description<textarea required maxLength={10000} className="mt-1 block w-full rounded border p-2" value={form.description} onChange={event => setForm({ ...form, description: event.target.value })} /></label>
        <label className="block text-sm">Tasks and responsibilities<textarea required maxLength={10000} className="mt-1 block w-full rounded border p-2" value={form.tasks} onChange={event => setForm({ ...form, tasks: event.target.value })} /></label>
        <div className="grid gap-3 md:grid-cols-3"><label className="text-sm">Start date<input type="date" className="mt-1 block rounded border p-2" value={form.starts_on} onChange={event => setForm({ ...form, starts_on: event.target.value })} /></label><label className="text-sm">End date<input type="date" className="mt-1 block rounded border p-2" value={form.ends_on} onChange={event => setForm({ ...form, ends_on: event.target.value })} /></label><label className="text-sm">Status<select className="mt-1 block rounded border p-2" value={form.status} onChange={event => setForm({ ...form, status: event.target.value })}><option value="draft">Draft</option><option value="published">Published</option><option value="closed">Closed</option></select></label></div>
        <fieldset className="rounded border p-3"><legend>Eligible programs and capacity</legend><p className="mb-3 text-sm">Configure host capacity first. Each program has its own slot limit.</p>
          {form.programs.map((row, index) => <div key={index} className="mb-2 flex flex-wrap gap-3">
            <select aria-label={`Eligible program ${index + 1}`} required className="rounded border p-2" value={row.program_id} onChange={event => setForm({ ...form, programs: form.programs.map((item, i) => i === index ? { ...item, program_id: event.target.value } : item) })}><option value="">Select program</option>{reference.programs.map(program => <option key={program.id} value={program.id}>{program.code}</option>)}</select>
            <input aria-label={`Program ${index + 1} slots`} required type="number" min="0" max="100000" className="w-28 rounded border p-2" value={row.capacity} onChange={event => setForm({ ...form, programs: form.programs.map((item, i) => i === index ? { ...item, capacity: event.target.value } : item) })} />
            <Button variant="outline" disabled={form.programs.length === 1} onClick={() => setForm({ ...form, programs: form.programs.filter((_, i) => i !== index) })}>Remove program</Button>
          </div>)}<Button variant="outline" onClick={() => setForm({ ...form, programs: [...form.programs, { program_id: "", capacity: "" }] })}>Add program</Button>
        </fieldset>
        <fieldset className="rounded border p-3"><legend>Required competencies</legend><div className="grid max-h-48 gap-2 overflow-y-auto md:grid-cols-2">{reference.competencies.map(item => <label key={item.id} className="text-sm"><input type="checkbox" checked={form.competency_ids.includes(item.id)} onChange={event => setForm({ ...form, competency_ids: event.target.checked ? [...form.competency_ids, item.id] : form.competency_ids.filter(id => id !== item.id) })} /> {item.name}</label>)}</div></fieldset>
        <div className="flex gap-3"><Button type="submit" disabled={busy}>{busy ? "Saving…" : "Save opportunity"}</Button><Button variant="outline" onClick={() => setForm(null)}>Cancel</Button></div>
      </form>
    </Card>}
    {reference && items.length === 0 && <Card>No opportunities are recorded in your access scope.</Card>}
    <div className="space-y-4">{items.map(item => <Card key={item.id}><CardTitle>{item.title}</CardTitle><p>{item.host_establishment.name} · {item.status}</p><p>{item.description}</p><p>{item.programs.map(program => `${program.code}: ${program.pivot.capacity ?? "unconfirmed"} slots`).join(" · ")}</p><Button variant="outline" onClick={() => setForm({ id: item.id, host_establishment_id: String(item.host_establishment_id), academic_term_id: String(item.academic_term_id), title: item.title, description: item.description, tasks: item.tasks ?? "", status: item.status, starts_on: item.starts_on?.slice(0, 10) ?? "", ends_on: item.ends_on?.slice(0, 10) ?? "", programs: item.programs.map(program => ({ program_id: String(program.id), capacity: program.pivot.capacity?.toString() ?? "" })), competency_ids: item.competencies.map(competency => competency.id) })}>Edit opportunity</Button></Card>)}</div>
    {last > 1 && <div className="mt-4 flex gap-3"><Button disabled={page === 1} onClick={() => setPage(page - 1)}>Previous</Button><span>{page} / {last}</span><Button disabled={page === last} onClick={() => setPage(page + 1)}>Next</Button></div>}
  </>;
}
