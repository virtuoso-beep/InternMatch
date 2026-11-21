import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Card, CardTitle, Pill } from "./ui";

type Status = {
  active: boolean;
  ranking_method: "logistic_regression" | "cosine_then_distance";
  reason: string | null;
  reranker_setting: string;
  judgments: { total: number; approved: number };
  card: {
    trained_at?: string;
    n_train?: number;
    n_test?: number;
    threshold_f1?: number;
    metrics?: { f1?: number; precision?: number; recall?: number; roc_auc?: number };
  } | null;
};

/**
 * Which method ranks recommendations right now. Logistic Regression is used only when a model built from
 * real, approved coordinator judgments has passed evaluation; until then recommendations use cosine
 * similarity, and this panel says why.
 */
export function RankingStatus() {
  const [status, setStatus] = useState<Status | null>(null);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    api<Status>("/matching/status")
      .then((data) => {
        if (active) setStatus(data);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, []);

  const f1 = status?.card?.metrics?.f1;
  return (
    <Card className="mb-5">
      <CardTitle
        right={
          status && (
            <Pill tone={status.active ? "success" : "muted"}>
              {status.active ? "Logistic Regression" : "Cosine similarity"}
            </Pill>
          )
        }
      >
        Ranking method
      </CardTitle>
      {error && <p className="text-sm text-destructive">{error}</p>}
      {!status && !error && <p className="text-sm text-muted-foreground">Checking the ranker…</p>}
      {status && (
        <div className="space-y-2 text-sm">
          <p>
            {status.active
              ? "Recommendations are ranked by a Logistic Regression suitability model trained on approved coordinator judgments. The similarity score is still shown for each recommendation."
              : "Recommendations are ranked by cosine similarity, with known distance as the tie-breaker."}
          </p>
          {!status.active && status.reason && (
            <p className="text-muted-foreground">Why: {status.reason}</p>
          )}
          {status.active && status.card && (
            <p className="text-muted-foreground">
              Trained on {status.card.n_train ?? "?"} judgments, evaluated on{" "}
              {status.card.n_test ?? "?"}
              {typeof f1 === "number" ? `, F1 ${f1.toFixed(2)}` : ""}
              {typeof status.card.threshold_f1 === "number"
                ? ` (required ${status.card.threshold_f1.toFixed(2)})`
                : ""}
              .
            </p>
          )}
          <p className="text-muted-foreground">
            Coordinator judgments recorded: {status.judgments.total} (approved{" "}
            {status.judgments.approved}). A model can be trained once enough approved judgments
            exist; synthetic test labels are never used for real placements.
          </p>
        </div>
      )}
    </Card>
  );
}
