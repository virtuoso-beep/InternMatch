import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, Field, FilterChips, Pill, matchTone } from "./ui";

type Recommendation = {
  id: number; rank: number; opportunity_id: number; similarity_score: number; distance_km: number | null;
  capacity_at_time: number; moa_status_at_time: string; generated_at: string;
  snapshot: { opportunity_title?: string; host_name?: string; city?: string; description?: string; tasks?: string; model_name?: string; model_version?: string };
};

export function StoredRecommendations({ enrollmentId }: { enrollmentId: number }) {
  const [items, setItems] = useState<Recommendation[] | null>(null);
  const [interested, setInterested] = useState<number[]>([]);
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [filter, setFilter] = useState("Best match");
  const [details, setDetails] = useState<Recommendation | null>(null);
  useEffect(() => {
    let active = true;
    Promise.all([api<{ data: Recommendation[] }>(`/enrollments/${enrollmentId}/recommendations`), api<{ data: number[] }>(`/enrollments/${enrollmentId}/interests`)])
      .then(([recommendations, interests]) => { if (active) { setItems(recommendations.data); setInterested(interests.data); } })
      .catch((cause: Error) => { if (active) setError(cause.message); });
    return () => { active = false; };
  }, [enrollmentId]);
  const sorted = [...(items ?? [])].sort((a,b) => filter === "Nearest" ? (a.distance_km ?? Infinity) - (b.distance_km ?? Infinity) : filter === "Most slots" ? b.capacity_at_time-a.capacity_at_time : a.rank-b.rank);
  return <div className="mt-4 space-y-3">
    {error && <p role="alert">{error}</p>}
    <div className="flex flex-wrap items-center justify-between gap-3">
      <FilterChips options={["Best match", "Nearest", "Most slots"]} value={filter} onChange={setFilter} />
      <Button disabled={busy} onClick={async () => {
        setBusy(true); setError("");
        try { setItems((await mutate<{data:Recommendation[]}>(`/enrollments/${enrollmentId}/recommendations`, {})).data); }
        catch (cause) { setError(cause instanceof Error ? cause.message : "Unable to generate recommendations."); }
        finally { setBusy(false); }
      }}>{busy ? "Generating…" : "Generate recommendations"}</Button>
    </div>
    {!items && !error && <p role="status">Loading recommendations…</p>}
    {items?.length === 0 && <Card>No recommendations are available. Complete your competencies and check eligible opportunities.</Card>}
    <div className="grid gap-4 xl:grid-cols-2">{sorted.map(item => <Card key={item.id}>
      <div className="flex items-start justify-between gap-4">
        <div><h3 className="text-lg font-semibold">{item.snapshot.host_name ?? `Opportunity ${item.opportunity_id}`}</h3>
          <p className="text-sm text-muted-foreground">{item.snapshot.opportunity_title}{item.snapshot.city ? ` · ${item.snapshot.city}` : ""}</p></div>
        <Pill tone={matchTone(item.similarity_score*100)}>{(item.similarity_score*100).toFixed(1)}% similarity</Pill>
      </div>
      <p className="mt-3 text-xs text-muted-foreground">Semantic competency similarity; this is not a probability of placement.</p>
      <div className="mt-4 grid grid-cols-3 gap-3 text-sm">
        <Field label="Straight-line distance" value={item.distance_km === null ? "Not recorded" : `${item.distance_km.toFixed(2)} km`} />
        <Field label="MOA at generation" value={item.moa_status_at_time} />
        <Field label="Program slots" value={String(item.capacity_at_time)} />
      </div>
      <div className="mt-5 flex gap-2">
        <Button disabled={busy} onClick={async () => {
          const next = !interested.includes(item.opportunity_id); setBusy(true); setError("");
          try { await mutate(`/enrollments/${enrollmentId}/opportunities/${item.opportunity_id}/interest`, {interested:next}); setInterested(current => next ? [...current,item.opportunity_id] : current.filter(id => id !== item.opportunity_id)); }
          catch(cause) { setError(cause instanceof Error ? cause.message : "Unable to save interest."); }
          finally { setBusy(false); }
        }}>{interested.includes(item.opportunity_id) ? "Interest sent" : "Express interest"}</Button>
        <Button variant="outline" onClick={() => setDetails(item)}>View details</Button>
      </div>
      <p className="mt-3 text-xs text-muted-foreground">Generated {new Date(item.generated_at).toLocaleString()}. Availability may have changed.</p>
    </Card>)}</div>
    {details && <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 px-4" role="presentation" onClick={() => setDetails(null)}>
      <div role="dialog" aria-modal="true" aria-label="Recommendation details" className="w-full max-w-md rounded-lg border border-border bg-card p-5 shadow-xl" onClick={event => event.stopPropagation()}>
        <h2 className="text-lg font-semibold">{details.snapshot.opportunity_title}</h2>
        <p className="mt-1 text-sm text-muted-foreground">{details.snapshot.host_name}</p>
        <p className="mt-4 text-sm">{details.snapshot.description}</p><p className="mt-2 text-sm">{details.snapshot.tasks}</p>
        <p className="mt-3 text-xs text-muted-foreground">Ranked by cosine similarity, then straight-line distance for ties. Program eligibility, valid MOA and available program capacity were checked first. Requirements are checked before allocation and deployment.</p>
        <div className="mt-5 flex justify-end"><Button onClick={() => setDetails(null)}>Close</Button></div>
      </div>
    </div>}
  </div>;
}
