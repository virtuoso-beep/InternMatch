import { useEffect, useState } from "react";
import { Plus } from "lucide-react";
import { toast } from "sonner";
import { api, mutate } from "@/lib/api";
import { useSessionUser } from "./SessionGuard";
import { Button, Card, CardTitle, FormField, FormModal, FormSection, formatRating } from "./ui";
import { ConfirmButton } from "./actions";
import { inputClass, toFormError, type FormError } from "./formUtils";

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
type Draft = {
  rubric: Rubric;
  period: string;
  comments: string;
  status: string;
  scores: Record<number, string>;
  existing: boolean;
};

const payload = (draft: Draft, status: string) => ({
  period: draft.period,
  status,
  comments: draft.comments || null,
  scores: draft.rubric.criteria
    .filter((item) => draft.scores[item.id] !== undefined && draft.scores[item.id] !== "")
    .map((item) => ({ criterion_id: item.id, score: Number(draft.scores[item.id]) })),
});

const fromEvaluation = (item: Evaluation): Draft => ({
  rubric: item.evaluation_rubric,
  period: item.period,
  comments: item.comments ?? "",
  status: "draft",
  scores: Object.fromEntries(
    item.scores.map((score) => [score.evaluation_criterion_id, score.score]),
  ),
  existing: true,
});

export function EvaluationPanel({
  placementId,
  placementStatus,
}: {
  placementId: number;
  placementStatus: string;
}) {
  const user = useSessionUser();
  const [data, setData] = useState<{ data: Evaluation[]; rubric: Rubric | null } | null>(null);
  const [message, setMessage] = useState("");
  const [revision, setRevision] = useState(0);
  const [editor, setEditor] = useState<Draft | null>(null);
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
  const refresh = () => setRevision((value) => value + 1);
  const canEvaluate =
    user.role === "supervisor" && ["active", "completed"].includes(placementStatus);
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
      {canEvaluate && data?.rubric && (
        <div className="mb-4">
          <Button
            onClick={() =>
              setEditor({
                rubric: data.rubric!,
                period: "",
                comments: "",
                status: "draft",
                scores: {},
                existing: false,
              })
            }
          >
            <Plus size={16} aria-hidden="true" />
            Add evaluation
          </Button>
        </div>
      )}
      {data?.data.length === 0 && <p>No evaluations are available.</p>}
      <div className="divide-y">
        {data?.data.map((item) => (
          <section key={item.id} className="space-y-2 py-3 text-sm">
            <p className="font-semibold">
              {item.period} · {item.status} · {item.evaluator.name}
            </p>
            <p>
              {item.evaluation_rubric.name} v{item.evaluation_rubric.version} · Overall rating:{" "}
              {item.weighted_percentage === null
                ? "Incomplete"
                : formatRating(item.weighted_percentage)}
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
            {canEvaluate && item.status === "draft" && (
              <div className="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" onClick={() => setEditor(fromEvaluation(item))}>
                  Edit draft evaluation
                </Button>
                <ConfirmButton
                  size="sm"
                  variant="primary"
                  disabled={item.evaluation_rubric.criteria.some(
                    (criterion) =>
                      !item.scores.some((score) => score.evaluation_criterion_id === criterion.id),
                  )}
                  title={`Finalize the ${item.period} evaluation?`}
                  description="A final evaluation counts toward the intern's rating and cannot be edited afterwards."
                  confirmLabel="Finalize"
                  success="Evaluation finalized"
                  onConfirm={() =>
                    mutate(
                      `/placements/${placementId}/evaluations`,
                      payload(fromEvaluation(item), "submitted"),
                    )
                  }
                  onDone={refresh}
                >
                  Finalize evaluation
                </ConfirmButton>
              </div>
            )}
          </section>
        ))}
      </div>
      {editor && (
        <EvaluationModal
          placementId={placementId}
          initial={editor}
          onClose={() => setEditor(null)}
          onSaved={() => {
            setEditor(null);
            refresh();
          }}
        />
      )}
    </Card>
  );
}

function EvaluationModal({
  placementId,
  initial,
  onClose,
  onSaved,
}: {
  placementId: number;
  initial: Draft;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [draft, setDraft] = useState(initial);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  const final = draft.status === "submitted";
  return (
    <FormModal
      open
      size="lg"
      onClose={onClose}
      title={initial.existing ? `Edit evaluation: ${initial.period}` : "Add evaluation"}
      description={`${draft.rubric.name} · Version ${draft.rubric.version}. Percentage = weighted mean of each score divided by its maximum, multiplied by 100.`}
      submitLabel={final ? "Submit final evaluation" : "Save draft"}
      busy={busy}
      error={error}
      onSubmit={async () => {
        setBusy(true);
        setError(null);
        try {
          await mutate(`/placements/${placementId}/evaluations`, payload(draft, draft.status));
          onSaved();
          toast.success(final ? "Evaluation finalized" : "Draft evaluation saved");
        } catch (cause) {
          setError(toFormError(cause, "Unable to save the evaluation."));
        } finally {
          setBusy(false);
        }
      }}
    >
      <FormField label="Evaluation period" required helper="For example: Midterm or Final.">
        <input
          name="period"
          required
          maxLength={60}
          readOnly={initial.existing}
          className={inputClass}
          value={draft.period}
          onChange={(event) => setDraft((d) => ({ ...d, period: event.target.value }))}
        />
      </FormField>
      <FormSection title="Scores">
        <div data-field="scores" className="grid gap-4 sm:grid-cols-2">
          {draft.rubric.criteria.map((item) => (
            <FormField
              key={item.id}
              label={item.name}
              required={final}
              helper={`0 to ${item.max_score}. Weight ${item.weight}.`}
            >
              <input
                aria-label={`${item.name} score`}
                type="number"
                min="0"
                max={item.max_score}
                step="0.01"
                required={final}
                className={inputClass}
                value={draft.scores[item.id] ?? ""}
                onChange={(event) =>
                  setDraft((d) => ({
                    ...d,
                    scores: { ...d.scores, [item.id]: event.target.value },
                  }))
                }
              />
            </FormField>
          ))}
        </div>
      </FormSection>
      <FormField label="Overall comments">
        <textarea
          name="comments"
          rows={4}
          className={inputClass}
          value={draft.comments}
          onChange={(event) => setDraft((d) => ({ ...d, comments: event.target.value }))}
        />
      </FormField>
      <FormField
        label="Save as"
        helper="A final evaluation cannot be edited. Every criterion must be scored."
      >
        <select
          name="status"
          className={inputClass}
          value={draft.status}
          onChange={(event) => setDraft((d) => ({ ...d, status: event.target.value }))}
        >
          <option value="draft">Draft</option>
          <option value="submitted">Final submission</option>
        </select>
      </FormField>
    </FormModal>
  );
}
