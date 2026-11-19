import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button, Card, CardTitle, PageHeader } from "./ui";
import { ReportWorkspace } from "./ReportWorkspace";
import { withSynthetic } from "@/lib/synthetic";
import { SyntheticToggle } from "./SyntheticToggle";
import type { Data } from "./ProgramAnalytics";

export function CoordinatorAnalytics() {
  const [data, setData] = useState<Data | null>(null),
    [error, setError] = useState("");
  const [revision, setRevision] = useState(0);
  const [includeSynthetic, setIncludeSynthetic] = useState(false);
  useEffect(() => {
    let active = true;
    setError("");
    setData(null);
    api<Data>(withSynthetic("/program-analytics", includeSynthetic))
      .then((result) => {
        if (active) setData(result);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [revision, includeSynthetic]);
  const total = data?.programs.reduce((sum, p) => sum + p.enrollments, 0) ?? 0;
  const placed = data?.programs.reduce((sum, p) => sum + p.placed, 0) ?? 0;
  const completed = data?.programs.reduce((sum, p) => sum + p.completed, 0) ?? 0;
  return (
    <div className="space-y-6">
      <PageHeader
        title="Analytics"
        subtitle="Placement outcomes and straight-line distance across all recorded terms in your assigned programs."
        action={
          <div className="flex flex-wrap items-center gap-3">
            <SyntheticToggle
              checked={includeSynthetic}
              onChange={setIncludeSynthetic}
              disabled={!data && !error}
            />
            <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
              Refresh analytics
            </Button>
          </div>
        }
      />
      {error && <p role="alert">{error} Use Refresh analytics to retry.</p>}
      {!data && !error && <p role="status">Loading analytics…</p>}
      {data && (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {[
              ["Placement rate", total ? `${((placed / total) * 100).toFixed(1)}%` : "No records"],
              [
                "Completion rate",
                total ? `${((completed / total) * 100).toFixed(1)}%` : "No records",
              ],
              [
                "Median straight-line distance",
                data.equity.median_km === null ? "Not recorded" : `${data.equity.median_km} km`,
              ],
              ["Missing coordinates", String(data.equity.missing_coordinates)],
            ].map(([label, value]) => (
              <Card key={label}>
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="mt-2 text-3xl font-bold">{value}</p>
              </Card>
            ))}
          </div>
          <div className="grid gap-5 lg:grid-cols-2">
            <Card>
              <CardTitle>Placement rate by program</CardTitle>
              <p className="mb-4 text-xs text-muted-foreground">
                Approved, active or completed placements / enrollment records. Completion uses the
                same denominator.
              </p>
              {data.programs.length === 0 && <p>No assigned programs.</p>}
              <div className="space-y-4">
                {data.programs.map((p) => (
                  <div key={p.code}>
                    <div className="flex justify-between gap-3 text-sm">
                      <span>
                        {p.code} · {p.name}
                      </span>
                      <span>
                        {p.placed}/{p.enrollments}
                      </span>
                    </div>
                    {p.placement_rate === null ? (
                      <p className="text-xs">No enrollment records</p>
                    ) : (
                      <>
                        <progress
                          aria-label={`${p.code} placement rate`}
                          className="h-2 w-full accent-brand"
                          max={100}
                          value={p.placement_rate}
                        />
                        <p className="text-xs text-muted-foreground">
                          {p.placement_rate.toFixed(1)}%
                        </p>
                      </>
                    )}
                  </div>
                ))}
              </div>
            </Card>
            <Card>
              <CardTitle>Placements by distance band</CardTitle>
              <p className="mb-4 text-xs text-muted-foreground">
                {data.equity.measured_placements} approved/active placements with coordinates.
                Missing coordinates are excluded. Haversine kilometers, not travel time.
              </p>
              <div className="space-y-4">
                {data.equity.bands.map((b) => (
                  <div key={b.label}>
                    <div className="flex justify-between gap-3 text-sm">
                      <span>{b.label}</span>
                      <span>
                        {b.value}
                        {data.equity.measured_placements
                          ? ` · ${((b.value / data.equity.measured_placements) * 100).toFixed(1)}%`
                          : ""}
                      </span>
                    </div>
                    <progress
                      aria-label={b.label}
                      className="h-2 w-full accent-brand"
                      max={data.equity.measured_placements || 1}
                      value={b.value}
                    />
                  </div>
                ))}
              </div>
            </Card>
          </div>
          <p className="text-xs text-muted-foreground">
            Retrieved {new Date(data.generated_at).toLocaleString()}. Live analytics are separate
            from the saved report snapshots below.
          </p>
        </>
      )}
      <ReportWorkspace title="Report snapshots" headingLevel={2} />
    </div>
  );
}
