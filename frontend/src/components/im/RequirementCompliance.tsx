import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button, Card, CardTitle } from "./ui";

type SummaryData = {
  requirements: {
    id: number;
    is_required: boolean;
    requirement_type: { name: string; kind: string };
  }[];
  submissions: { program_term_requirement_id: number; revision: number; status: string }[];
};
export function RequirementCompliance({ enrollments }: { enrollments: { id: number }[] }) {
  const [data, setData] = useState<SummaryData[] | null>(null),
    [error, setError] = useState("");
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    setData(null);
    setError("");
    async function load() {
      const results: SummaryData[] = [];
      for (let i = 0; i < enrollments.length; i += 5) {
        results.push(
          ...(await Promise.all(
            enrollments
              .slice(i, i + 5)
              .map((e) => api<SummaryData>(`/enrollments/${e.id}/requirements`)),
          )),
        );
        if (!active) return;
      }
      if (active) setData(results);
    }
    void load().catch((cause: Error) => {
      if (active) setError(cause.message);
    });
    return () => {
      active = false;
    };
  }, [enrollments, revision]);
  if (error)
    return (
      <Card>
        <p role="alert">Compliance summary unavailable: {error}</p>
        <Button onClick={() => setRevision((v) => v + 1)}>Retry summary</Button>
      </Card>
    );
  if (!data) return <p role="status">Loading document compliance…</p>;
  let complete = 0,
    pending = 0,
    missing = 0,
    returned = 0;
  const types = new Map<string, { total: number; approved: number }>();
  for (const record of data) {
    for (const requirement of record.requirements.filter(
      (r) => r.is_required && r.requirement_type.kind === "document",
    )) {
      const latest = record.submissions
        .filter((s) => s.program_term_requirement_id === requirement.id)
        .sort((a, b) => b.revision - a.revision)[0];
      const type = types.get(requirement.requirement_type.name) ?? { total: 0, approved: 0 };
      type.total++;
      if (latest?.status === "approved") {
        complete++;
        type.approved++;
      } else if (latest?.status === "submitted" || latest?.status === "pending") {
        pending++;
      } else if (latest) {
        returned++;
      } else {
        missing++;
      }
      types.set(requirement.requirement_type.name, type);
    }
  }
  const total = complete + pending + missing + returned;
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
          <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
            Refresh compliance
          </Button>
        </div>
        <p className="mb-4 text-xs text-muted-foreground">
          Latest revisions of required documents across {enrollments.length} accessible enrollment
          records. Counts are documents, not students. {missing} missing; {returned} other
          submissions need action. Events and journals are excluded.
        </p>
        {!total && <p>No required document records are configured.</p>}
        <div className="space-y-4">
          {[...types.entries()]
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([name, count]) => (
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
