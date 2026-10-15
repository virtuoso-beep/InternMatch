import { useEffect, useRef, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, Field, Pill, matchTone } from "./ui";

type Skill = { name: string; level: number | null; minimum_level: number };
type Recommendation = {
  id: number;
  rank: number;
  opportunity_id: number;
  similarity_score: number;
  distance_km: number | null;
  capacity_at_time: number;
  moa_status_at_time: string;
  generated_at: string;
  snapshot: {
    opportunity_title?: string;
    host_name?: string;
    city?: string;
    description?: string;
    tasks?: string;
    model_name?: string;
    model_version?: string;
    competencies?: Skill[];
  };
};
type Available = {
  id: number;
  eligibility: { capacity_remaining: number; moa_expires_on: string };
};
type Sort = "Best match" | "Nearest" | "Most slots";

export function StoredRecommendations({ enrollmentId }: { enrollmentId: number }) {
  const [items, setItems] = useState<Recommendation[] | null>(null);
  const [available, setAvailable] = useState<Available[]>([]);
  const [interested, setInterested] = useState<number[]>([]);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(true);
  const [revision, setRevision] = useState(0);
  const [filter, setFilter] = useState<Sort>("Best match");
  const [details, setDetails] = useState<Recommendation | null>(null);
  useEffect(() => {
    let active = true;
    setLoading(true);
    setError("");
    setDetails(null);
    Promise.all([
      api<{ data: Recommendation[] }>("/enrollments/" + enrollmentId + "/recommendations"),
      api<{ data: number[] }>("/enrollments/" + enrollmentId + "/interests"),
      api<{ data: Available[] }>("/enrollments/" + enrollmentId + "/opportunities"),
    ])
      .then(([recommendations, interests, opportunities]) => {
        if (active) {
          setItems(recommendations.data);
          setInterested(interests.data);
          setAvailable(opportunities.data);
        }
      })
      .catch((cause: Error) => {
        if (active) {
          setItems(null);
          setError(cause.message);
        }
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [enrollmentId, revision]);
  const availability = new Map(available.map((item) => [item.id, item.eligibility]));
  const sorted = [...(items ?? [])]
    .filter((item) => availability.has(item.opportunity_id))
    .sort((a, b) => {
      const difference =
        filter === "Nearest"
          ? (a.distance_km ?? Infinity) - (b.distance_km ?? Infinity)
          : filter === "Most slots"
            ? availability.get(b.opportunity_id)!.capacity_remaining -
              availability.get(a.opportunity_id)!.capacity_remaining
            : a.rank - b.rank;
      return difference || a.rank - b.rank;
    });
  async function interest(item: Recommendation, next: boolean) {
    setBusy(true);
    setError("");
    setNotice("");
    try {
      await mutate(
        "/enrollments/" + enrollmentId + "/opportunities/" + item.opportunity_id + "/interest",
        { interested: next },
      );
      setInterested((current) =>
        next
          ? [...new Set([...current, item.opportunity_id])]
          : current.filter((id) => id !== item.opportunity_id),
      );
      setNotice(
        next
          ? "Interest saved. Your coordinator still needs to review and approve placement."
          : "Interest withdrawn.",
      );
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Unable to save interest.");
    } finally {
      setBusy(false);
    }
  }
  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div role="group" aria-label="Sort recommendations" className="flex flex-wrap gap-2">
          {(["Best match", "Nearest", "Most slots"] as Sort[]).map((option) => (
            <button
              key={option}
              type="button"
              aria-pressed={filter === option}
              onClick={() => setFilter(option)}
              className={
                "rounded-full border px-5 py-2 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand " +
                (filter === option
                  ? "border-brand bg-brand text-white"
                  : "border-border bg-card hover:bg-muted")
              }
            >
              {option}
            </button>
          ))}
        </div>
        <Button
          variant="outline"
          disabled={busy || loading}
          onClick={async () => {
            setBusy(true);
            setError("");
            setNotice("");
            try {
              const recommendations = await mutate<{ data: Recommendation[] }>(
                "/enrollments/" + enrollmentId + "/recommendations",
                {},
              );
              const opportunities = await api<{ data: Available[] }>(
                "/enrollments/" + enrollmentId + "/opportunities",
              );
              setItems(recommendations.data);
              setAvailable(opportunities.data);
              setDetails(null);
              setNotice("Recommendations updated.");
            } catch (cause) {
              setError(
                cause instanceof Error ? cause.message : "Unable to generate recommendations.",
              );
            } finally {
              setBusy(false);
            }
          }}
        >
          {busy
            ? "Please wait…"
            : items?.length
              ? "Refresh recommendations"
              : "Generate recommendations"}
        </Button>
      </div>
      {error && (
        <div role="alert" className="rounded-lg border border-brand/20 bg-brand-soft p-4 text-sm">
          {error}
          <button
            className="ml-3 underline"
            disabled={busy}
            onClick={() => setRevision((value) => value + 1)}
          >
            Reload
          </button>
        </div>
      )}
      {notice && (
        <p role="status" className="rounded-lg bg-success-soft p-3 text-sm text-success">
          {notice}
        </p>
      )}
      {loading && <p role="status">Checking recommendations and current availability…</p>}
      {!loading && items && (
        <p className="text-sm text-muted-foreground">
          {sorted.length} available {sorted.length === 1 ? "recommendation" : "recommendations"} ·
          Distances are straight-line kilometers. Match scores show competency similarity, not
          placement probability.
        </p>
      )}
      {!loading && sorted.length > 0 && (
        <p role="status" className="text-sm text-muted-foreground">
          {filter === "Best match"
            ? "Showing best competency matches first."
            : filter === "Most slots"
              ? "Showing the most available program slots first. Equal slot counts keep the best-match order."
              : sorted.every((item) => item.distance_km === null)
                ? "Distance is not recorded for these matches, so the best-match order is retained. Save your location in My profile and refresh recommendations; hosts also need saved coordinates."
                : "Showing the nearest recorded locations first. Matches without coordinates appear last."}
        </p>
      )}
      {!loading && items && sorted.length === 0 && (
        <Card>
          <h2 className="font-semibold">No available recommendations yet</h2>
          <p className="mt-2 text-sm text-muted-foreground">
            {items.length
              ? "Previously recommended opportunities no longer meet current eligibility. Refresh recommendations to check again."
              : "Complete your competencies, then generate recommendations. Only opportunities with a valid agreement and available program capacity qualify."}
          </p>
          <div className="mt-4 flex flex-wrap gap-4 text-sm font-semibold text-brand">
            <a className="underline" href="/student/competencies">
              Review competencies
            </a>
            <a className="underline" href="/student/opportunities">
              Browse eligible opportunities
            </a>
          </div>
        </Card>
      )}
      {!loading && (
        <div className="grid gap-5 xl:grid-cols-2">
          {sorted.map((item) => {
            const skills = item.snapshot.competencies;
            const recorded = skills?.filter((skill) => skill.level !== null) ?? [];
            const average = recorded.length
              ? recorded.reduce((sum, skill) => sum + (skill.level ?? 0), 0) / recorded.length
              : null;
            const eligible = availability.get(item.opportunity_id)!;
            const sent = interested.includes(item.opportunity_id);
            return (
              <Card key={item.id} className="flex flex-col p-6">
                <div className="flex items-start justify-between gap-4">
                  <div className="min-w-0">
                    <h3 className="text-xl font-semibold">
                      {item.snapshot.host_name ?? "Opportunity " + item.opportunity_id}
                    </h3>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {item.snapshot.opportunity_title}
                      {item.snapshot.city ? " · " + item.snapshot.city : ""}
                    </p>
                  </div>
                  <span className="shrink-0">
                    <Pill tone={matchTone(item.similarity_score * 100)}>
                      {(item.similarity_score * 100).toFixed(1)}% match
                    </Pill>
                  </span>
                </div>
                <div className="mt-5 flex flex-wrap gap-2">
                  {skills?.map((skill) => (
                    <Pill key={skill.name} tone={skill.level === null ? "muted" : "success"}>
                      {skill.name} · {skill.level === null ? "Not assessed" : skill.level + "%"}
                    </Pill>
                  ))}
                </div>
                <p className="mt-3 text-xs text-muted-foreground">
                  {skills?.length
                    ? recorded.length +
                      " of " +
                      skills.length +
                      " listed skills assessed" +
                      (average === null ? "" : " · " + average.toFixed(0) + "% average proficiency")
                    : skills
                      ? "No structured competency requirements listed."
                      : "Skill breakdown was not retained in this older recommendation. Refresh to include it."}
                </p>
                <div className="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3">
                  <Field
                    label="Straight-line distance"
                    value={
                      item.distance_km === null
                        ? "Not recorded"
                        : item.distance_km.toFixed(2) + " km"
                    }
                  />
                  <Field label="MOA valid through" value={eligible.moa_expires_on} />
                  <Field
                    label="Available program slots"
                    value={String(eligible.capacity_remaining)}
                  />
                </div>
                <div className="mt-auto pt-6">
                  <div className="flex flex-wrap items-center gap-3">
                    {sent ? (
                      <>
                        <Pill tone="success">Interest sent</Pill>
                        <Button
                          variant="ghost"
                          disabled={busy}
                          onClick={() => interest(item, false)}
                        >
                          Withdraw interest
                        </Button>
                      </>
                    ) : (
                      <Button disabled={busy} onClick={() => interest(item, true)}>
                        Express interest
                      </Button>
                    )}
                    <Button variant="outline" onClick={() => setDetails(item)}>
                      View details
                    </Button>
                  </div>
                  <p className="mt-4 text-xs text-muted-foreground">
                    Match generated {new Date(item.generated_at).toLocaleString()}. Availability
                    checked on page load; approval rechecks eligibility.
                  </p>
                </div>
              </Card>
            );
          })}
        </div>
      )}
      {details && <RecommendationDetails item={details} onClose={() => setDetails(null)} />}
    </div>
  );
}

function RecommendationDetails({ item, onClose }: { item: Recommendation; onClose: () => void }) {
  const dialog = useRef<HTMLDialogElement>(null);
  useEffect(() => {
    const element = dialog.current;
    element?.showModal();
    return () => element?.close();
  }, []);
  return (
    <dialog
      ref={dialog}
      aria-labelledby="recommendation-details-title"
      onCancel={(event) => {
        event.preventDefault();
        onClose();
      }}
      className="fixed inset-0 m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-xl overflow-y-auto rounded-xl border border-border bg-card p-6 text-foreground shadow-xl backdrop:bg-black/35"
    >
      <h2 id="recommendation-details-title" className="text-xl font-semibold">
        {item.snapshot.opportunity_title ?? "Recommendation details"}
      </h2>
      <p className="mt-1 text-sm text-muted-foreground">
        {item.snapshot.host_name}
        {item.snapshot.city ? " · " + item.snapshot.city : ""}
      </p>
      <h3 className="mt-5 font-semibold">About the opportunity</h3>
      <p className="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">
        {item.snapshot.description || "No description recorded."}
      </p>
      <h3 className="mt-5 font-semibold">Tasks and responsibilities</h3>
      <p className="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">
        {item.snapshot.tasks || "No tasks recorded."}
      </p>
      <div className="mt-5 rounded-lg bg-muted p-4 text-sm">
        <h3 className="font-semibold">How this recommendation was ranked</h3>
        <p className="mt-2 text-muted-foreground">
          Competency similarity determines rank; ties use straight-line distance. Program
          eligibility, valid MOA and capacity are checked before generation. Expressing interest
          does not reserve a slot or approve placement.
        </p>
        <p className="mt-2 text-muted-foreground">
          At generation: rank {item.rank}, {item.capacity_at_time} available program slots, MOA{" "}
          {item.moa_status_at_time}. These saved facts may differ from current availability.
        </p>
      </div>
      <div className="mt-6 flex justify-end">
        <Button onClick={onClose}>Close</Button>
      </div>
    </dialog>
  );
}
