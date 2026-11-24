import { useCallback, useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { RANKING_LINE, TEST_MODE_LINE } from "@/lib/wording";
import { Button, Card, CardTitle, ConfirmDialog, Pill, formatDateTime } from "./ui";

type Counts = { suitable: number; not_suitable: number; uncertain: number };
type Scores = { accuracy: number; precision: number; recall: number; f1: number; n: number };
type Evaluation = { overall: Scores; by_program: Record<string, Scores>; students: number };
type Ranking = { precision_at_3: number; recall_at_3: number; ndcg_at_3: number };
type Fallback = {
  students: number;
  similarity: Ranking;
  keyword_overlap: Ranking;
  distance_only: Ranking;
} | null;
type Run = {
  id: number;
  started_at: string;
  result: "updated" | "not_enough_data" | "not_reliable" | "failed";
  used_test_data: boolean;
  suitable: number;
  not_suitable: number;
  reason?: string | null;
  metrics?: { model: Evaluation; similarity_only: Evaluation } | null;
};
type Status = {
  ranking: { mode: "similarity" | "learned"; test_mode: boolean };
  counts: { overall: Counts; by_program: Record<string, Counts>; required: number };
  test_mode_available: boolean;
  last_run: Run | null;
  history: Run[];
  technical: {
    status: string;
    reason: string | null;
    model: {
      trained_at?: string;
      n_train?: number;
      programs?: string[];
      synthetic?: boolean;
      metrics?: { model: Evaluation; similarity_only: Evaluation };
    } | null;
    fallback: Fallback;
  };
};

const RESULT: Record<Run["result"], string> = {
  updated: "Updated",
  not_enough_data: "Not enough judgments",
  not_reliable: "Not reliable enough",
  failed: "Could not run",
};

const fixed = (value: number | undefined) => (typeof value === "number" ? value.toFixed(2) : "—");

/**
 * What happened, in plain words. The message follows real coordinator judgments: a practice run's result is
 * shown only right after the administrator runs one.
 */
function message(status: Status, run: Run | null): string {
  const { overall, required } = status.counts;
  const enough = overall.suitable >= required && overall.not_suitable >= required;
  if (!enough && !run?.used_test_data)
    return "Recommendations use skills match and distance for now. They'll also learn from coordinator judgments once there are enough.";
  if (run?.result === "updated") return "Recommendations updated.";
  if (run?.result === "not_reliable")
    return "Recommendations weren't updated. The test showed they wouldn't be reliable enough yet. The current recommendations stay as they are.";
  if (run?.result === "failed")
    return "Recommendations weren't updated because the update couldn't run. The current recommendations stay as they are. Try again later.";
  if (run?.result === "not_enough_data")
    return "Recommendations weren't updated. There aren't enough judgments yet. The current recommendations stay as they are.";
  return "There are enough coordinator judgments to update recommendations.";
}

/**
 * Administrator card "Recommendation learning" (spec §9). Plain wording on the card; method names and metrics
 * appear only under "Technical details".
 */
export function RecommendationLearning() {
  const [status, setStatus] = useState<Status | null>(null);
  const [error, setError] = useState("");
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [runError, setRunError] = useState("");
  const [practice, setPractice] = useState(false);
  const [justRan, setJustRan] = useState<Run | null>(null);

  const load = useCallback(() => {
    api<Status>("/recommendation-learning")
      .then(setStatus)
      .catch((cause: Error) => setError(cause.message));
  }, []);
  useEffect(load, [load]);

  if (error)
    return (
      <Card className="mb-5">
        <p className="text-sm text-destructive">{error}</p>
      </Card>
    );
  if (!status)
    return (
      <Card className="mb-5">
        <p role="status" className="text-sm text-muted-foreground">
          Loading recommendation learning…
        </p>
      </Card>
    );

  const { overall, required } = status.counts;
  const run = justRan ?? status.history.find((item) => !item.used_test_data) ?? null;
  const lastRun = justRan ?? status.last_run;
  const model = status.technical.model?.metrics;
  return (
    <Card className="mb-5">
      <CardTitle
        right={
          <Pill tone={status.ranking.mode === "learned" ? "success" : "muted"}>
            {status.ranking.mode === "learned"
              ? "Learning from judgments"
              : "Skills match and distance"}
          </Pill>
        }
      >
        Recommendation learning
      </CardTitle>
      <div className="space-y-3 text-sm">
        <p role="status">{message(status, run)}</p>
        <p className="font-medium tabular-nums">
          Suitable: {overall.suitable} of {required} · Not suitable: {overall.not_suitable} of{" "}
          {required}
        </p>
        <p className="text-muted-foreground">
          {RANKING_LINE[status.ranking.mode]}.{status.ranking.test_mode ? ` ${TEST_MODE_LINE}` : ""}
        </p>
        <div className="flex flex-wrap items-center gap-4">
          <Button
            onClick={() => {
              setRunError("");
              setOpen(true);
            }}
            disabled={busy}
          >
            {busy ? "Updating recommendations…" : "Update recommendations"}
          </Button>
          {status.test_mode_available && (
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={practice}
                onChange={(event) => setPractice(event.target.checked)}
              />
              Use practice data
            </label>
          )}
        </div>
      </div>

      <ConfirmDialog
        open={open}
        title="Update recommendations?"
        description="This checks the coordinator judgments collected so far and updates how recommendations are ordered, but only if the result is reliable enough. Existing recommendations are not changed."
        confirmLabel="Update"
        tone="primary"
        busy={busy}
        error={runError || undefined}
        onCancel={() => setOpen(false)}
        onConfirm={async () => {
          if (busy) return;
          setBusy(true);
          setRunError("");
          try {
            const result = await mutate<{ data: Run }>("/recommendation-learning/update", {
              test_data: practice,
            });
            setJustRan(result.data);
            setOpen(false);
            load();
          } catch (cause) {
            setRunError(cause instanceof Error ? cause.message : "The update could not start.");
          } finally {
            setBusy(false);
          }
        }}
      />

      {status.history.length > 0 && (
        <div className="mt-6">
          <h3 className="mb-2 text-sm font-semibold">Past updates</h3>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[420px] text-left text-sm">
              <thead>
                <tr className="border-b text-xs text-muted-foreground">
                  <th scope="col" className="py-2 pr-4">
                    Date
                  </th>
                  <th scope="col" className="py-2 pr-4">
                    Judgments used
                  </th>
                  <th scope="col" className="py-2">
                    Result
                  </th>
                </tr>
              </thead>
              <tbody>
                {status.history.map((item) => (
                  <tr key={item.id} className="border-b border-border/70 last:border-0">
                    <td className="py-2 pr-4">{formatDateTime(item.started_at)}</td>
                    <td className="py-2 pr-4 tabular-nums">
                      {item.suitable + item.not_suitable}
                      {item.used_test_data ? " (practice data)" : ""}
                    </td>
                    <td className="py-2">{RESULT[item.result]}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      <details className="mt-6 rounded-lg border border-border px-4 py-3 text-sm">
        <summary className="cursor-pointer font-semibold">Technical details</summary>
        <div className="mt-3 space-y-4">
          <dl className="grid gap-x-6 gap-y-1 sm:grid-cols-[max-content_1fr]">
            <dt className="text-muted-foreground">Method in use</dt>
            <dd>{status.technical.status}</dd>
            {status.technical.reason && (
              <>
                <dt className="text-muted-foreground">Why</dt>
                <dd>{status.technical.reason}</dd>
              </>
            )}
            <dt className="text-muted-foreground">Rule</dt>
            <dd>
              Train only with at least {required} suitable and {required} not-suitable judgments;
              evaluate with leave-one-student-out cross-validation; install only if F1 is better
              than similarity alone and every program has both labels.
            </dd>
            <dt className="text-muted-foreground">Uncertain judgments (excluded)</dt>
            <dd className="tabular-nums">{overall.uncertain}</dd>
            {status.technical.model?.trained_at && (
              <>
                <dt className="text-muted-foreground">Installed model</dt>
                <dd>
                  Logistic Regression trained {formatDateTime(status.technical.model.trained_at)} on{" "}
                  {status.technical.model.n_train} pairs
                  {status.technical.model.synthetic ? " (practice data)" : ""}
                </dd>
              </>
            )}
            {lastRun?.reason && (
              <>
                <dt className="text-muted-foreground">Last run</dt>
                <dd>
                  {lastRun.used_test_data ? "(practice data) " : ""}
                  {lastRun.reason}
                </dd>
              </>
            )}
          </dl>

          {(model ?? lastRun?.metrics) && (
            <EvaluationTable metrics={(model ?? lastRun?.metrics)!} />
          )}

          <div>
            <h4 className="mb-1 font-semibold">
              Fallback evaluation (Precision@3, Recall@3, NDCG@3)
            </h4>
            {status.technical.fallback ? (
              <div className="overflow-x-auto">
                <table className="w-full min-w-[420px] text-left tabular-nums">
                  <thead>
                    <tr className="border-b text-xs text-muted-foreground">
                      <th scope="col" className="py-1 pr-4">
                        Method
                      </th>
                      <th scope="col" className="py-1 pr-4">
                        P@3
                      </th>
                      <th scope="col" className="py-1 pr-4">
                        R@3
                      </th>
                      <th scope="col" className="py-1">
                        NDCG@3
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {(
                      [
                        [
                          "Similarity, then distance (current fallback)",
                          status.technical.fallback.similarity,
                        ],
                        ["Keyword overlap", status.technical.fallback.keyword_overlap],
                        ["Distance only", status.technical.fallback.distance_only],
                      ] as const
                    ).map(([label, value]) => (
                      <tr key={label} className="border-b border-border/70 last:border-0">
                        <td className="py-1 pr-4">{label}</td>
                        <td className="py-1 pr-4">{fixed(value.precision_at_3)}</td>
                        <td className="py-1 pr-4">{fixed(value.recall_at_3)}</td>
                        <td className="py-1">{fixed(value.ndcg_at_3)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <p className="mt-1 text-xs text-muted-foreground">
                  Averaged over {status.technical.fallback.students} students with both a suitable
                  and a not-suitable judgment.
                </p>
              </div>
            ) : (
              <p className="text-muted-foreground">Not enough judgments yet</p>
            )}
          </div>
        </div>
      </details>
    </Card>
  );
}

function EvaluationTable({
  metrics,
}: {
  metrics: { model: Evaluation; similarity_only: Evaluation };
}) {
  const rows: [string, Scores][] = [
    ["Model, all programs", metrics.model.overall],
    ...Object.entries(metrics.model.by_program).map(
      ([program, scores]) => [`Model, ${program}`, scores] as [string, Scores],
    ),
    ["Similarity alone, all programs", metrics.similarity_only.overall],
  ];
  return (
    <div>
      <h4 className="mb-1 font-semibold">
        Leave-one-student-out evaluation ({metrics.model.students} students)
      </h4>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[480px] text-left tabular-nums">
          <thead>
            <tr className="border-b text-xs text-muted-foreground">
              <th scope="col" className="py-1 pr-4">
                Group
              </th>
              <th scope="col" className="py-1 pr-4">
                Accuracy
              </th>
              <th scope="col" className="py-1 pr-4">
                Precision
              </th>
              <th scope="col" className="py-1 pr-4">
                Recall
              </th>
              <th scope="col" className="py-1 pr-4">
                F1
              </th>
              <th scope="col" className="py-1">
                Pairs
              </th>
            </tr>
          </thead>
          <tbody>
            {rows.map(([label, s]) => (
              <tr key={label} className="border-b border-border/70 last:border-0">
                <td className="py-1 pr-4">{label}</td>
                <td className="py-1 pr-4">{fixed(s.accuracy)}</td>
                <td className="py-1 pr-4">{fixed(s.precision)}</td>
                <td className="py-1 pr-4">{fixed(s.recall)}</td>
                <td className="py-1 pr-4">{fixed(s.f1)}</td>
                <td className="py-1">{s.n}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
