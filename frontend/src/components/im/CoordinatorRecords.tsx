import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, PageHeader, Row, Table } from "./ui";

type Enrollment = { id: number; status: string; student: { user: { name: string } }; program_term: { program: { code: string }; academic_term: { name: string } }; current_placement: null | { status: string; host_establishment: { name: string } } };
type Review = { id: number; student_name: string; program: string; explanation: string; history: { id: number; judgment: string; reason: string; approved_at: string }[] };

export function CoordinatorStudents() {
  const [rows, setRows] = useState<Enrollment[] | null>(null), [error, setError] = useState("");
  useEffect(() => { let active = true; api<{ data: Enrollment[] }>("/enrollments").then(r => { if (active) setRows(r.data); }).catch((e: Error) => { if (active) setError(e.message); }); return () => { active = false; }; }, []);
  return <><PageHeader title="Student profiles" subtitle="Enrollment and placement records in your assigned programs." />
    {error && <p role="alert">{error}</p>}{!rows && !error && <p role="status">Loading students…</p>}
    {rows && <Card>{rows.length === 0 ? <p>No enrollment records in your assigned programs.</p> : <Table head={["Student", "Program", "Academic term", "Enrollment", "Placement", "Host"]}>{rows.map(r => <Row key={r.id}><td>{r.student.user.name}</td><td>{r.program_term.program.code}</td><td>{r.program_term.academic_term.name}</td><td>{r.status}</td><td>{r.current_placement?.status ?? "No current placement"}</td><td>{r.current_placement?.host_establishment.name ?? "Not assigned"}</td></Row>)}</Table>}</Card>}
  </>;
}

export function CoordinatorRecommendations() {
  const [data, setData] = useState<{ data: Review[]; last_page: number } | null>(null), [page, setPage] = useState(1), [revision, setRevision] = useState(0), [error, setError] = useState("");
  useEffect(() => { let active = true; setData(null); setError(""); api<{ data: Review[]; last_page: number }>(`/recommendation-reviews?page=${page}`).then(r => { if (active) setData(r); }).catch((e: Error) => { if (active) setError(e.message); }); return () => { active = false; }; }, [page, revision]);
  return <><PageHeader title="Recommendation review" subtitle="Review saved explanations and record a suitability judgment. Final placement decisions are made in Pending approvals." />
    {error && <p role="alert">{error}</p>}{!data && !error && <p role="status">Loading recommendations…</p>}
    {data?.data.length === 0 && <Card>No saved recommendations in your assigned programs.</Card>}
    <div className="space-y-4">{data?.data.map(r => <RecommendationReview key={r.id} record={r} saved={() => setRevision(v => v + 1)} />)}</div>
    {data && data.last_page > 1 && <div className="mt-4 flex gap-3"><Button disabled={page === 1} onClick={() => setPage(v => v - 1)}>Previous</Button><span>{page} / {data.last_page}</span><Button disabled={page === data.last_page} onClick={() => setPage(v => v + 1)}>Next</Button></div>}
  </>;
}

function RecommendationReview({ record, saved }: { record: Review; saved: () => void }) {
  const [judgment, setJudgment] = useState("uncertain"), [reason, setReason] = useState(""), [confirm, setConfirm] = useState(false), [busy, setBusy] = useState(false), [error, setError] = useState("");
  return <Card><h2 className="font-semibold">{record.student_name} · {record.program}</h2><p className="mt-3 whitespace-pre-wrap text-sm">{record.explanation}</p>
    <form className="mt-4 space-y-3" aria-label={`Review recommendation ${record.id}`} onSubmit={async e => { e.preventDefault(); setBusy(true); setError(""); try { await mutate(`/recommendation-reviews/${record.id}`, { judgment, reason, confirm }); saved(); } catch (cause) { setError(cause instanceof Error ? cause.message : "Unable to save judgment."); } finally { setBusy(false); } }}>
      <label className="block text-sm">Suitability judgment<select aria-label="Suitability judgment" className="mt-1 block rounded border p-2" value={judgment} onChange={e => setJudgment(e.target.value)}><option value="uncertain">Uncertain</option><option value="suitable">Suitable</option><option value="unsuitable">Unsuitable</option></select></label>
      <label className="block text-sm">Supporting reason<textarea required minLength={10} maxLength={5000} className="mt-1 block w-full rounded border p-2" value={reason} onChange={e => setReason(e.target.value)} /></label>
      <label className="block text-sm"><input type="checkbox" required checked={confirm} onChange={e => setConfirm(e.target.checked)} /> I confirm this judgment and its supporting reason.</label>
      {error && <p role="alert">{error}</p>}<Button type="submit" disabled={busy || !confirm}>{busy ? "Saving…" : "Save judgment"}</Button>
    </form>
    <h3 className="mt-4 font-semibold">Judgment history</h3>{record.history.length === 0 ? <p className="text-sm">No judgments recorded.</p> : <ul className="space-y-2 text-sm">{record.history.map(h => <li key={h.id}>{h.judgment} · {h.reason} · {h.approved_at}</li>)}</ul>}
  </Card>;
}
