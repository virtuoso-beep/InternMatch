import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Plus } from "lucide-react";
import { toast } from "sonner";
import { Button, Card, CardTitle, FormModal, PageHeader } from "./ui";
import { inputClass, toFormError, type FormError } from "./formUtils";

type Program = {
  id: number;
  code: string;
  evaluation_rubric_id: number | null;
  program_terms: {
    id: number;
    academic_term: { code: string };
    monitoring_rules: {
      remaining_days_threshold?: number;
      remaining_hours_threshold?: number;
      approval_reference: string;
      [rule: string]: number | string | undefined;
    } | null;
  }[];
};
type Config = {
  programs: Program[];
  rubrics: Record<
    number,
    {
      name: string;
      version: number;
      criteria: { name: string; max_score: string; weight: string }[];
    }
  >;
};
export function ProgramMonitoringSettings() {
  const [config, setConfig] = useState<Config | null>(null);
  const [selected, setSelected] = useState("");
  const [message, setMessage] = useState("");
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    setMessage("");
    api<Config>("/program-monitoring")
      .then((value) => {
        if (active) setConfig(value);
      })
      .catch((cause: Error) => {
        if (active) setMessage(cause.message);
      });
    return () => {
      active = false;
    };
  }, [revision]);
  const program = config?.programs.find((item) => item.id === Number(selected));
  return (
    <>
      <PageHeader
        title="Monitoring settings"
        subtitle="Enter department-confirmed settings. No default alert levels or evaluation criteria are assumed."
      />
      {message && (
        <p role="alert">
          {message}{" "}
          <Button variant="outline" onClick={() => setRevision((v) => v + 1)}>
            Try again
          </Button>
        </p>
      )}
      {!config && !message && <p role="status">Loading monitoring settings…</p>}
      {config?.programs.length === 0 && <Card>No programs are assigned to your account.</Card>}
      <label className="mb-4 block text-sm">
        Program
        <select
          aria-label="Program"
          value={selected}
          onChange={(event) => setSelected(event.target.value)}
          className="ml-2 rounded border p-2"
        >
          <option value="">Select program</option>
          {config?.programs.map((item) => (
            <option key={item.id} value={item.id}>
              {item.code}
            </option>
          ))}
        </select>
      </label>
      {!program && config && (
        <p className="mt-6 rounded-lg border border-dashed bg-card p-6 text-sm text-muted-foreground">
          Choose a program to view or update its monitoring alert levels and evaluation rubric.
        </p>
      )}
      {program && (
        <Settings
          key={program.id}
          program={program}
          currentRubric={
            program.evaluation_rubric_id ? config?.rubrics[program.evaluation_rubric_id] : undefined
          }
          saved={() => setRevision((v) => v + 1)}
        />
      )}
    </>
  );
}

/** Spec §13 adopted defaults (mirrors ProgressSummary::DEFAULTS on the server). */
const SPEC_RULES: {
  key: string;
  label: string;
  unit: string;
  min: number;
  max: number;
  value: number;
}[] = [
  {
    key: "behind_schedule_percent",
    label: "Behind schedule below % of expected pace",
    unit: "%",
    min: 1,
    max: 100,
    value: 85,
  },
  {
    key: "behind_schedule_after_days",
    label: "Start pace checks after",
    unit: "working days",
    min: 0,
    max: 200,
    value: 10,
  },
  {
    key: "max_daily_hours",
    label: "Maximum creditable hours per day",
    unit: "hours",
    min: 1,
    max: 24,
    value: 8,
  },
  {
    key: "no_log_days",
    label: "No hours logged for",
    unit: "working days",
    min: 1,
    max: 60,
    value: 5,
  },
  {
    key: "uncertified_days",
    label: "Time log waiting for certification over",
    unit: "working days",
    min: 1,
    max: 60,
    value: 5,
  },
  {
    key: "weekly_report_grace_days",
    label: "Weekly report grace after week ends",
    unit: "working days",
    min: 0,
    max: 60,
    value: 3,
  },
  {
    key: "requirement_grace_days",
    label: "Requirement grace after deadline",
    unit: "working days",
    min: 0,
    max: 60,
    value: 2,
  },
  {
    key: "evaluation_window_days",
    label: "Evaluation period stays open",
    unit: "working days",
    min: 1,
    max: 60,
    value: 10,
  },
  {
    key: "evaluation_grace_days",
    label: "Evaluation flag after period closes",
    unit: "working days",
    min: 0,
    max: 60,
    value: 5,
  },
];
const ruleValues = (saved?: Record<string, unknown> | null) =>
  Object.fromEntries(SPEC_RULES.map((rule) => [rule.key, String(saved?.[rule.key] ?? rule.value)]));

function Settings({
  program,
  currentRubric,
  saved,
}: {
  program: Program;
  currentRubric: Config["rubrics"][number] | undefined;
  saved: () => void;
}) {
  const [name, setName] = useState("");
  const [approval, setApproval] = useState("");
  const [criteria, setCriteria] = useState([{ name: "", max_score: "", weight: "" }]);
  const [termId, setTermId] = useState("");
  const [days, setDays] = useState("");
  const [hours, setHours] = useState("");
  const [ruleApproval, setRuleApproval] = useState("");
  const [specRules, setSpecRules] = useState<Record<string, string>>(ruleValues());
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [rubric, setRubric] = useState(currentRubric);
  const [rubricOpen, setRubricOpen] = useState(false);
  const [rulesOpen, setRulesOpen] = useState(false);
  const [rubricError, setRubricError] = useState<FormError | null>(null);
  const [rulesError, setRulesError] = useState<FormError | null>(null);
  const [terms, setTerms] = useState(program.program_terms);
  const term = terms.find((item) => item.id === Number(termId));
  useEffect(() => {
    setDays(String(term?.monitoring_rules?.remaining_days_threshold ?? ""));
    setHours(String(term?.monitoring_rules?.remaining_hours_threshold ?? ""));
    setRuleApproval(term?.monitoring_rules?.approval_reference ?? "");
    setSpecRules(ruleValues(term?.monitoring_rules));
  }, [term]);
  return (
    <div className="space-y-5">
      {message && <p role="status">{message}</p>}
      <Card>
        <CardTitle>Evaluation rubric</CardTitle>
        {rubric ? (
          <div className="mb-4 text-sm">
            <p>
              {rubric.name} · Version {rubric.version}
            </p>
            <ul>
              {rubric.criteria.map((item) => (
                <li key={item.name}>
                  {item.name} · Maximum {item.max_score} · Weight {item.weight}
                </li>
              ))}
            </ul>
          </div>
        ) : (
          <p className="mb-4">No confirmed rubric.</p>
        )}
        <Button
          onClick={() => {
            setRubricError(null);
            setRubricOpen(true);
          }}
        >
          <Plus size={16} aria-hidden="true" />
          New rubric version
        </Button>
        <FormModal
          open={rubricOpen}
          size="lg"
          onClose={() => setRubricOpen(false)}
          title={`New rubric version: ${program.code}`}
          description="Existing evaluations keep their original rubric."
          submitLabel="Save new rubric version"
          busy={busy}
          error={rubricError}
          onSubmit={async () => {
            setBusy(true);
            setRubricError(null);
            try {
              const result = await mutate<{ data: Config["rubrics"][number] }>(
                `/programs/${program.id}/evaluation-rubrics`,
                {
                  name,
                  approval_reference: approval,
                  criteria: criteria.map((item) => ({
                    ...item,
                    max_score: Number(item.max_score),
                    weight: Number(item.weight),
                  })),
                },
              );
              setRubric(result.data);
              saved();
              setRubricOpen(false);
              toast.success("New rubric version saved");
            } catch (cause) {
              setRubricError(toFormError(cause, "Unable to save rubric."));
            } finally {
              setBusy(false);
            }
          }}
        >
          <div className="space-y-3">
            <label className="block text-sm">
              Rubric name
              <input
                required
                value={name}
                onChange={(event) => setName(event.target.value)}
                className={`mt-1 ${inputClass}`}
              />
            </label>
            <label className="block text-sm">
              Department approval reference
              <input
                required
                value={approval}
                onChange={(event) => setApproval(event.target.value)}
                className={`mt-1 ${inputClass}`}
              />
            </label>
            {criteria.map((criterion, index) => (
              <div key={index} className="grid gap-2 sm:grid-cols-3">
                {(["name", "max_score", "weight"] as const).map((key) => (
                  <label key={key} className="text-sm">
                    {key === "name"
                      ? "Criterion"
                      : key === "max_score"
                        ? "Maximum score"
                        : "Weight"}
                    <input
                      required
                      type={key === "name" ? "text" : "number"}
                      min={key === "weight" ? "0.0001" : "0.01"}
                      step={key === "weight" ? "0.0001" : "0.01"}
                      value={criterion[key]}
                      onChange={(event) =>
                        setCriteria((current) =>
                          current.map((item, i) =>
                            i === index ? { ...item, [key]: event.target.value } : item,
                          ),
                        )
                      }
                      className={`mt-1 ${inputClass}`}
                    />
                  </label>
                ))}
                {criteria.length > 1 && (
                  <Button
                    variant="outline"
                    onClick={() => setCriteria((current) => current.filter((_, i) => i !== index))}
                  >
                    Remove criterion
                  </Button>
                )}
              </div>
            ))}
            <div className="flex gap-2">
              <Button
                variant="outline"
                disabled={criteria.length >= 100}
                onClick={() =>
                  setCriteria((current) => [...current, { name: "", max_score: "", weight: "" }])
                }
              >
                Add criterion
              </Button>
              <Button
                variant="outline"
                onClick={() => {
                  setName("");
                  setApproval("");
                  setCriteria([{ name: "", max_score: "", weight: "" }]);
                }}
              >
                Clear rubric draft
              </Button>
            </div>
          </div>
        </FormModal>
      </Card>
      <Card>
        <CardTitle>Monitoring rules</CardTitle>
        <p className="mb-3 text-sm">
          The adopted spec §13 rules are active by default for every program. Adjust a value for
          this program term only when the department approves it; every change is logged. Only
          certified time counts.
        </p>
        <Button
          onClick={() => {
            setRulesError(null);
            setRulesOpen(true);
          }}
        >
          Edit monitoring rules
        </Button>
        <FormModal
          open={rulesOpen}
          size="lg"
          onClose={() => setRulesOpen(false)}
          title={`Monitoring rules: ${program.code}`}
          description="Change a value only when the department approves it. Every change is logged."
          submitLabel="Save monitoring rules"
          busy={busy}
          submitDisabled={!termId}
          error={rulesError}
          onSubmit={async () => {
            setBusy(true);
            setRulesError(null);
            try {
              const rules = {
                ...Object.fromEntries(
                  SPEC_RULES.map((rule) => [rule.key, Number(specRules[rule.key])]),
                ),
                ...(days !== "" && hours !== ""
                  ? {
                      remaining_days_threshold: Number(days),
                      remaining_hours_threshold: Number(hours),
                    }
                  : {}),
                approval_reference: ruleApproval,
              };
              await mutate(`/program-terms/${termId}/monitoring-rules`, rules, "PUT");
              setTerms((current) =>
                current.map((item) =>
                  item.id === Number(termId) ? { ...item, monitoring_rules: rules } : item,
                ),
              );
              saved();
              setRulesOpen(false);
              toast.success("Monitoring rules saved");
            } catch (cause) {
              setRulesError(toFormError(cause, "Unable to save rules."));
            } finally {
              setBusy(false);
            }
          }}
        >
          <div className="space-y-3">
            <label className="block text-sm">
              Academic term
              <select
                aria-label="Academic term"
                required
                value={termId}
                onChange={(event) => setTermId(event.target.value)}
                className={`mt-1 ${inputClass}`}
              >
                <option value="">Select term</option>
                {terms.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.academic_term.code}
                  </option>
                ))}
              </select>
            </label>
            <div className="grid gap-3 sm:grid-cols-2">
              {SPEC_RULES.map((rule) => (
                <label key={rule.key} className="block text-sm">
                  {rule.label}
                  <span className="mt-1 flex items-center gap-2">
                    <input
                      required
                      type="number"
                      min={rule.min}
                      max={rule.max}
                      value={specRules[rule.key]}
                      onChange={(event) =>
                        setSpecRules((current) => ({ ...current, [rule.key]: event.target.value }))
                      }
                      className={`${inputClass} w-28`}
                    />
                    <span className="text-xs text-muted-foreground">{rule.unit}</span>
                  </span>
                </label>
              ))}
            </div>
            <p className="pt-2 text-xs font-semibold text-muted-foreground">
              Optional older hours alert level
            </p>
            <label className="block text-sm">
              Remaining days alert level
              <input
                type="number"
                min="0"
                max="365"
                value={days}
                onChange={(event) => setDays(event.target.value)}
                className={`mt-1 ${inputClass}`}
              />
            </label>
            <label className="block text-sm">
              Remaining hours threshold
              <input
                type="number"
                min="1"
                max="10000"
                value={hours}
                onChange={(event) => setHours(event.target.value)}
                className={`mt-1 ${inputClass}`}
              />
            </label>
            <label className="block text-sm">
              Department approval reference
              <input
                required
                value={ruleApproval}
                onChange={(event) => setRuleApproval(event.target.value)}
                className={`mt-1 ${inputClass}`}
              />
            </label>
            {term && (
              <p className="rounded-lg bg-muted p-3 text-sm" role="status">
                {term.monitoring_rules
                  ? "Saved rules exist for this term."
                  : "This term uses the spec §13 defaults."}{" "}
                {days !== "" &&
                  hours !== "" &&
                  `Preview: flag when remaining days ≤ ${days} AND remaining certified-work hours needed ≥ ${hours}.`}
              </p>
            )}
            <Button
              variant="outline"
              disabled={!termId}
              onClick={() => {
                setDays(String(term?.monitoring_rules?.remaining_days_threshold ?? ""));
                setHours(String(term?.monitoring_rules?.remaining_hours_threshold ?? ""));
                setRuleApproval(term?.monitoring_rules?.approval_reference ?? "");
                setSpecRules(ruleValues(term?.monitoring_rules));
                setMessage("");
              }}
            >
              Reset to saved rules
            </Button>
          </div>
        </FormModal>
      </Card>
    </div>
  );
}
