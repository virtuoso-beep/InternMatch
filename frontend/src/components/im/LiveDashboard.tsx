import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { ROLES } from "@/lib/internmatch";
import { useSessionUser } from "./SessionGuard";
import { Button, Card, Pill } from "./ui";
import { DashboardEmpty, DashboardHeading, DashboardLink, DashboardMetric } from "./DashboardUI";

type Dashboard = {
  stats: { label: string; value: number }[];
  programs: { id: number; code: string }[];
  program_breakdown: { code: string; enrollments: number }[];
  generated_at: string;
};
type Action = { title: string; description: string; path: string };
const layouts: Record<string, { intro: string; metrics: string[]; actions: Action[] }> = {
  coordinator: {
    intro: "Keep placement reviews, student requirements, and internship progress moving.",
    metrics: [
      "Enrollment records",
      "Active placements",
      "Submissions awaiting review",
      "Certified hours",
    ],
    actions: [
      {
        title: "Review placement proposals",
        description: "Check readiness and confirm final placements.",
        path: "approvals",
      },
      {
        title: "Review requirements",
        description: "Review documents and confirm event attendance.",
        path: "requirements",
      },
      {
        title: "Monitor internship progress",
        description: "Follow certified hours, journals, and attention flags.",
        path: "progress",
      },
    ],
  },
  supervisor: {
    intro: "Support your assigned interns and keep their work records up to date.",
    metrics: [
      "Active placements",
      "Time logs awaiting verification",
      "Certified hours",
      "Submitted evaluations",
    ],
    actions: [
      {
        title: "Verify internship hours",
        description: "Certify submitted time or return it for correction.",
        path: "attendance",
      },
      {
        title: "Complete evaluations",
        description: "Review performance using the configured rubric.",
        path: "evaluations",
      },
      {
        title: "Manage opportunities",
        description: "Keep your internship tasks and program slots current.",
        path: "opportunities",
      },
    ],
  },
  dean: {
    intro: "A clear view of internship participation and outcomes across your assigned programs.",
    metrics: ["Enrollment records", "Active placements", "Completed placements", "Certified hours"],
    actions: [
      {
        title: "View approved reports",
        description: "Read coordinator-approved program snapshots.",
        path: "accreditation",
      },
      {
        title: "Review program performance",
        description: "Explore outcomes within your assigned scope.",
        path: "performance",
      },
      {
        title: "Explore placement equity",
        description: "Review placement distribution and accessibility.",
        path: "equity",
      },
    ],
  },
  admin: {
    intro: "Manage system access and keep the academic and host registries ready for use.",
    metrics: [
      "Active accounts (system-wide)",
      "Active hosts (system-wide)",
      "Published opportunities (system-wide)",
      "Enrollment records",
    ],
    actions: [
      {
        title: "Manage user access",
        description: "Review existing accounts and their assigned scope.",
        path: "users",
      },
      {
        title: "Manage academic records",
        description: "Maintain program requirements, terms, and enrollments.",
        path: "programs",
      },
      {
        title: "Inspect audit trail",
        description: "Trace recorded changes and the people who made them.",
        path: "audit",
      },
    ],
  },
};

export function LiveDashboard() {
  const user = useSessionUser();
  const layout = layouts[user.role];
  const [data, setData] = useState<Dashboard | null>(null);
  const [program, setProgram] = useState("");
  const [error, setError] = useState("");
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    setError("");
    setData(null);
    api<Dashboard>(`/dashboard${program ? `?program_id=${program}` : ""}`)
      .then((value) => {
        if (active) setData(value);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [program, revision]);
  if (!layout) return null;
  const base = ROLES[user.role].base;
  const metric = (label: string) => data?.stats.find((s) => s.label === label)?.value;
  const total = data?.program_breakdown.reduce((sum, p) => sum + Number(p.enrollments), 0) ?? 0;
  const additional = data?.stats.filter((s) => !layout.metrics.includes(s.label));
  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="mb-2 text-xs font-semibold uppercase tracking-widest text-brand">
            {ROLES[user.role].title} overview
          </p>
          <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
            Welcome back, {user.name.split(" ")[0]}
          </h1>
          <p className="mt-2 max-w-2xl text-sm leading-relaxed text-muted-foreground">
            {layout.intro}
          </p>
        </div>
        <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
          Refresh dashboard
        </Button>
      </div>
      <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-card px-5 py-3">
        <label className="text-sm font-medium">
          Program
          <select
            value={program}
            onChange={(e) => setProgram(e.target.value)}
            className="ml-3 rounded-lg border border-input bg-background px-3 py-2 text-sm"
            disabled={!data && !error}
          >
            <option value="">All accessible programs</option>
            {data?.programs.map((p) => (
              <option key={p.id} value={p.id}>
                {p.code}
              </option>
            ))}
            {!data && program && <option value={program}>Selected program</option>}
          </select>
        </label>
        <p className="text-xs text-muted-foreground">
          {data
            ? `Updated ${new Date(data.generated_at).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}`
            : "Records within your authorized scope"}
        </p>
      </div>
      {error && (
        <p role="alert" className="rounded-lg bg-brand-soft p-4 text-sm text-brand">
          Unable to load dashboard. {error} Use Refresh dashboard to retry.
        </p>
      )}
      {!data && !error && (
        <p role="status" className="py-6 text-muted-foreground">
          Loading your overview…
        </p>
      )}
      {data && (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {layout.metrics.map((label, index) => (
              <DashboardMetric
                key={label}
                label={label}
                value={metric(label)?.toLocaleString() ?? "—"}
                hint={
                  label.includes("system-wide")
                    ? "Across the system, independent of program filter"
                    : label === "Certified hours"
                      ? "Verified time logs only"
                      : program
                        ? "Within the selected program"
                        : "Across your accessible programs"
                }
                index={index}
                accent={label === "Certified hours" || label === "Completed placements"}
              />
            ))}
          </div>
          <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            <Card className="!rounded-xl !p-6">
              <DashboardHeading
                title="Enrollment by program"
                description="Enrollment records in the current scope"
                action={<Pill tone="brand">{total.toLocaleString()} total</Pill>}
              />
              {data.program_breakdown.length === 0 ? (
                <DashboardEmpty>
                  No enrollment records match this scope. Select another accessible program to
                  explore its records.
                </DashboardEmpty>
              ) : (
                <ul className="space-y-5">
                  {data.program_breakdown.map((p) => (
                    <li key={p.code}>
                      <div className="mb-2 flex items-center justify-between gap-3 text-sm">
                        <span className="font-semibold">{p.code}</span>
                        <span className="text-muted-foreground">
                          {Number(p.enrollments).toLocaleString()} enrollments{" "}
                          <span className="ml-2 font-semibold text-foreground">
                            {total ? Math.round((Number(p.enrollments) / total) * 100) : 0}%
                          </span>
                        </span>
                      </div>
                      <div className="h-2 overflow-hidden rounded-full bg-muted" aria-hidden="true">
                        <div
                          className="h-full rounded-full bg-brand/80"
                          style={{ width: `${total ? (Number(p.enrollments) / total) * 100 : 0}%` }}
                        />
                      </div>
                    </li>
                  ))}
                </ul>
              )}
              <p className="mt-6 border-t border-border pt-4 text-xs leading-relaxed text-muted-foreground">
                A student can have more than one enrollment. Counts represent enrollment records,
                not unique people.
              </p>
            </Card>
            <Card className="!rounded-xl !p-6">
              <DashboardHeading
                title="Your workspace"
                description="Go directly to the tasks for your role"
              />
              <div className="space-y-3">
                {layout.actions.map((action, index) => (
                  <a
                    key={action.path}
                    href={`${base}/${action.path}`}
                    className="group flex items-start gap-3 rounded-lg border border-border p-4 transition-colors hover:border-brand/30 hover:bg-brand-soft/30 focus-visible:outline-2 focus-visible:outline-brand"
                  >
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-xs font-bold text-brand">
                      0{index + 1}
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-semibold group-hover:text-brand">{action.title}</p>
                      <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                        {action.description}
                      </p>
                    </div>
                    <span aria-hidden="true" className="text-brand">
                      →
                    </span>
                  </a>
                ))}
              </div>
            </Card>
          </div>
          <Card className="!rounded-xl !p-6">
            <DashboardHeading
              title="More about your scope"
              description="Additional recorded activity and placement figures"
              action={<DashboardLink href={`${base}/notifications`}>Notifications</DashboardLink>}
            />
            <dl className="grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
              {additional?.map((stat) => (
                <div
                  key={stat.label}
                  className="flex items-start justify-between gap-4 border-b border-border pb-4"
                >
                  <dt className="text-sm leading-relaxed text-muted-foreground">{stat.label}</dt>
                  <dd className="text-xl font-bold tabular-nums">{stat.value.toLocaleString()}</dd>
                </div>
              ))}
            </dl>
            <p className="mt-4 text-xs leading-relaxed text-muted-foreground">
              {user.role === "admin"
                ? "Account, host, and published-opportunity totals are system-wide. Placement approval belongs to practicum coordinators."
                : user.role === "dean"
                  ? "Operational summaries reflect your assigned programs. Individual report snapshots become available after coordinator approval."
                  : "Placement status and certified hours reflect saved records. An approved placement is distinct from an active internship."}
            </p>
          </Card>
        </>
      )}
    </div>
  );
}
