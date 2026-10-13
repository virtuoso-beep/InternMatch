import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { useSessionUser } from "./SessionGuard";
import { Button, Card, Pill } from "./ui";
import {
  DashboardEmpty,
  DashboardHeading,
  DashboardLink,
  DashboardMetric,
  DashboardProgress,
} from "./DashboardUI";

type Enrollment = {
  id: number;
  year_level: number | null;
  status: string;
  program_term: {
    program: { code: string; name: string; required_ojt_hours: number | null };
    academic_term: { name: string; academic_year: string };
  };
  current_placement: {
    id: number;
    status: string;
    host_establishment: { name: string };
    opportunity: { title: string };
  } | null;
};
type Recommendation = {
  id: number;
  rank: number;
  similarity_score: number;
  distance_km: number | null;
  generated_at: string;
  snapshot: { host_name?: string; opportunity_title?: string; city?: string };
};
type Checklist = {
  requirements: {
    id: number;
    is_required: boolean;
    due_at: string | null;
    requirement_type: { name: string; kind: string };
  }[];
  submissions: { program_term_requirement_id: number; revision: number; status: string }[];
  attendances: { program_term_requirement_id: number; confirmed_at: string | null }[];
};
type Monitoring = {
  progress: {
    completed_minutes: number;
    required_hours: number | null;
    remaining_minutes: number | null;
    flags: { code: string; description: string }[];
  };
};

export function StudentDashboard() {
  const user = useSessionUser();
  const [enrollments, setEnrollments] = useState<Enrollment[] | null>(null);
  const [selected, setSelected] = useState("");
  const [revision, setRevision] = useState(0);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    setError("");
    setEnrollments(null);
    api<{ data: Enrollment[] }>("/enrollments")
      .then(({ data }) => {
        if (!active) return;
        setEnrollments(data);
        setSelected((current) =>
          data.some((e) => String(e.id) === current)
            ? current
            : String(
                data.find((e) => e.current_placement?.status === "active")?.id ?? data[0]?.id ?? "",
              ),
        );
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [revision]);
  const enrollment = enrollments?.find((e) => String(e.id) === selected);
  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="mb-2 text-xs font-semibold uppercase tracking-widest text-brand">
            Your internship overview
          </p>
          <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
            Welcome back, {user.name.split(" ")[0]}
          </h1>
          <p className="mt-2 text-sm text-muted-foreground">
            {enrollment
              ? `${enrollment.program_term.program.name}${enrollment.year_level ? ` · Year ${enrollment.year_level}` : ""} · ${enrollment.program_term.academic_term.academic_year}`
              : "Your next steps, requirements, and internship progress in one place."}
          </p>
        </div>
        <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
          Refresh dashboard
        </Button>
      </div>
      {error && (
        <p
          role="alert"
          className="rounded-lg border border-brand/20 bg-brand-soft p-4 text-sm text-brand"
        >
          Unable to load your dashboard. {error}
        </p>
      )}
      {!enrollments && !error && (
        <p role="status" className="py-8 text-muted-foreground">
          Loading your internship overview…
        </p>
      )}
      {enrollments?.length === 0 && (
        <DashboardEmpty>
          No enrollment is linked to your account yet. Contact your practicum coordinator to get
          started.
        </DashboardEmpty>
      )}
      {!!enrollments?.length && (
        <div className="flex flex-wrap items-center justify-between gap-3">
          <label className="min-w-0 max-w-full text-sm font-medium">
            Enrollment
            <select
              className="mt-2 block w-full max-w-full rounded-lg border border-input bg-card p-2 text-sm sm:ml-3 sm:mt-0 sm:inline-block sm:w-auto"
              value={selected}
              onChange={(e) => setSelected(e.target.value)}
            >
              {enrollments.map((e) => (
                <option key={e.id} value={e.id}>
                  {e.program_term.program.code} · {e.program_term.academic_term.name}
                </option>
              ))}
            </select>
          </label>
          <DashboardLink href="/student/profile">View my profile</DashboardLink>
        </div>
      )}
      {enrollment && (
        <EnrollmentDashboard key={`${enrollment.id}-${revision}`} enrollment={enrollment} />
      )}
    </div>
  );
}

function EnrollmentDashboard({ enrollment }: { enrollment: Enrollment }) {
  const [recommendations, setRecommendations] = useState<Recommendation[] | null>(null);
  const [checklist, setChecklist] = useState<Checklist | null>(null);
  const [monitoring, setMonitoring] = useState<Monitoring | null>(null);
  const [errors, setErrors] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);
  const placement = enrollment.current_placement;
  const placementId = placement?.id;
  useEffect(() => {
    let active = true;
    Promise.allSettled([
      api<{ data: Recommendation[] }>(`/enrollments/${enrollment.id}/recommendations`),
      api<Checklist>(`/enrollments/${enrollment.id}/requirements`),
      placementId
        ? api<Monitoring>(`/placements/${placementId}/monitoring`)
        : Promise.resolve(null),
    ]).then(([matches, requirements, progress]) => {
      if (!active) return;
      const failed: string[] = [];
      if (matches.status === "fulfilled") setRecommendations(matches.value.data);
      else failed.push("Recommendations could not be loaded.");
      if (requirements.status === "fulfilled") setChecklist(requirements.value);
      else failed.push("Requirements could not be loaded.");
      if (progress.status === "fulfilled") setMonitoring(progress.value);
      else failed.push("Certified hours could not be loaded.");
      setErrors(failed);
      setLoading(false);
    });
    return () => {
      active = false;
    };
  }, [enrollment.id, placementId]);
  const rows = checklist?.requirements.map((requirement) => {
    const submission = checklist.submissions
      .filter((s) => s.program_term_requirement_id === requirement.id)
      .sort((a, b) => b.revision - a.revision)[0];
    const attendance = checklist.attendances.find(
      (a) => a.program_term_requirement_id === requirement.id,
    );
    const status =
      requirement.requirement_type.kind === "event"
        ? attendance?.confirmed_at
          ? "confirmed"
          : attendance
            ? "awaiting confirmation"
            : "not attended"
        : (submission?.status ?? "not submitted");
    return { ...requirement, status, done: ["approved", "confirmed"].includes(status) };
  });
  const required = rows?.filter((r) => r.is_required);
  const pending = required?.filter((r) => !r.done).length;
  const finished = required?.filter((r) => r.done).length;
  const progress = monitoring?.progress;
  const hours = progress ? progress.completed_minutes / 60 : null;
  const target = progress?.required_hours ?? enrollment.program_term.program.required_ojt_hours;
  const percent = hours !== null && target ? Math.min(100, (hours / target) * 100) : null;
  const shownRows = [...(rows ?? [])]
    .sort(
      (a, b) => Number(a.done) - Number(b.done) || Number(b.is_required) - Number(a.is_required),
    )
    .slice(0, 6);
  const number = (value: number) => value.toLocaleString(undefined, { maximumFractionDigits: 2 });
  return (
    <>
      {errors.length > 0 && (
        <p role="alert" className="rounded-lg bg-brand-soft p-4 text-sm text-brand">
          {errors.join(" ")} Use Refresh dashboard to retry.
        </p>
      )}
      {loading && (
        <span role="status" className="sr-only">
          Loading dashboard details…
        </span>
      )}
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <DashboardMetric
          label="Requirements completed"
          value={required ? `${finished} / ${required.length}` : "—"}
          hint="Approved documents and confirmed events"
          accent
        />
        <DashboardMetric
          label="Recommended opportunities"
          value={recommendations ? String(recommendations.length) : "—"}
          hint="Your latest saved recommendation results"
          index={1}
        />
        <DashboardMetric
          label="Requirements to complete"
          value={pending === undefined ? "—" : String(pending)}
          hint="Required items still awaiting completion"
          index={2}
        />
        <DashboardMetric
          label="Certified internship hours"
          value={
            hours === null ? "—" : `${number(hours)} / ${target === null ? "—" : number(target)}`
          }
          hint={
            placement
              ? "Only supervisor-certified time counts"
              : "Available once a placement is recorded"
          }
          index={3}
          accent
        />
      </div>
      <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <Card className="!rounded-xl !p-6">
          <DashboardHeading
            title="Top recommendations for you"
            description="Based on your saved competency matches"
            action={<DashboardLink href="/student/recommendations">View all</DashboardLink>}
          />
          {recommendations === null ? (
            <DashboardEmpty>
              {loading
                ? "Loading recommendations…"
                : "Recommendations are unavailable. Please retry."}
            </DashboardEmpty>
          ) : recommendations.length === 0 ? (
            <DashboardEmpty>
              No saved recommendations yet. Complete your competencies, then{" "}
              <DashboardLink href="/student/recommendations">
                generate recommendations
              </DashboardLink>
              .
            </DashboardEmpty>
          ) : (
            <div className="space-y-3">
              {recommendations.slice(0, 3).map((item, index) => (
                <a
                  key={item.id}
                  href="/student/recommendations"
                  className="group flex items-start gap-3 rounded-lg border border-border p-4 transition-colors hover:border-brand/30 hover:bg-brand-soft/30 focus-visible:outline-2 focus-visible:outline-brand"
                >
                  <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-sm font-bold text-brand">
                    {index + 1}
                  </span>
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <h3 className="font-semibold group-hover:text-brand">
                        {item.snapshot.host_name ?? "Host not recorded"}
                      </h3>
                      <Pill tone="success">
                        {(Number(item.similarity_score) * 100).toFixed(1)}% similarity
                      </Pill>
                    </div>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {item.snapshot.opportunity_title ?? "Internship opportunity"}
                    </p>
                    <p className="mt-2 text-xs text-muted-foreground">
                      {item.snapshot.city ? `${item.snapshot.city} · ` : ""}
                      {item.distance_km === null
                        ? "Distance not recorded"
                        : `${Number(item.distance_km).toFixed(2)} km straight-line`}
                    </p>
                  </div>
                </a>
              ))}
            </div>
          )}
          {recommendations?.[0] && (
            <p className="mt-4 text-xs leading-relaxed text-muted-foreground">
              Saved {new Date(recommendations[0].generated_at).toLocaleDateString()}. Availability
              may have changed. Similarity is not a placement guarantee.
            </p>
          )}
        </Card>
        <Card className="!rounded-xl !p-6">
          <DashboardHeading
            title="Requirement checklist"
            description="Outstanding required items appear first"
            action={<DashboardLink href="/student/requirements">View all</DashboardLink>}
          />
          {!rows ? (
            <DashboardEmpty>
              {loading ? "Loading requirements…" : "Requirements are unavailable. Please retry."}
            </DashboardEmpty>
          ) : rows.length === 0 ? (
            <DashboardEmpty>
              No requirements have been configured for this cohort yet.
            </DashboardEmpty>
          ) : (
            <ul className="divide-y divide-border">
              {shownRows.map((row) => (
                <li key={row.id} className="flex items-start justify-between gap-3 py-3 first:pt-0">
                  <div>
                    <p className="text-sm font-medium">{row.requirement_type.name}</p>
                    {row.due_at && (
                      <p className="mt-1 text-xs text-muted-foreground">
                        Due {new Date(row.due_at).toLocaleDateString()}
                      </p>
                    )}
                    {!row.is_required && <p className="text-xs text-muted-foreground">Optional</p>}
                  </div>
                  <Pill tone={row.done ? "success" : "warn"}>
                    {row.status.replaceAll("_", " ")}
                  </Pill>
                </li>
              ))}
            </ul>
          )}
          {rows && rows.length > 6 && (
            <p className="mt-4 text-xs text-muted-foreground">
              Showing 6 of {rows.length} items. Open the full checklist to submit or review
              documents.
            </p>
          )}
        </Card>
      </div>
      <Card className="!rounded-xl !p-6">
        <DashboardHeading
          title="Internship progress"
          action={<DashboardLink href="/student/internship">My internship</DashboardLink>}
        />
        {placement ? (
          <>
            <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
              <div>
                <p className="font-semibold">{placement.opportunity.title}</p>
                <p className="mt-1 text-sm text-muted-foreground">
                  {placement.host_establishment.name}
                </p>
              </div>
              <Pill tone={placement.status === "active" ? "success" : "info"}>
                {placement.status}
              </Pill>
            </div>
            {percent !== null && hours !== null ? (
              <>
                <div className="mb-3 flex flex-wrap justify-between gap-2 text-sm">
                  <p>
                    {number(hours)} of {number(target!)} required hours certified
                  </p>
                  <strong className="text-success">{Math.round(percent)}% complete</strong>
                </div>
                <DashboardProgress value={percent} label="Certified internship hours" />
                {progress?.remaining_minutes !== null &&
                  progress?.remaining_minutes !== undefined && (
                    <p className="mt-3 text-xs text-muted-foreground">
                      {number(progress.remaining_minutes / 60)} hours remaining. Pending time logs
                      are not included.
                    </p>
                  )}
              </>
            ) : (
              <p className="text-sm text-muted-foreground">
                {loading
                  ? "Loading certified progress…"
                  : target === null
                    ? "Required hours are awaiting department confirmation."
                    : "Certified progress is unavailable. Refresh to retry."}
              </p>
            )}
            {placement.status === "approved" && (
              <p className="mt-4 rounded-lg bg-info-soft p-3 text-sm text-info">
                Your placement is approved. Time-log submissions become available when it is active.
              </p>
            )}
            {progress?.flags.map((flag) => (
              <p key={flag.code} className="mt-3 rounded-lg bg-warn-soft p-3 text-sm text-warn">
                Attention required: {flag.description}
              </p>
            ))}
          </>
        ) : (
          <DashboardEmpty>
            No placement has been recorded for this enrollment. Explore recommendations and complete
            your requirements while your coordinator reviews placement readiness.
          </DashboardEmpty>
        )}
      </Card>
    </>
  );
}
