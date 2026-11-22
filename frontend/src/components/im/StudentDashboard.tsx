import { useEffect, useMemo, useState } from "react";
import { Link } from "@tanstack/react-router";
import { Briefcase, ClipboardCheck, Clock, ListOrdered, RotateCw } from "lucide-react";
import { api } from "@/lib/api";
import { TONE_COLOR } from "@/lib/status";
import { Button, Card, PageHeader, Pill, formatSimilarity, statusTone } from "./ui";
import { inputClass } from "./formUtils";
import {
  ActionList,
  EmptyState,
  ErrorState,
  KpiCard,
  LoadingBlock,
  SectionHeading,
  type ActionItem,
} from "./ds";
import { ChartCard, DonutChartView } from "./charts";
import { SimilarityScoreLabel } from "./SimilarityScoreLabel";

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
type PastPlacement = {
  id: number;
  status: string;
  student_enrollment_id: number;
  host_establishment: { name: string };
  opportunity: { title: string };
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

const linkClass = "text-sm font-semibold text-brand hover:underline";
const number = (value: number) => value.toLocaleString("en-PH", { maximumFractionDigits: 2 });
const manila = (value: string) =>
  new Date(value).toLocaleDateString("en-PH", { timeZone: "Asia/Manila", dateStyle: "medium" });

export function StudentDashboard() {
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
    <div className="space-y-8">
      <PageHeader
        title="Dashboard"
        subtitle={
          enrollment
            ? `${enrollment.program_term.program.name}${enrollment.year_level ? `, year ${enrollment.year_level}` : ""} · ${enrollment.program_term.academic_term.name} ${enrollment.program_term.academic_term.academic_year}`
            : "Your requirements, recommendations and internship progress"
        }
        action={
          <>
            {enrollments && enrollments.length > 1 && (
              <label className="flex items-center gap-2 text-sm font-medium">
                <span className="whitespace-nowrap">Enrollment</span>
                <select
                  className={`${inputClass} w-auto`}
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
            )}
            <Button
              variant="outline"
              size="sm"
              onClick={() => setRevision((v) => v + 1)}
              disabled={!enrollments && !error}
            >
              <RotateCw size={14} aria-hidden="true" />
              Refresh
            </Button>
          </>
        }
      />
      {error && (
        <ErrorState
          title="Could not load your enrollment"
          message={error}
          onRetry={() => setRevision((v) => v + 1)}
        />
      )}
      {!enrollments && !error && <LoadingBlock label="Loading your enrollment" rows={4} />}
      {enrollments?.length === 0 && (
        <EmptyState
          title="No enrollment is linked to your account yet"
          description="Your practicum coordinator links your enrollment after the class roster is imported. Contact them if this persists."
        />
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
  const [failed, setFailed] = useState<{ matches: string; requirements: string; progress: string }>(
    { matches: "", requirements: "", progress: "" },
  );
  const [loading, setLoading] = useState(true);
  const [retry, setRetry] = useState(0);
  // A completed or ended placement is not "current"; fall back to the student's latest own placement.
  const [past, setPast] = useState<PastPlacement | null>(null);
  useEffect(() => {
    if (enrollment.current_placement) return;
    let active = true;
    api<{ data: PastPlacement[] }>("/placements")
      .then(({ data }) => {
        if (active) setPast(data.find((p) => p.student_enrollment_id === enrollment.id) ?? null);
      })
      .catch(() => {});
    return () => {
      active = false;
    };
  }, [enrollment.id, enrollment.current_placement]);
  const placement = enrollment.current_placement ?? past;
  const placementId = placement?.id;
  useEffect(() => {
    let active = true;
    setLoading(true);
    Promise.allSettled([
      api<{ data: Recommendation[] }>(`/enrollments/${enrollment.id}/recommendations`),
      api<Checklist>(`/enrollments/${enrollment.id}/requirements`),
      placementId
        ? api<Monitoring>(`/placements/${placementId}/monitoring`)
        : Promise.resolve(null),
    ]).then(([matches, requirements, progress]) => {
      if (!active) return;
      const message = (r: PromiseSettledResult<unknown>) =>
        r.status === "rejected"
          ? r.reason instanceof Error
            ? r.reason.message
            : "Request failed."
          : "";
      if (matches.status === "fulfilled") setRecommendations(matches.value.data);
      if (requirements.status === "fulfilled") setChecklist(requirements.value);
      if (progress.status === "fulfilled") setMonitoring(progress.value);
      setFailed({
        matches: message(matches),
        requirements: message(requirements),
        progress: message(progress),
      });
      setLoading(false);
    });
    return () => {
      active = false;
    };
  }, [enrollment.id, placementId, retry]);

  const rows = useMemo(
    () =>
      checklist?.requirements.map((requirement) => {
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
        const done = ["approved", "confirmed"].includes(status);
        const waiting = ["submitted", "under_review", "awaiting confirmation"].includes(status);
        return { ...requirement, status, done, waiting };
      }),
    [checklist],
  );
  const required = rows?.filter((r) => r.is_required);
  const finished = required?.filter((r) => r.done).length ?? 0;
  const waiting = required?.filter((r) => r.waiting).length ?? 0;
  const todo = (required?.length ?? 0) - finished - waiting;
  const returned = rows?.filter((r) => r.status === "rejected").length ?? 0;
  const progress = monitoring?.progress;
  const hours = progress ? progress.completed_minutes / 60 : null;
  const target = progress?.required_hours ?? enrollment.program_term.program.required_ojt_hours;
  const percent = hours !== null && target ? Math.min(100, (hours / target) * 100) : null;
  const shownRows = [...(rows ?? [])]
    .sort(
      (a, b) => Number(a.done) - Number(b.done) || Number(b.is_required) - Number(a.is_required),
    )
    .slice(0, 6);

  const actions: ActionItem[] = [];
  progress?.flags.forEach((flag) =>
    actions.push({
      id: `flag-${flag.code}`,
      tone: "danger",
      to: "/student/internship",
      title: flag.description,
    }),
  );
  if (returned)
    actions.push({
      id: "returned",
      tone: "danger",
      to: "/student/requirements",
      title: `${returned} ${returned === 1 ? "document was" : "documents were"} returned for correction`,
    });
  if (todo > 0)
    actions.push({
      id: "todo",
      tone: "warn",
      to: "/student/requirements",
      title: `${todo} required ${todo === 1 ? "item is" : "items are"} not yet submitted or attended`,
    });
  if (placement?.status === "approved")
    actions.push({
      id: "approved",
      tone: "info",
      to: "/student/internship",
      title: "Your placement is approved",
      detail: "Time logs open when your coordinator marks the internship active.",
    });
  if (!placement && recommendations?.length === 0)
    actions.push({
      id: "recommend",
      tone: "info",
      to: "/student/recommendations",
      title: "No saved recommendations yet",
      detail: "Update your competencies, then generate recommendations.",
    });

  const requirementSlices = [
    { label: "Done", value: finished, color: TONE_COLOR.success },
    { label: "Waiting for review", value: waiting, color: TONE_COLOR.warn },
    { label: "To do", value: Math.max(0, todo), color: TONE_COLOR.brand },
  ];

  return (
    <>
      <section aria-labelledby="student-attention">
        <SectionHeading id="student-attention" title="Needs your attention" />
        <ActionList
          items={actions}
          loading={loading}
          emptyText="Nothing needs your attention right now."
        />
      </section>

      <section aria-labelledby="student-kpis">
        <SectionHeading id="student-kpis" title="Your internship at a glance" />
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <Card className="p-4 sm:col-span-2">
            <div className="flex items-center justify-between gap-3">
              <p className="text-sm font-medium text-muted-foreground">
                Certified internship hours
              </p>
              <span className="rounded-lg bg-success-soft p-1.5 text-success">
                <Clock size={16} strokeWidth={1.9} aria-hidden="true" />
              </span>
            </div>
            {loading ? (
              <LoadingBlock label="Loading certified hours" rows={2} />
            ) : failed.progress ? (
              <p className="mt-2 text-sm text-brand">Certified hours could not be loaded.</p>
            ) : !placement ? (
              <p className="mt-2 text-sm text-muted-foreground">
                Hours are counted once a placement is recorded.
              </p>
            ) : (
              <>
                <p className="mt-2 text-3xl font-semibold tracking-tight tabular-nums">
                  {hours === null ? "—" : number(hours)}
                  <span className="ml-1.5 text-base font-normal text-muted-foreground">
                    of {target === null ? "—" : number(target)} hours
                  </span>
                </p>
                {percent !== null && (
                  <div className="mt-3">
                    <div
                      role="progressbar"
                      aria-label="Certified internship hours"
                      aria-valuemin={0}
                      aria-valuemax={100}
                      aria-valuenow={Math.round(percent)}
                      className="h-2.5 overflow-hidden rounded-full bg-muted"
                    >
                      <div
                        className="h-full rounded-full bg-success transition-[width] duration-300"
                        style={{ width: `${percent}%` }}
                      />
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground">
                      {Math.round(percent)}% of required hours certified
                      {progress?.remaining_minutes !== null &&
                      progress?.remaining_minutes !== undefined
                        ? ` · ${number(progress.remaining_minutes / 60)} hours remaining`
                        : ""}
                      . Pending time logs are not included.
                    </p>
                  </div>
                )}
                {target === null && (
                  <p className="mt-2 text-xs text-muted-foreground">
                    Required hours are awaiting department confirmation.
                  </p>
                )}
              </>
            )}
          </Card>
          <KpiCard
            label="Required items completed"
            value={required ? `${finished} of ${required.length}` : null}
            icon={ClipboardCheck}
            tone="success"
            loading={loading}
            to="/student/requirements"
            hint="Approved documents and confirmed events"
          />
          <KpiCard
            label="Placement"
            value={
              placement
                ? placement.status.replaceAll("_", " ").replace(/^./, (c) => c.toUpperCase())
                : "Not placed"
            }
            icon={Briefcase}
            tone={placement ? statusTone(placement.status) : "muted"}
            loading={loading}
            to="/student/internship"
            hint={
              placement ? placement.host_establishment.name : "Your coordinator records placements"
            }
          />
        </div>
      </section>

      <section aria-labelledby="student-detail" className="grid items-start gap-4 xl:grid-cols-5">
        <h2 id="student-detail" className="sr-only">
          Requirements and recommendations
        </h2>
        <ChartCard
          className="xl:col-span-2"
          title="Required items by status"
          description="Required documents and events for this enrollment"
          loading={loading}
          error={failed.requirements}
          onRetry={() => setRetry((v) => v + 1)}
          isEmpty={!required?.length}
          emptyTitle="No required items configured yet"
          emptyDescription="Your coordinator sets the requirements for this cohort."
        >
          <DonutChartView
            title="Required items by status"
            slices={requirementSlices}
            totalLabel="required"
            unit="items"
          />
        </ChartCard>

        <Card className="xl:col-span-3">
          <SectionHeading
            title="Requirement checklist"
            description="Outstanding required items first"
            action={
              <Link
                to="/student/$section"
                params={{ section: "requirements" }}
                className={linkClass}
              >
                Open requirements
              </Link>
            }
          />
          {failed.requirements ? (
            <ErrorState
              title="Could not load requirements"
              message={failed.requirements}
              onRetry={() => setRetry((v) => v + 1)}
            />
          ) : loading ? (
            <LoadingBlock label="Loading requirements" rows={4} />
          ) : !rows?.length ? (
            <EmptyState compact title="No requirements configured for this cohort yet" />
          ) : (
            <>
              <ul className="divide-y divide-border">
                {shownRows.map((row) => (
                  <li key={row.id} className="flex items-start justify-between gap-3 py-2.5">
                    <div className="min-w-0">
                      <p className="text-sm font-medium">{row.requirement_type.name}</p>
                      <p className="mt-0.5 text-xs text-muted-foreground">
                        {row.is_required ? "Required" : "Optional"}
                        {row.due_at ? ` · due ${manila(row.due_at)}` : ""}
                      </p>
                    </div>
                    <Pill
                      tone={row.done ? "success" : row.status === "rejected" ? "brand" : "warn"}
                    >
                      {row.status.replaceAll("_", " ")}
                    </Pill>
                  </li>
                ))}
              </ul>
              {rows.length > 6 && (
                <p className="mt-3 text-xs text-muted-foreground">
                  Showing 6 of {rows.length} items.
                </p>
              )}
            </>
          )}
        </Card>
      </section>

      <section aria-labelledby="student-recs">
        <SectionHeading
          id="student-recs"
          title="Top recommendations"
          description="Your latest saved matches, ranked by similarity score"
          action={
            <Link
              to="/student/$section"
              params={{ section: "recommendations" }}
              className={linkClass}
            >
              All recommendations
            </Link>
          }
        />
        {failed.matches ? (
          <ErrorState
            title="Could not load recommendations"
            message={failed.matches}
            onRetry={() => setRetry((v) => v + 1)}
          />
        ) : loading ? (
          <Card>
            <LoadingBlock label="Loading recommendations" rows={3} />
          </Card>
        ) : !recommendations?.length ? (
          <EmptyState
            icon={ListOrdered}
            title="No saved recommendations yet"
            description="Recommendations use your competencies. Update them, then generate recommendations."
            action={
              <Link
                to="/student/$section"
                params={{ section: "recommendations" }}
                className={linkClass}
              >
                Go to recommendations
              </Link>
            }
          />
        ) : (
          <>
            <ul className="grid gap-3 md:grid-cols-3">
              {recommendations.slice(0, 3).map((item) => (
                <li key={item.id}>
                  <Link
                    to="/student/$section"
                    params={{ section: "recommendations" }}
                    className="block h-full rounded-xl border border-border bg-card p-4 shadow-xs transition-colors hover:border-brand/40 hover:bg-brand-soft/30"
                  >
                    <p className="text-xs font-medium text-muted-foreground">Rank {item.rank}</p>
                    <p className="mt-1 font-semibold">
                      {item.snapshot.host_name ?? "Host not recorded"}
                    </p>
                    <p className="mt-0.5 text-sm text-muted-foreground">
                      {item.snapshot.opportunity_title ?? "Internship opportunity"}
                    </p>
                    <dl className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                      <div>
                        <dt className="inline">
                          <SimilarityScoreLabel />{" "}
                        </dt>
                        <dd className="inline font-semibold text-foreground tabular-nums">
                          {formatSimilarity(item.similarity_score)}
                        </dd>
                      </div>
                      <div>
                        <dt className="sr-only">Distance</dt>
                        <dd className="inline">
                          {item.distance_km === null
                            ? "Distance not recorded"
                            : `${Number(item.distance_km).toFixed(2)} km straight-line`}
                        </dd>
                      </div>
                    </dl>
                  </Link>
                </li>
              ))}
            </ul>
            <p className="mt-3 text-xs text-muted-foreground">
              Saved {manila(recommendations[0]!.generated_at)}. Slots may have changed since then; a
              similarity score is not a placement decision.
            </p>
          </>
        )}
      </section>
    </>
  );
}
