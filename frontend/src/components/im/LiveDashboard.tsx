import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { useSessionUser } from "./SessionGuard";
import { Card, CardTitle, PageHeader, StatCard, StatGrid } from "./ui";

type Dashboard = { stats: { label: string; value: number }[]; programs: { id: number; code: string }[]; program_breakdown: { code: string; enrollments: number }[]; generated_at: string };
export function LiveDashboard() {
  const user = useSessionUser(); const [data, setData] = useState<Dashboard | null>(null); const [program, setProgram] = useState(""); const [error, setError] = useState("");
  useEffect(() => { let active = true; setError(""); api<Dashboard>(`/dashboard${program ? `?program_id=${program}` : ""}`).then(value => { if (active) setData(value); }).catch((cause: Error) => { if (active) setError(cause.message); }); return () => { active = false; }; }, [program]);
  return <><PageHeader title={`Welcome, ${user.name}`} subtitle="Current records within your authorized scope." />{error && <p role="alert">{error}</p>}{!data && !error && <p role="status">Loading dashboard…</p>}{data && <>
    <label className="mb-5 block text-sm">Program<select value={program} onChange={event => setProgram(event.target.value)} className="ml-2 rounded border bg-card p-2"><option value="">All accessible programs</option>{data.programs.map(item => <option key={item.id} value={item.id}>{item.code}</option>)}</select></label>
    <StatGrid>{data.stats.map(item => <StatCard key={item.label} label={item.label} value={String(item.value)} />)}</StatGrid>
    <Card><CardTitle>Enrollment by program</CardTitle>{data.program_breakdown.length === 0 ? <p>No enrollment records match this scope.</p> : <ul className="space-y-2">{data.program_breakdown.map(item => <li key={item.code} className="flex justify-between border-b py-2"><span>{item.code}</span><span>{item.enrollments}</span></li>)}</ul>}<p className="mt-4 text-xs text-muted-foreground">Retrieved {new Date(data.generated_at).toLocaleString()}. Certified hours include verified time logs only.</p></Card>
  </>}</>;
}
