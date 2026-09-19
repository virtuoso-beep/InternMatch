import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { useSessionUser } from "./SessionGuard";
import { Button, Card, CardTitle } from "./ui";

type Criterion = { id: number; name: string; max_score: string; weight: string };
type Rubric = { id: number; name: string; version: number; criteria: Criterion[] };
type Evaluation = {
  id: number;
  period: string;
  status: string;
  comments: string | null;
  weighted_percentage: number | null;
  evaluation_rubric: Rubric;
  scores: { evaluation_criterion_id: number; score: string }[];
  evaluator: { name: string };
};

export function EvaluationPanel({
  placementId,
  placementStatus,
}: {
  placementId: number;
  placementStatus: string;
}) {
  const user = useSessionUser();
  const [data, setData] = useState<{ data: Evaluation[]; rubric: Rubric | null } | null>(null);
  const [period, setPeriod] = useState("");
  const [status, setStatus] = useState("draft");
  const [comments, setComments] = useState("");
  const [scores, setScores] = useState<Record<number, string>>({});
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [revision, setRevision] = useState(0);
  const [draftRubric, setDraftRubric] = useState<Rubric | null>(null);
  useEffect(() => {
    let active = true;
    api<{ data: Evaluation[]; rubric: Rubric | null }>(`/placements/${placementId}/evaluations`)
      .then((value) => {
        if (active) setData(value);
      })
      .catch((cause: Error) => {
        if (active) setMessage(cause.message);
      });
    return () => {
      active = false;
    };
  }, [placementId, revision]);
  const rubric = draftRubric ?? data?.rubric;
  return (
    <Card>
      <CardTitle>Supervisor evaluations</CardTitle>
      {message && (
        <p role="status" className="mb-3">
          {message}
        </p>
      )}
      {data && !data.rubric && (
        <p>No confirmed evaluation rubric is configured for this program.</p>
      )}
      {user.role === "supervisor" &&
        rubric &&
        ["active", "completed"].includes(placementStatus) && (
          <form
            className="my-4 space-y-3"
            onSubmit={async (event) => {
              event.preventDefault();
              setBusy(true);
              setMessage("");
              try {
                await mutate(`/placements/${placementId}/evaluations`, {
                  period,
                  status,
                  comments: comments || null,
                  scores: rubric.criteria
                    .filter((item) => scores[item.id] !== undefined && scores[item.id] !== "")
                    .map((item) => ({ criterion_id: item.id, score: Number(scores[item.id]) })),
                });
                setRevision((value) => value + 1);
                setMessage("Evaluation saved.");
                setPeriod("");
                setScores({});
                setComments("");
                setDraftRubric(null);
              } catch (cause) {
                setMessage(cause instanceof Error ? cause.message : "Unable to save evaluation.");
              } finally {
                setBusy(false);
              }
            }}
          >
            <p className="text-sm font-semibold">
              {rubric.name} · Version {rubric.version}
            </p>
            <label className="block text-sm">
              Evaluation period
              <input
                required
                maxLength={60}
                value={period}
                readOnly={draftRubric !== null}
                onChange={(event) => setPeriod(event.target.value)}
                className="ml-2 rounded border p-2"
              />
            </label>
            {rubric.criteria.map((item) => (
              <label key={item.id} className="block text-sm">
                {item.name} (0–{item.max_score}; weight {item.weight})
                <input
                  aria-label={`${item.name} score`}
                  type="number"
                  min="0"
                  max={item.max_score}
                  step="0.01"
                  required={status === "submitted"}
                  value={scores[item.id] ?? ""}
                  onChange={(event) =>
                    setScores((current) => ({ ...current, [item.id]: event.target.value }))
                  }
                  className="ml-2 w-28 rounded border p-2"
                />
              </label>
            ))}
            <label className="block text-sm">
              Overall comments
              <textarea
                value={comments}
                onChange={(event) => setComments(event.target.value)}
                className="mt-1 w-full rounded border p-2"
              />
            </label>
            <label className="block text-sm">
              Save as
              <select
                value={status}
                onChange={(event) => setStatus(event.target.value)}
                className="ml-2 rounded border p-2"
              >
                <option value="draft">Draft</option>
                <option value="submitted">Final submission</option>
              </select>
            </label>
            <p className="text-sm text-muted-foreground">
              Final submissions cannot be edited. Percentage = weighted mean of each score divided
              by its maximum, multiplied by 100.
            </p>
            <Button type="submit" disabled={busy}>
              Save evaluation
            </Button>
            {draftRubric && (
              <Button
                variant="outline"
                onClick={() => {
                  setDraftRubric(null);
                  setPeriod("");
                  setScores({});
                  setComments("");
                }}
              >
                Cancel draft editing
              </Button>
            )}
          </form>
        )}
      {data?.data.length === 0 && <p>No evaluations are available.</p>}
      <div className="divide-y">
        {data?.data.map((item) => (
          <section key={item.id} className="space-y-2 py-3 text-sm">
            <p className="font-semibold">
              {item.period} · {item.status} · {item.evaluator.name}
            </p>
            <p>
              {item.evaluation_rubric.name} v{item.evaluation_rubric.version} · Weighted percentage:{" "}
              {item.weighted_percentage === null
                ? "Incomplete"
                : `${item.weighted_percentage.toFixed(2)}%`}
            </p>
            <ul>
              {item.evaluation_rubric.criteria.map((criterion) => (
                <li key={criterion.id}>
                  {criterion.name}:{" "}
                  {item.scores.find((score) => score.evaluation_criterion_id === criterion.id)
                    ?.score ?? "Not scored"}{" "}
                  / {criterion.max_score}
                </li>
              ))}
            </ul>
            <p>{item.comments}</p>
            {user.role === "supervisor" && item.status === "draft" && (
              <Button
                variant="outline"
                onClick={() => {
                  setDraftRubric(item.evaluation_rubric);
                  setPeriod(item.period);
                  setComments(item.comments ?? "");
                  setStatus("draft");
                  setScores(
                    Object.fromEntries(
                      item.scores.map((score) => [score.evaluation_criterion_id, score.score]),
                    ),
                  );
                }}
              >
                Edit draft evaluation
              </Button>
            )}
          </section>
        ))}
      </div>
    </Card>
  );
}
