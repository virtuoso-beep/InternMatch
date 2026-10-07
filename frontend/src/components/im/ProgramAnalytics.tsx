import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { AccessibilityMap } from "./AccessibilityMap";
import { Card, CardTitle, PageHeader, Row, StatCard, StatGrid, Table } from "./ui";

type Band = { label: string; value: number };
type Summary = { enrollments: number; placed: number; completed: number; placement_rate: number | null; completion_rate: number | null };
type Data = {
  programs: (Summary & { code: string; name: string; evaluation_percent: number | null; evaluation_count: number })[];
  hosts: { id: number; name: string; industry: string; city: string; is_active: boolean; current_agreements: number; agreements_expiring_90_days: number }[];
  equity: { median_km: number | null; measured_placements: number; missing_coordinates: number; over_20_km: number; bands: Band[] };
  similarity_bands: Band[]; competency_demand: Band[]; terms: (Summary & { term: string })[]; generated_at: string;
};
const percent = (v: number | null) => v === null ? "No records" : `${v.toFixed(1)}%`;

export function ProgramAnalytics({ section }: { section: "performance" | "hosts" | "equity" | "analytics" }) {
  const [data, setData] = useState<Data | null>(null), [error, setError] = useState("");
  useEffect(() => { let active = true; api<Data>("/program-analytics").then(d => { if (active) setData(d); }).catch((e: Error) => { if (active) setError(e.message); }); return () => { active = false; }; }, []);
  const titles = { performance: "Program performance", hosts: "Host establishments", equity: "Placement equity", analytics: "Analytics" };
  return <><PageHeader title={titles[section]} subtitle="Live records across recorded terms in your assigned programs." />
    {error && <p role="alert">{error}</p>}{!data && !error && <p role="status">Loading program records…</p>}
    {data && <>
      {section === "performance" && <Card><CardTitle>Enrollment outcomes</CardTitle><p className="mb-4 text-sm">Counts represent enrollment records across terms. Placed includes approved, active and completed placements. Evaluation averages include only submitted, scored evaluations.</p>
        <Table head={["Program", "Enrollments", "Placed", "Completed", "Placement rate", "Completion rate", "Evaluation average", "Evaluations"]}>{data.programs.map(p => <Row key={p.code}><td>{p.code} · {p.name}</td><td>{p.enrollments}</td><td>{p.placed}</td><td>{p.completed}</td><td>{percent(p.placement_rate)}</td><td>{percent(p.completion_rate)}</td><td>{percent(p.evaluation_percent)}</td><td>{p.evaluation_count}</td></Row>)}</Table>{data.programs.length === 0 && <p>No programs assigned.</p>}
      </Card>}
      {section === "hosts" && <><StatGrid><StatCard label="Accessible active hosts" value={String(data.hosts.filter(h => h.is_active).length)} /><StatCard label="Current scoped agreements" value={String(data.hosts.reduce((n,h) => n+h.current_agreements,0))} /><StatCard label="Agreements expiring within 90 days" value={String(data.hosts.reduce((n,h) => n+h.agreements_expiring_90_days,0))} /></StatGrid>
        <Card><Table head={["Establishment", "Industry", "City", "Host status", "Current scoped agreements"]}>{data.hosts.map(h => <Row key={h.id}><td>{h.name}</td><td>{h.industry}</td><td>{h.city}</td><td>{h.is_active ? "Active" : "Inactive"}</td><td>{h.current_agreements}</td></Row>)}</Table>{data.hosts.length === 0 && <p>No hosts in your assigned programs.</p>}</Card></>}
      {section === "equity" && <><StatGrid><StatCard label="Median straight-line distance" value={data.equity.median_km === null ? "Not recorded" : `${data.equity.median_km} km`} /><StatCard label="Measured current placements" value={String(data.equity.measured_placements)} /><StatCard label="Current placements over 20 km" value={String(data.equity.over_20_km)} /><StatCard label="Placements missing coordinates" value={String(data.equity.missing_coordinates)} /></StatGrid>
        <Card><CardTitle>Current approved and active placements</CardTitle><Counts values={data.equity.bands} /><p className="mt-3 text-sm">Distances use stored coordinates and Haversine. They do not measure road distance, travel time or fairness.</p></Card><div className="mt-5"><AccessibilityMap note="Host locations within your assigned programs. Student addresses are not exposed in this view." /></div></>}
      {section === "analytics" && <div className="grid gap-5 lg:grid-cols-2"><Card><CardTitle>Stored recommendation snapshots</CardTitle><Counts values={data.similarity_bands} /><p className="mt-3 text-sm">Cosine similarity bands include retained generations. These scores are not placement probabilities.</p></Card>
        <Card><CardTitle>Placement outcomes by term</CardTitle><Table head={["Term", "Enrollments", "Placed", "Placement rate"]}>{data.terms.map(t => <Row key={t.term}><td>{t.term}</td><td>{t.enrollments}</td><td>{t.placed}</td><td>{percent(t.placement_rate)}</td></Row>)}</Table>{data.terms.length === 0 && <p>No recorded terms.</p>}</Card>
        <Card className="lg:col-span-2"><CardTitle>Required competencies in published opportunities</CardTitle><Counts values={data.competency_demand} /></Card></div>}
      <p className="mt-4 text-xs text-muted-foreground">Retrieved {new Date(data.generated_at).toLocaleString()}</p>
    </>}
  </>;
}

function Counts({ values }: { values: Band[] }) {
  return values.length === 0 ? <p>No records available.</p> : <Table head={["Category", "Records"]}>{values.map(v => <Row key={v.label}><td>{v.label}</td><td>{v.value}</td></Row>)}</Table>;
}
