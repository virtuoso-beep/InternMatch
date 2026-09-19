import { useEffect, useState } from "react";
import { api } from "@/lib/api";

type Recommendation = {
  id: number; rank: number; opportunity_id: number; similarity_score: number; distance_km: number | null;
  capacity_at_time: number; moa_status_at_time: string; generated_at: string;
  snapshot: { opportunity_title?: string; host_name?: string };
};

export function StoredRecommendations({ enrollmentId }: { enrollmentId: number }) {
  const [items, setItems] = useState<Recommendation[] | null>(null);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    api<{ data: Recommendation[] }>(`/enrollments/${enrollmentId}/recommendations`).then(({ data }) => { if (active) setItems(data); })
      .catch((cause: Error) => { if (active) setError(cause.message); });
    return () => { active = false; };
  }, [enrollmentId]);
  return <div className="mt-4 space-y-3">
    {error && <p role="alert">{error}</p>}
    {!items && !error && <p role="status">Loading stored recommendations…</p>}
    {items?.length === 0 && <p>No semantic recommendations have been generated. Eligible opportunities are available in the Opportunities page.</p>}
    {items?.map(item => <div key={item.id} className="rounded border p-3">
      <h3 className="font-semibold">{item.rank}. {item.snapshot.opportunity_title ?? `Opportunity ${item.opportunity_id}`}</h3>
      {item.snapshot.host_name && <p>{item.snapshot.host_name}</p>}
      <p>Cosine similarity: {item.similarity_score.toFixed(3)}</p>
      <p>Straight-line distance: {item.distance_km === null ? "Not recorded" : `${item.distance_km.toFixed(2)} km`}</p>
      <p>Program capacity at generation: {item.capacity_at_time} · MOA at generation: {item.moa_status_at_time}</p>
      <p className="text-sm text-muted-foreground">Snapshot generated {new Date(item.generated_at).toLocaleString()}. Availability may since have changed.</p>
    </div>)}
  </div>;
}
