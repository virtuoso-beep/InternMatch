import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { RANKING_LINE, TEST_MODE_LINE } from "@/lib/wording";
import { Card } from "./ui";

type Counts = { suitable: number; not_suitable: number; uncertain: number };
type Summary = {
  ranking: { mode: "similarity" | "learned"; test_mode: boolean };
  counts: { by_program: Record<string, Counts> };
};

/**
 * Coordinator, read only: how recommendations are ordered and the live judgment counts for their own
 * programs. Coordinators cannot update recommendations.
 */
export function RankingSummary() {
  const [summary, setSummary] = useState<Summary | null>(null);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    api<Summary>("/recommendation-learning")
      .then((data) => {
        if (active) setSummary(data);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, []);

  if (error) return null;
  if (!summary)
    return (
      <p role="status" className="mb-5 text-sm text-muted-foreground">
        Loading how recommendations are ordered…
      </p>
    );
  const programs = Object.entries(summary.counts.by_program);
  return (
    <Card className="mb-5">
      <div className="space-y-1 text-sm">
        <p className="font-medium">{RANKING_LINE[summary.ranking.mode]}</p>
        {summary.ranking.test_mode && <p className="text-muted-foreground">{TEST_MODE_LINE}</p>}
        {programs.length === 0 ? (
          <p className="text-muted-foreground">
            No coordinator judgments recorded for your programs yet.
          </p>
        ) : (
          programs.map(([program, counts]) => (
            <p key={program} className="tabular-nums text-muted-foreground">
              Judgments for {program}: Suitable {counts.suitable} · Not suitable{" "}
              {counts.not_suitable} · Uncertain {counts.uncertain}
            </p>
          ))
        )}
      </div>
    </Card>
  );
}
