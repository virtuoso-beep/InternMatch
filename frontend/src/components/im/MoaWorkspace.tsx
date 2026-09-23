import { useEffect, useState } from "react";
import { api, mutate, upload } from "@/lib/api";
import type { PlacementReference } from "@/lib/placement";
import { useSessionUser } from "./SessionGuard";
import { Button, Card, CardTitle, PageHeader } from "./ui";

type Moa = { id: number; host_establishment_id: number; host_establishment: { name: string }; reference_number: string; status: string; effective_on: string | null; expires_on: string | null; is_institution_wide: boolean; programs: { id: number; code: string }[]; notes: string | null; document: { original_name: string } | null };
export function MoaWorkspace() {
  const [data, setData] = useState<{ data: Moa[]; last_page: number } | null>(null); const [reference, setReference] = useState<PlacementReference | null>(null);
  const [page, setPage] = useState(1); const [revision, setRevision] = useState(0); const [message, setMessage] = useState(""); const [editing, setEditing] = useState<Moa | null | undefined>(undefined);
  const user = useSessionUser();
  useEffect(() => { let active = true; Promise.all([api<{ data: Moa[]; last_page: number }>(`/moas?page=${page}`), api<PlacementReference>("/placement-reference")]).then(([records, refs]) => { if (active) { setData(records); setReference(refs); } }).catch((cause: Error) => { if (active) setMessage(cause.message); }); return () => { active = false; }; }, [page, revision]);
  return <><PageHeader title="Memoranda of agreement" subtitle="Register executed agreements and their program coverage. Recording an agreement here does not sign it." />
    {message && <p role="alert">{message}</p>}
    <Button onClick={() => setEditing(null)}>Register agreement</Button>
    {editing !== undefined && reference && <MoaEditor key={editing?.id ?? "new"} record={editing} reference={reference} done={() => { setEditing(undefined); setRevision(value => value + 1); }} />}
    {data?.data.length === 0 && <p className="mt-4">No agreements are available in your scope.</p>}
    <div className="mt-5 space-y-4">{data?.data.map(item => <Card key={item.id}><CardTitle>{item.reference_number} · {item.host_establishment.name}</CardTitle><p>{item.status} · {item.effective_on?.slice(0, 10) ?? "Start unset"} to {item.expires_on?.slice(0, 10) ?? "Expiry unset"}</p><p>Coverage: {item.is_institution_wide ? "Institution-wide" : item.programs.map(program => program.code).join(", ") || "No programs recorded"}</p><p>{item.notes}</p>
      {item.document && <a className="my-2 block text-sm underline" href={`/api/v1/moas/${item.id}/document`}>{item.document.original_name}</a>}
      {(user.role === "admin" || (user.role === "coordinator" && !item.is_institution_wide && item.programs.every(program => reference?.programs.some(own => own.id === program.id))) || (user.role === "supervisor" && item.status === "draft")) && <div className="mt-3"><Button variant="outline" onClick={() => setEditing(item)}>Edit agreement record</Button>{item.status !== "active" && <AgreementUpload id={item.id} done={() => setRevision(value => value + 1)} />}</div>}
    </Card>)}</div>
    {data && data.last_page > 1 && <div className="mt-4 flex gap-3"><Button disabled={page === 1} onClick={() => setPage(page - 1)}>Previous</Button><span>{page} / {data.last_page}</span><Button disabled={page === data.last_page} onClick={() => setPage(page + 1)}>Next</Button></div>}
  </>;
}

function AgreementUpload({ id, done }: { id: number; done: () => void }) {
  const [message, setMessage] = useState(""); const [busy, setBusy] = useState(false);
  return <form className="mt-3 flex flex-wrap items-center gap-3" onSubmit={async event => { event.preventDefault(); const form = event.currentTarget; setBusy(true); setMessage(""); try { await upload(`/moas/${id}/document`, new FormData(form)); form.reset(); done(); } catch (cause) { setMessage(cause instanceof Error ? cause.message : "Upload failed."); } finally { setBusy(false); } }}><input type="file" name="file" aria-label="Executed agreement document" accept=".pdf,.jpg,.jpeg,.png,.docx" required /><Button type="submit" disabled={busy}>Upload document</Button>{message && <p role="alert">{message}</p>}</form>;
}

function MoaEditor({ record, reference, done }: { record: Moa | null; reference: PlacementReference; done: () => void }) {
  const user = useSessionUser(); const [busy, setBusy] = useState(false); const [message, setMessage] = useState("");
  const [form, setForm] = useState({ host_establishment_id: record ? String(record.host_establishment_id) : "", reference_number: record?.reference_number ?? "", status: record?.status ?? "draft", effective_on: record?.effective_on?.slice(0, 10) ?? "", expires_on: record?.expires_on?.slice(0, 10) ?? "", notes: record?.notes ?? "", is_institution_wide: record?.is_institution_wide ?? false, program_ids: record?.programs.map(item => item.id) ?? [] as number[] });
  return <Card className="my-5"><CardTitle>{record ? "Edit agreement" : "Register draft agreement"}</CardTitle><form className="space-y-3" onSubmit={async event => { event.preventDefault(); setBusy(true); setMessage(""); try { await mutate(record ? `/moas/${record.id}` : "/moas", { ...form, host_establishment_id: Number(form.host_establishment_id), effective_on: form.effective_on || null, expires_on: form.expires_on || null }, record ? "PUT" : "POST"); done(); } catch (cause) { setMessage(cause instanceof Error ? cause.message : "Unable to save agreement."); } finally { setBusy(false); } }}>
    <label className="block text-sm">Host establishment<select required disabled={!!record} value={form.host_establishment_id} onChange={event => setForm(current => ({ ...current, host_establishment_id: event.target.value }))} className="mt-1 block w-full rounded border p-2"><option value="">Select host</option>{reference.hosts.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label>
    {([['reference_number', 'Agreement reference'], ['effective_on', 'Effective date'], ['expires_on', 'Expiry date'], ['notes', 'Notes']] as const).map(([key, label]) => <label key={key} className="block text-sm">{label}<input required={key === 'reference_number'} type={key.endsWith('_on') ? 'date' : 'text'} value={form[key]} onChange={event => setForm(current => ({ ...current, [key]: event.target.value }))} className="mt-1 block w-full rounded border p-2" /></label>)}
    <label className="block text-sm">Agreement status<select value={form.status} onChange={event => setForm(current => ({ ...current, status: event.target.value }))} className="ml-2 rounded border p-2">{(user.role === "supervisor" ? ["draft"] : ["draft", "pending_signature", "active", "expired", "terminated"]).map(status => <option key={status} value={status}>{status.replaceAll('_', ' ')}</option>)}</select></label>
    {user.role === "admin" && <label className="block text-sm"><input type="checkbox" checked={form.is_institution_wide} onChange={event => setForm(current => ({ ...current, is_institution_wide: event.target.checked, program_ids: [] }))} /> Explicit institution-wide coverage</label>}
    {!form.is_institution_wide && <fieldset className="grid gap-2 sm:grid-cols-3"><legend className="mb-2 text-sm font-semibold">Covered programs</legend>{reference.programs.map(program => <label key={program.id} className="text-sm"><input type="checkbox" checked={form.program_ids.includes(program.id)} onChange={event => setForm(current => ({ ...current, program_ids: event.target.checked ? [...current.program_ids, program.id] : current.program_ids.filter(id => id !== program.id) }))} /> {program.code}</label>)}</fieldset>}
    <p className="text-sm text-muted-foreground">Save the draft, upload the executed agreement, then record active status with valid dates.</p>
    {message && <p role="alert">{message}</p>}<div className="flex gap-2"><Button type="submit" disabled={busy}>Save agreement</Button><Button variant="outline" onClick={done}>Close</Button></div>
  </form></Card>;
}
