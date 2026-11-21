import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { toast } from "sonner";
import { Button, Card, CardTitle, ConfirmDialog, FormField, FormModal, PageHeader } from "./ui";
import { inputClass, toFormError, type FormError } from "./formUtils";

type Item = {
  id: number;
  competency_id: number;
  /** Null when the level has not been rated yet (e.g. imported skills). */
  level: number | null;
  competency: { name: string };
  evidence: { source: string }[];
};
type Data = { data: Item[]; vocabulary: { id: number; name: string }[] };
type Editor = { kind: "add" } | { kind: "edit" | "remove"; item: Item };

function evidenceSources(item: Item) {
  const sources = [...new Set(item.evidence.map((evidence) => evidence.source).filter(Boolean))];
  if (!sources.length) return "Self-assessment";
  return sources
    .map((source) => {
      if (source === "self_assessment") return "Self-assessment";
      return source.replace(/[_-]/g, " ").replace(/^\w/, (letter) => letter.toUpperCase());
    })
    .join(" + ");
}

export function StudentCompetencies() {
  const [records, setRecords] = useState<Data | null>(null);
  const [editor, setEditor] = useState<Editor | null>(null);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    setError("");
    api<Data>("/competencies")
      .then((data) => {
        if (active) setRecords(data);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [revision]);
  function openEditor(value: Editor) {
    setMessage("");
    setEditor(value);
  }
  return (
    <>
      <PageHeader
        title="Competencies"
        subtitle="Your skills and proficiency levels help match you with internship opportunities."
        action={
          <Button
            variant="outline"
            disabled={!records || records.vocabulary.length === 0}
            onClick={() => openEditor({ kind: "add" })}
          >
            Add competency
          </Button>
        }
      />
      {error && (
        <div
          role="alert"
          className="mb-5 rounded-lg border border-brand/20 bg-brand-soft p-4 text-sm"
        >
          {error}
          <button className="ml-3 underline" onClick={() => setRevision((value) => value + 1)}>
            Retry
          </button>
        </div>
      )}
      {message && (
        <p role="status" className="mb-5 rounded-lg bg-success-soft p-3 text-sm text-success">
          {message}
        </p>
      )}
      {!records && !error && <p role="status">Loading competencies…</p>}
      {records && (
        <>
          {records.vocabulary.length === 0 && (
            <p className="mb-5 rounded-lg bg-muted p-4 text-sm text-muted-foreground">
              Your program enrollment needs a competency list before you can add skills. Contact
              your coordinator.
            </p>
          )}
          <div className="grid items-stretch gap-6 xl:grid-cols-2">
            <Card className="p-6 sm:p-7">
              <CardTitle>Proficiency levels</CardTitle>
              {records.data.length === 0 ? (
                <p className="py-8 text-sm text-muted-foreground">
                  No competencies yet. Add a competency to start your skills profile.
                </p>
              ) : (
                <ul className="space-y-5 py-1">
                  {records.data.map((item) => (
                    <li key={item.id}>
                      <div className="mb-2 flex items-start justify-between gap-4 text-sm sm:text-base">
                        <span>{item.competency.name}</span>
                        <span className="shrink-0 text-muted-foreground">
                          {item.level === null ? "Not rated" : `${item.level}%`}
                        </span>
                      </div>
                      {item.level !== null && (
                        <div
                          role="progressbar"
                          aria-label={item.competency.name}
                          aria-valuemin={0}
                          aria-valuemax={100}
                          aria-valuenow={item.level}
                          className="h-2.5 overflow-hidden rounded-full bg-muted"
                        >
                          <div
                            className="h-full rounded-full bg-brand"
                            style={{ width: Math.max(0, Math.min(100, item.level)) + "%" }}
                          />
                        </div>
                      )}
                    </li>
                  ))}
                </ul>
              )}
            </Card>
            <Card className="p-6 sm:p-7">
              <CardTitle>Evidence sources</CardTitle>
              {records.data.length === 0 ? (
                <p className="py-8 text-sm text-muted-foreground">
                  Evidence sources will appear alongside your saved competencies.
                </p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-sm">
                    <thead>
                      <tr className="border-b text-xs font-medium text-muted-foreground">
                        <th scope="col" className="py-3 pr-4 font-medium">
                          Competency
                        </th>
                        <th scope="col" className="py-3 pr-4 font-medium">
                          Level
                        </th>
                        <th scope="col" className="py-3 font-medium">
                          Source
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      {records.data.map((item) => (
                        <tr key={item.id} className="border-b last:border-0">
                          <th scope="row" className="py-4 pr-4 align-top font-normal">
                            <span>{item.competency.name}</span>
                            <div className="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs">
                              <button
                                className="py-1 font-medium text-brand underline-offset-4 hover:underline"
                                aria-label={"Edit " + item.competency.name}
                                onClick={() => openEditor({ kind: "edit", item })}
                              >
                                Edit
                              </button>
                              <button
                                className="py-1 text-muted-foreground underline-offset-4 hover:underline"
                                aria-label={"Remove " + item.competency.name}
                                onClick={() => openEditor({ kind: "remove", item })}
                              >
                                Remove
                              </button>
                            </div>
                          </th>
                          <td className="py-4 pr-4 align-top">
                            {item.level === null ? "Not rated" : `${item.level}%`}
                          </td>
                          <td className="py-4 align-top text-muted-foreground">
                            {evidenceSources(item)}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </Card>
          </div>
          {editor && (
            <CompetencyEditor
              editor={editor}
              records={records}
              onClose={() => setEditor(null)}
              onSaved={(data) => {
                setRecords(data);
                setEditor(null);
              }}
            />
          )}
        </>
      )}
    </>
  );
}

function CompetencyEditor({
  editor,
  records,
  onClose,
  onSaved,
}: {
  editor: Editor;
  records: Data;
  onClose: () => void;
  onSaved: (data: Data) => void;
}) {
  const [selected, setSelected] = useState(
    editor.kind === "add" ? "" : String(editor.item.competency_id),
  );
  const [level, setLevel] = useState(editor.kind === "add" ? "" : String(editor.item.level ?? ""));
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  const choices =
    records.vocabulary.some((item) => item.id === Number(selected)) || editor.kind === "add"
      ? records.vocabulary
      : [
          ...records.vocabulary,
          { id: editor.item.competency_id, name: editor.item.competency.name },
        ];

  if (editor.kind === "remove") {
    const name = editor.item.competency.name;
    return (
      <ConfirmDialog
        open
        title={`Remove ${name}?`}
        description={`Remove ${name} from your skills profile? Competencies with attached evidence cannot be removed. This cannot be undone.`}
        confirmLabel="Remove competency"
        busy={busy}
        error={error?.message}
        onCancel={onClose}
        onConfirm={async () => {
          setBusy(true);
          setError(null);
          try {
            await mutate("/competencies/" + editor.item.id, undefined, "DELETE");
            onSaved({
              ...records,
              data: records.data.filter((item) => item.id !== editor.item.id),
            });
            toast.success("Competency removed");
          } catch (cause) {
            setError(toFormError(cause, "Unable to remove the competency."));
          } finally {
            setBusy(false);
          }
        }}
      />
    );
  }

  return (
    <FormModal
      open
      onClose={onClose}
      title={
        editor.kind === "edit"
          ? `Edit competency: ${editor.item.competency.name}`
          : "Add competency"
      }
      description={
        editor.kind === "edit"
          ? "Update your self-assessed proficiency."
          : "Add a skill to your competency profile."
      }
      submitLabel="Save competency"
      busy={busy}
      error={error}
      onSubmit={async () => {
        setBusy(true);
        setError(null);
        try {
          const response = await mutate<{ data: Omit<Item, "evidence"> }>("/competencies", {
            competency_id: Number(selected),
            level: Number(level),
          });
          const existing = records.data.find(
            (item) => item.competency_id === response.data.competency_id,
          );
          const saved: Item = { ...response.data, evidence: existing?.evidence ?? [] };
          onSaved({
            ...records,
            data: existing
              ? records.data.map((item) => (item.id === existing.id ? saved : item))
              : [...records.data, saved],
          });
          toast.success(editor.kind === "edit" ? "Competency updated" : "Competency saved");
        } catch (cause) {
          setError(toFormError(cause, "Unable to save. Please try again."));
        } finally {
          setBusy(false);
        }
      }}
    >
      <FormField label="Competency" required>
        <select
          required
          name="competency_id"
          disabled={busy || editor.kind === "edit"}
          value={selected}
          onChange={(event) => setSelected(event.target.value)}
          className={inputClass}
        >
          <option value="">Select a program competency</option>
          {choices.map((item) => (
            <option key={item.id} value={item.id}>
              {item.name}
            </option>
          ))}
        </select>
      </FormField>
      <FormField label="Proficiency (%)" required helper="Whole number from 0 to 100.">
        <input
          required
          name="level"
          disabled={busy}
          type="number"
          min="0"
          max="100"
          step="1"
          placeholder="75"
          value={level}
          onChange={(event) => setLevel(event.target.value)}
          className={inputClass}
        />
      </FormField>
      {editor.kind === "add" &&
        records.data.some((item) => item.competency_id === Number(selected)) && (
          <p className="text-sm text-muted-foreground">
            This competency is already in your profile. Saving updates its proficiency.
          </p>
        )}
    </FormModal>
  );
}
