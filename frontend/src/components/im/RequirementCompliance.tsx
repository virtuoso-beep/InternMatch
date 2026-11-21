import { Button, Card, CardTitle } from "./ui";

/** Server-computed compliance across every enrollment in scope (GET /requirement-tracking `summary`). */
export type ComplianceSummary = {
  complete: number;
  pending: number;
  returned: number;
  missing: number;
  total: number;
  enrollments: number;
  types: Record<string, { total: number; approved: number }>;
};

export function RequirementCompliance({
  summary,
  onRefresh,
}: {
  summary: ComplianceSummary;
  onRefresh: () => void;
}) {
  const { complete, pending, missing, returned, total } = summary;
  return (
    <section className="mb-6 space-y-5" aria-label="Document compliance overview">
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {[
          ["Approved documents", complete],
          ["Under review", pending],
          ["Missing / needs action", missing + returned],
          [
            "Compliance rate",
            total ? `${Math.round((complete / total) * 100)}%` : "Not applicable",
          ],
        ].map(([label, value]) => (
          <Card key={label}>
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-2 text-3xl font-bold">{value}</p>
          </Card>
        ))}
      </div>
      <Card>
        <div className="flex flex-wrap items-start justify-between gap-3">
          <CardTitle>Compliance by document type</CardTitle>
          <Button variant="outline" onClick={onRefresh}>
            Refresh compliance
          </Button>
        </div>
        <p className="mb-4 text-xs text-muted-foreground">
          Latest revisions of required documents across {summary.enrollments} accessible enrollment
          records. Counts are documents, not students. {missing} missing; {returned} returned for
          correction. Events and journals are excluded.
        </p>
        {!total && <p>No required document records are configured.</p>}
        <div className="space-y-4">
          {Object.entries(summary.types).map(([name, count]) => (
            <div key={name}>
              <div className="mb-1 flex justify-between gap-3 text-sm">
                <span>{name}</span>
                <span>
                  {count.approved}/{count.total} approved ·{" "}
                  {Math.round((count.approved / count.total) * 100)}%
                </span>
              </div>
              <progress
                className="h-2 w-full accent-brand"
                aria-label={`${name} compliance`}
                max={count.total}
                value={count.approved}
              />
            </div>
          ))}
        </div>
      </Card>
    </section>
  );
}
