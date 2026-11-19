import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { AccessibilityMap } from "./AccessibilityMap";
import { withSynthetic } from "@/lib/synthetic";
import { SyntheticToggle } from "./SyntheticToggle";
import { Button, Card, CardTitle, PageHeader, Row, StatCard, StatGrid, Table } from "./ui";

type Band = { label: string; value: number };
type Summary = {
  enrollments: number;
  placed: number;
  completed: number;
  placement_rate: number | null;
  completion_rate: number | null;
};
export type Data = {
  programs: (Summary & {
    code: string;
    name: string;
    evaluation_percent: number | null;
    evaluation_count: number;
  })[];
  hosts: {
    id: number;
    name: string;
    industry: string;
    city: string;
    is_active: boolean;
    current_agreements: number;
    agreements_expiring_90_days: number;
  }[];
  equity: {
    median_km: number | null;
    measured_placements: number;
    missing_coordinates: number;
    over_20_km: number;
    bands: Band[];
  };
  similarity_bands: Band[];
  competency_demand: Band[];
  terms: (Summary & { term: string })[];
  generated_at: string;
};
const percent = (v: number | null) => (v === null ? "No records" : `${v.toFixed(1)}%`);

export function ProgramAnalytics({
  section,
}: {
  section: "performance" | "hosts" | "equity" | "analytics";
}) {
  const [data, setData] = useState<Data | null>(null),
    [error, setError] = useState("");
  const [revision, setRevision] = useState(0),
    [program, setProgram] = useState(""),
    [includeSynthetic, setIncludeSynthetic] = useState(false);
  useEffect(() => {
    let active = true;
    setData(null);
    setError("");
    api<Data>(withSynthetic("/program-analytics", includeSynthetic))
      .then((d) => {
        if (active) setData(d);
      })
      .catch((e: Error) => {
        if (active) setError(e.message);
      });
    return () => {
      active = false;
    };
  }, [revision, includeSynthetic]);
  const [hostSearch, setHostSearch] = useState("");
  const [hostFilter, setHostFilter] = useState("all");
  const visibleHosts =
    data?.hosts.filter(
      (h) =>
        `${h.name} ${h.industry} ${h.city}`
          .toLowerCase()
          .includes(hostSearch.trim().toLowerCase()) &&
        (hostFilter === "all" ||
          (hostFilter === "expiring"
            ? h.agreements_expiring_90_days > 0
            : hostFilter === "uncovered"
              ? h.current_agreements === 0
              : h.is_active)),
    ) ?? [];
  const titles = {
    performance: "Program performance",
    hosts: "Host establishments",
    equity: "Placement equity",
    analytics: "Analytics",
  };
  return (
    <>
      <PageHeader
        title={titles[section]}
        subtitle="Live records across recorded terms in your assigned programs."
        action={
          <div className="flex flex-wrap items-center gap-3">
            <SyntheticToggle
              checked={includeSynthetic}
              onChange={setIncludeSynthetic}
              disabled={!data && !error}
            />
            <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
              Refresh program records
            </Button>
          </div>
        }
      />
      {error && <p role="alert">{error}</p>}
      {!data && !error && <p role="status">Loading program records…</p>}
      {data && (
        <>
          {section === "performance" && (
            <>
              <div className="mb-5 flex flex-wrap gap-3">
                <label className="text-sm">
                  Program
                  <select
                    aria-label="Program"
                    className="ml-2 rounded border p-2"
                    value={program}
                    onChange={(e) => setProgram(e.target.value)}
                  >
                    <option value="">All assigned programs</option>
                    {data.programs.map((p) => (
                      <option key={p.code}>{p.code}</option>
                    ))}
                  </select>
                </label>
                <Button variant="outline" onClick={() => setProgram("")}>
                  Clear filter
                </Button>
              </div>
              <div className="mb-5 grid gap-5 lg:grid-cols-2">
                {(["placement_rate", "completion_rate"] as const).map((metric) => (
                  <Card key={metric}>
                    <CardTitle>
                      {metric === "placement_rate" ? "Placement rate" : "Completion rate"}
                    </CardTitle>
                    <div className="space-y-4">
                      {data.programs
                        .filter((p) => !program || p.code === program)
                        .map((p) => (
                          <div key={p.code}>
                            <div className="mb-1 flex justify-between gap-3 text-sm">
                              <span>{p.code}</span>
                              <span>{percent(p[metric])}</span>
                            </div>
                            {p[metric] !== null && (
                              <progress
                                aria-label={`${p.code} ${metric.replaceAll("_", " ")}`}
                                className="h-2 w-full accent-brand"
                                max={100}
                                value={p[metric] ?? 0}
                              />
                            )}
                          </div>
                        ))}
                      {data.programs.length === 0 && <p>No assigned programs.</p>}
                    </div>
                  </Card>
                ))}
              </div>
              <Card>
                <CardTitle>Enrollment outcomes</CardTitle>
                <p className="mb-4 text-sm">
                  Counts represent enrollment records across terms. Placed includes approved, active
                  and completed placements. Evaluation averages include only submitted, scored
                  evaluations.
                </p>
                <Table
                  head={[
                    "Program",
                    "Enrollments",
                    "Placed",
                    "Completed",
                    "Placement rate",
                    "Completion rate",
                    "Evaluation average",
                    "Evaluations",
                  ]}
                >
                  {data.programs
                    .filter((p) => !program || p.code === program)
                    .map((p) => (
                      <Row key={p.code}>
                        <td>
                          {p.code} · {p.name}
                        </td>
                        <td>{p.enrollments}</td>
                        <td>{p.placed}</td>
                        <td>{p.completed}</td>
                        <td>{percent(p.placement_rate)}</td>
                        <td>{percent(p.completion_rate)}</td>
                        <td>{percent(p.evaluation_percent)}</td>
                        <td>{p.evaluation_count}</td>
                      </Row>
                    ))}
                </Table>
                {data.programs.length === 0 && <p>No programs assigned.</p>}
              </Card>
            </>
          )}
          {section === "hosts" && (
            <>
              <div className="mb-5 flex flex-wrap gap-3">
                <label className="text-sm">
                  Search hosts
                  <input
                    type="search"
                    className="ml-2 rounded border p-2"
                    value={hostSearch}
                    onChange={(e) => setHostSearch(e.target.value)}
                  />
                </label>
                <label className="text-sm">
                  Coverage
                  <select
                    aria-label="Host coverage"
                    className="ml-2 rounded border p-2"
                    value={hostFilter}
                    onChange={(e) => setHostFilter(e.target.value)}
                  >
                    <option value="all">All hosts</option>
                    <option value="active">Active hosts</option>
                    <option value="expiring">Agreements expiring within 90 days</option>
                    <option value="uncovered">No current agreement</option>
                  </select>
                </label>
                <Button
                  variant="outline"
                  onClick={() => {
                    setHostSearch("");
                    setHostFilter("all");
                  }}
                >
                  Clear host filters
                </Button>
              </div>
              <p className="mb-3 text-xs text-muted-foreground">
                {visibleHosts.length} of {data.hosts.length} hosts. Summary totals cover all
                accessible hosts.
              </p>
              <StatGrid>
                <StatCard
                  label="Accessible active hosts"
                  value={String(data.hosts.filter((h) => h.is_active).length)}
                />
                <StatCard
                  label="Current scoped agreements"
                  value={String(data.hosts.reduce((n, h) => n + h.current_agreements, 0))}
                />
                <StatCard
                  label="Agreements expiring within 90 days"
                  value={String(data.hosts.reduce((n, h) => n + h.agreements_expiring_90_days, 0))}
                />
              </StatGrid>
              <Card>
                <Table
                  head={[
                    "Establishment",
                    "Industry",
                    "City",
                    "Host status",
                    "Current scoped agreements",
                    "Expiring within 90 days",
                  ]}
                >
                  {visibleHosts.map((h) => (
                    <Row key={h.id}>
                      <td>{h.name}</td>
                      <td>{h.industry}</td>
                      <td>{h.city}</td>
                      <td>{h.is_active ? "Active" : "Inactive"}</td>
                      <td>{h.current_agreements || "No current agreement"}</td>
                      <td>{h.agreements_expiring_90_days}</td>
                    </Row>
                  ))}
                </Table>
                {visibleHosts.length === 0 && <p>No hosts match these filters.</p>}
              </Card>
            </>
          )}
          {section === "equity" && (
            <>
              <StatGrid>
                <StatCard
                  label="Median straight-line distance"
                  value={
                    data.equity.median_km === null ? "Not recorded" : `${data.equity.median_km} km`
                  }
                />
                <StatCard
                  label="Measured current placements"
                  value={String(data.equity.measured_placements)}
                />
                <StatCard
                  label="Current placements over 20 km"
                  value={String(data.equity.over_20_km)}
                />
                <StatCard
                  label="Placements missing coordinates"
                  value={String(data.equity.missing_coordinates)}
                />
              </StatGrid>
              <Card>
                <CardTitle>Current approved and active placements</CardTitle>
                <Counts values={data.equity.bands} />
                <p className="mt-3 text-sm">
                  Distances use stored coordinates and Haversine. They do not measure road distance,
                  travel time or fairness.
                </p>
              </Card>
              <div className="mt-5">
                <AccessibilityMap note="Host locations within your assigned programs. Student addresses are not exposed in this view." />
              </div>
            </>
          )}
          {section === "analytics" && (
            <div className="grid gap-5 lg:grid-cols-2">
              <Card>
                <CardTitle>Stored recommendation snapshots</CardTitle>
                <Counts values={data.similarity_bands} />
                <p className="mt-3 text-sm">
                  Cosine similarity bands include retained generations. These scores are not
                  placement probabilities.
                </p>
              </Card>
              <Card>
                <CardTitle>Placement outcomes by term</CardTitle>
                <div className="space-y-4">
                  {data.terms.map((t) => (
                    <div key={t.term}>
                      <div className="mb-1 flex justify-between gap-3 text-sm">
                        <span>{t.term}</span>
                        <span>{percent(t.placement_rate)}</span>
                      </div>
                      {t.placement_rate !== null && (
                        <progress
                          aria-label={`${t.term} placement rate`}
                          className="h-2 w-full accent-brand"
                          max={100}
                          value={t.placement_rate}
                        />
                      )}
                      <p className="text-xs text-muted-foreground">
                        {t.placed} placed / {t.enrollments} enrollment records
                      </p>
                    </div>
                  ))}
                </div>{" "}
                {data.terms.length === 0 && <p>No recorded terms.</p>}
              </Card>
              <Card className="lg:col-span-2">
                <CardTitle>Required competencies in published opportunities</CardTitle>
                <Counts values={data.competency_demand} distribution={false} />
              </Card>
            </div>
          )}
          <p className="mt-4 text-xs text-muted-foreground">
            Retrieved {new Date(data.generated_at).toLocaleString()}
          </p>
        </>
      )}
    </>
  );
}

function Counts({ values, distribution = true }: { values: Band[]; distribution?: boolean }) {
  const total = values.reduce((sum, value) => sum + value.value, 0);
  const max = distribution ? total : Math.max(0, ...values.map((value) => value.value));
  return !total ? (
    <p>No records available.</p>
  ) : (
    <div className="space-y-4">
      <p className="text-xs text-muted-foreground">
        {distribution
          ? `${total} records with available measurements. Percentages use this total.`
          : "Counts of published opportunities requiring each competency. Bars are scaled to the highest count; competencies can overlap."}
      </p>
      {values.map((value) => (
        <div key={value.label}>
          <div className="mb-1 flex justify-between gap-3 text-sm">
            <span>{value.label}</span>
            <span>
              {value.value}
              {distribution ? ` · ${((value.value / total) * 100).toFixed(1)}%` : " opportunities"}
            </span>
          </div>
          <progress
            aria-label={value.label}
            className="h-2 w-full accent-brand"
            max={max || 1}
            value={value.value}
          />
        </div>
      ))}
      <details>
        <summary className="cursor-pointer text-sm font-semibold text-brand">
          View exact counts
        </summary>
        <Table head={["Category", "Records"]}>
          {values.map((value) => (
            <Row key={value.label}>
              <td>{value.label}</td>
              <td>{value.value}</td>
            </Row>
          ))}
        </Table>
      </details>
    </div>
  );
}
