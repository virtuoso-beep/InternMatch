import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, PageHeader } from "./ui";

type Item = { id: number; competency_id: number; level: number; competency: { name: string } };
type Data = { data: Item[]; vocabulary: { id: number; name: string }[] };

export function StudentCompetencies() {
  const [records, setRecords] = useState<Data | null>(null);
  const [selected, setSelected] = useState("");
  const [level, setLevel] = useState("50");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  useEffect(() => {
    let active = true;
    api<Data>("/competencies").then(data => { if (active) setRecords(data); })
      .catch((cause: Error) => { if (active) setError(cause.message); });
    return () => { active = false; };
  }, []);
  return <>
    <PageHeader title="My competencies" subtitle="Save your self-assessed competencies to your student profile." />
    {error && <p role="alert" className="mb-4">{error}</p>}
    {message && <p role="status" className="mb-4">{message}</p>}
    {!records && !error && <p role="status">Loading competencies…</p>}
    {records && <Card>
      <form className="mb-6 flex flex-wrap items-end gap-4" onSubmit={async event => {
        event.preventDefault(); setBusy(true); setError(""); setMessage("");
        try {
          await mutate("/competencies", { competency_id: Number(selected), level: Number(level) });
          setRecords(await api<Data>("/competencies")); setMessage("Competency saved.");
        } catch (cause) { setError(cause instanceof Error ? cause.message : "Unable to save."); }
        finally { setBusy(false); }
      }}>
        <label className="text-sm">Program competency
          <select required className="mt-1 block rounded border p-2" value={selected} onChange={event => setSelected(event.target.value)}>
            <option value="">Select a competency</option>
            {records.vocabulary.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}
          </select>
        </label>
        <label className="text-sm">Self-assessed level (0–100)
          <input required className="mt-1 block w-32 rounded border p-2" type="number" min="0" max="100" step="1" value={level} onChange={event => setLevel(event.target.value)} />
        </label>
        <Button type="submit" disabled={busy || !selected}>{busy ? "Saving…" : "Save competency"}</Button>
      </form>
      {records.vocabulary.length === 0 && <p>Your program enrollment must be configured to show its starter vocabulary.</p>}
      {records.data.length === 0 && <p>No competencies saved yet.</p>}
      <ul className="divide-y">{records.data.map(item => <li key={item.id} className="flex items-center justify-between py-3">
        <span>{item.competency.name} · {item.level}/100</span>
        <Button variant="outline" disabled={busy} onClick={async () => {
          setBusy(true); setError(""); setMessage("");
          try {
            await mutate(`/competencies/${item.id}`, undefined, "DELETE");
            setRecords(current => current && ({ ...current, data: current.data.filter(record => record.id !== item.id) }));
            setMessage("Competency removed.");
          } catch (cause) { setError(cause instanceof Error ? cause.message : "Unable to delete."); }
          finally { setBusy(false); }
        }}>Remove</Button>
      </li>)}</ul>
    </Card>}
  </>;
}
