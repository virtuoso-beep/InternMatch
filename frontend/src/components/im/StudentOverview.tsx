import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { useSessionUser } from "./SessionGuard";
import { Card, CardTitle, PageHeader } from "./ui";
import { StoredRecommendations } from "./StoredRecommendations";

type Enrollment = {
  id: number; year_level: number | null; status: string;
  program_term: { program: { code: string; name: string; required_ojt_hours: number | null }; academic_term: { name: string } };
  current_placement: null | { status: string; host_establishment: { name: string }; opportunity: { title: string } };
};
type Opportunity = { distance_km: number | null; id: number; title: string; description: string; host_name: string; city: string; eligibility: { capacity_remaining: number; moa_expires_on: string } };

export function StudentOverview({ opportunities = false, recommendations = false }: { opportunities?: boolean; recommendations?: boolean }) {
  const user = useSessionUser();
  const [enrollments, setEnrollments] = useState<Enrollment[] | null>(null);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    api<{ data: Enrollment[] }>("/enrollments").then(({ data }) => { if (active) setEnrollments(data); })
      .catch((cause: Error) => { if (active) setError(cause.message); });
    return () => { active = false; };
  }, []);
  return <>
    <PageHeader title={recommendations ? "Recommendations" : opportunities ? "Eligible opportunities" : `Welcome, ${user.name}`} subtitle="Your current academic and placement records." />
    {error && <p role="alert">{error}</p>}
    {!enrollments && !error && <p role="status">Loading your records…</p>}
    {enrollments?.length === 0 && <Card>No enrollment has been linked to your account. Contact your practicum coordinator.</Card>}
    <div className="space-y-4">{enrollments?.map(enrollment => <Card key={enrollment.id}>
      <CardTitle>{enrollment.program_term.program.code} · {enrollment.program_term.academic_term.name}</CardTitle>
      <p>Enrollment: {enrollment.status} · Year level: {enrollment.year_level ?? "Not recorded"}</p>
      <p>Required hours: {enrollment.program_term.program.required_ojt_hours ?? "Awaiting department confirmation"}</p>
      {enrollment.current_placement
        ? <p>{enrollment.current_placement.host_establishment.name} · {enrollment.current_placement.opportunity.title} · {enrollment.current_placement.status}</p>
        : <p>No current placement recorded.</p>}
      {opportunities && <EligibleOpportunities enrollmentId={enrollment.id} />}
      {recommendations && <StoredRecommendations enrollmentId={enrollment.id} />}
    </Card>)}</div>
  </>;
}

function EligibleOpportunities({ enrollmentId }: { enrollmentId: number }) {
  const [items, setItems] = useState<Opportunity[] | null>(null);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    api<{ data: Opportunity[] }>(`/enrollments/${enrollmentId}/opportunities`).then(({ data }) => { if (active) setItems(data); })
      .catch((cause: Error) => { if (active) setError(cause.message); });
    return () => { active = false; };
  }, [enrollmentId]);
  return <div className="mt-4 space-y-3">
    {error && <p role="alert">{error}</p>}
    {!items && !error && <p role="status">Checking eligibility…</p>}
    {items?.length === 0 && <p>No open opportunities currently meet your program, agreement, and capacity requirements.</p>}
    {items?.map(item => <div key={item.id} className="rounded border p-3">
      <h3 className="font-semibold">{item.title}</h3><p>{item.host_name} · {item.city}</p>
      <p>{item.description}</p><p>Straight-line distance: {item.distance_km === null ? "Coordinates not recorded" : `${item.distance_km.toFixed(2)} km`}</p><p>{item.eligibility.capacity_remaining} program slots remaining · MOA valid through {item.eligibility.moa_expires_on}</p>
    </div>)}
  </div>;
}
