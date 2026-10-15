import { useEffect, useRef, useState } from "react";
import { api } from "@/lib/api";
import { useSessionUser } from "./SessionGuard";
import { Button, Card, CardTitle, Field, PageHeader, Pill } from "./ui";
import { StoredRecommendations } from "./StoredRecommendations";

type Enrollment = {
  id: number;
  year_level: number | null;
  status: string;
  program_term: {
    program: { code: string; name: string; required_ojt_hours: number | null };
    academic_term: { name: string };
  };
  current_placement: null | {
    status: string;
    host_establishment: { name: string };
    opportunity: { title: string };
  };
};
type Opportunity = {
  distance_km: number | null;
  id: number;
  title: string;
  description: string;
  tasks?: string;
  host_name: string;
  city: string;
  eligibility: { capacity_remaining: number; moa_expires_on: string };
};

export function StudentOverview({
  opportunities = false,
  recommendations = false,
}: {
  opportunities?: boolean;
  recommendations?: boolean;
}) {
  const user = useSessionUser();
  const [enrollments, setEnrollments] = useState<Enrollment[] | null>(null);
  const [error, setError] = useState("");
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    setError("");
    api<{ data: Enrollment[] }>("/enrollments")
      .then(({ data }) => {
        if (active) setEnrollments(data);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [revision]);
  return (
    <>
      <PageHeader
        title={
          recommendations
            ? "Recommended internships"
            : opportunities
              ? "Internship opportunities"
              : "Welcome, " + user.name
        }
        subtitle={
          recommendations
            ? "Ranked by competency similarity, with straight-line distance used to break ties."
            : opportunities
              ? "Explore eligible hosts with valid agreements and available slots for your program."
              : "Your current academic and placement records."
        }
      />
      {error && (
        <p role="alert" className="mb-4 rounded-lg bg-brand-soft p-4 text-sm">
          {error}
          <button className="ml-3 underline" onClick={() => setRevision((value) => value + 1)}>
            Retry
          </button>
        </p>
      )}
      {!enrollments && !error && <p role="status">Loading your records…</p>}
      {enrollments?.length === 0 && (
        <Card>
          No enrollment has been linked to your account. Contact your practicum coordinator.
        </Card>
      )}
      <div className="space-y-8">
        {enrollments?.map((enrollment) => (
          <section
            key={enrollment.id}
            aria-label={
              enrollment.program_term.program.code +
              (recommendations ? " recommendations" : " opportunities")
            }
          >
            {(enrollments.length > 1 || opportunities) && (
              <div className="mb-5 flex flex-wrap items-center gap-3 rounded-lg border bg-card px-4 py-3 text-sm">
                <span className="font-semibold">{enrollment.program_term.program.code}</span>
                <span className="text-muted-foreground">
                  {enrollment.program_term.academic_term.name}
                </span>
                <Pill>{enrollment.status}</Pill>
              </div>
            )}
            {recommendations ? (
              <StoredRecommendations enrollmentId={enrollment.id} />
            ) : opportunities ? (
              <EligibleOpportunities enrollmentId={enrollment.id} />
            ) : (
              <Card>
                <CardTitle>
                  {enrollment.program_term.program.code} ·{" "}
                  {enrollment.program_term.academic_term.name}
                </CardTitle>
                <p>
                  Enrollment: {enrollment.status} · Year level:{" "}
                  {enrollment.year_level ?? "Not recorded"}
                </p>
                <p>
                  Required hours:{" "}
                  {enrollment.program_term.program.required_ojt_hours ??
                    "Awaiting department confirmation"}
                </p>
                {enrollment.current_placement ? (
                  <p>
                    {enrollment.current_placement.host_establishment.name} ·{" "}
                    {enrollment.current_placement.opportunity.title} ·{" "}
                    {enrollment.current_placement.status}
                  </p>
                ) : (
                  <p>No current placement recorded.</p>
                )}
              </Card>
            )}
          </section>
        ))}
      </div>
    </>
  );
}

function EligibleOpportunities({ enrollmentId }: { enrollmentId: number }) {
  const [items, setItems] = useState<Opportunity[] | null>(null);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [sort, setSort] = useState("Title");
  const [revision, setRevision] = useState(0);
  const [loading, setLoading] = useState(true);
  const [details, setDetails] = useState<Opportunity | null>(null);
  useEffect(() => {
    let active = true;
    setLoading(true);
    setError("");
    setDetails(null);
    api<{ data: Opportunity[] }>("/enrollments/" + enrollmentId + "/opportunities")
      .then(({ data }) => {
        if (active) setItems(data);
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
  const query = search.trim().toLocaleLowerCase();
  const filtered = (items ?? [])
    .filter((item) =>
      [item.title, item.host_name, item.city, item.description].some((value) =>
        value?.toLocaleLowerCase().includes(query),
      ),
    )
    .sort(
      (a, b) =>
        (sort === "Nearest"
          ? (a.distance_km ?? Infinity) - (b.distance_km ?? Infinity)
          : sort === "Most slots"
            ? b.eligibility.capacity_remaining - a.eligibility.capacity_remaining
            : 0) || a.title.localeCompare(b.title),
    );
  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-end gap-3">
        <label className="min-w-0 flex-1 basis-64 text-sm font-medium">
          Search opportunities
          <input
            type="search"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Role, host, city or keyword"
            className="mt-2 block w-full rounded-lg border bg-card px-4 py-2.5 font-normal focus:border-brand focus:ring-2 focus:ring-brand/20"
          />
        </label>
        <label className="text-sm font-medium">
          Sort by
          <select
            value={sort}
            onChange={(event) => setSort(event.target.value)}
            className="mt-2 block rounded-lg border bg-card px-4 py-2.5 font-normal"
          >
            <option>Title</option>
            <option>Nearest</option>
            <option>Most slots</option>
          </select>
        </label>
        <Button
          variant="outline"
          disabled={loading}
          onClick={() => setRevision((value) => value + 1)}
        >
          {loading ? "Checking…" : "Refresh listings"}
        </Button>
      </div>
      {error && (
        <p role="alert" className="rounded-lg bg-brand-soft p-4 text-sm">
          {error}
          <button className="ml-3 underline" onClick={() => setRevision((value) => value + 1)}>
            Retry
          </button>
        </p>
      )}
      {loading && <p role="status">Checking current eligibility…</p>}
      {!loading && items && (
        <>
          <p role="status" className="text-sm text-muted-foreground">
            {filtered.length} of {items.length} eligible opportunities · Straight-line distance in
            km
          </p>
          {filtered.length === 0 && (
            <Card>
              <h2 className="font-semibold">
                {items.length ? "No matching opportunities" : "No eligible opportunities right now"}
              </h2>
              <p className="mt-2 text-sm text-muted-foreground">
                {items.length
                  ? "Try another role, host or city, or clear your search."
                  : "Listings require an active program enrollment, valid MOA and available program capacity. Check again later or contact your coordinator."}
              </p>
              {search && (
                <Button className="mt-4" variant="outline" onClick={() => setSearch("")}>
                  Clear search
                </Button>
              )}
            </Card>
          )}
          <div className="grid gap-5 xl:grid-cols-2">
            {filtered.map((item) => (
              <Card key={item.id} className="flex flex-col p-6">
                <div className="flex items-start justify-between gap-3">
                  <div className="min-w-0">
                    <h3 className="text-xl font-semibold">{item.title}</h3>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {item.host_name}
                      {item.city ? " · " + item.city : ""}
                    </p>
                  </div>
                  <Pill tone="success">Eligible</Pill>
                </div>
                <p className="mt-4 line-clamp-3 text-sm leading-relaxed text-muted-foreground">
                  {item.description || "Open the details to learn more about this opportunity."}
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
                  <Field
                    label="Available program slots"
                    value={String(item.eligibility.capacity_remaining)}
                  />
                  <Field label="MOA valid through" value={item.eligibility.moa_expires_on} />
                </div>
                <div className="mt-auto flex flex-wrap items-center gap-4 pt-6">
                  <Button variant="outline" onClick={() => setDetails(item)}>
                    View details
                  </Button>
                  <a
                    href="/student/recommendations"
                    className="text-sm font-semibold text-brand underline-offset-4 hover:underline"
                  >
                    See my recommendations →
                  </a>
                </div>
              </Card>
            ))}
          </div>
          {items.length > 0 && (
            <p className="text-xs text-muted-foreground">
              Availability is checked when listings load. Your coordinator reviews and approves
              placements; browsing does not reserve a slot.
            </p>
          )}
        </>
      )}
      {details && <OpportunityDetails item={details} onClose={() => setDetails(null)} />}
    </div>
  );
}

function OpportunityDetails({ item, onClose }: { item: Opportunity; onClose: () => void }) {
  const dialog = useRef<HTMLDialogElement>(null);
  useEffect(() => {
    const element = dialog.current;
    element?.showModal();
    return () => element?.close();
  }, []);
  return (
    <dialog
      ref={dialog}
      aria-labelledby="opportunity-details-title"
      onCancel={(event) => {
        event.preventDefault();
        onClose();
      }}
      className="fixed inset-0 m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-xl overflow-y-auto rounded-xl border bg-card p-6 text-foreground shadow-xl backdrop:bg-black/35"
    >
      <h2 id="opportunity-details-title" className="text-xl font-semibold">
        {item.title}
      </h2>
      <p className="mt-1 text-sm text-muted-foreground">
        {item.host_name} · {item.city}
      </p>
      <h3 className="mt-5 font-semibold">About the opportunity</h3>
      <p className="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">
        {item.description || "No description recorded."}
      </p>
      <h3 className="mt-5 font-semibold">Tasks and responsibilities</h3>
      <p className="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">
        {item.tasks || "No tasks recorded."}
      </p>
      <p className="mt-5 rounded-lg bg-muted p-4 text-sm">
        {item.eligibility.capacity_remaining} program slots available · MOA valid through{" "}
        {item.eligibility.moa_expires_on}. Placement remains subject to coordinator approval.
      </p>
      <div className="mt-6 flex justify-end">
        <Button onClick={onClose}>Close</Button>
      </div>
    </dialog>
  );
}
