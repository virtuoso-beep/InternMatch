import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, CardTitle, PageHeader } from "./ui";

type Program = {
  id: number;
  code: string;
  evaluation_rubric_id: number | null;
  program_terms: {
    id: number;
    academic_term: { code: string };
    monitoring_rules: {
      remaining_days_threshold: number;
      remaining_hours_threshold: number;
      approval_reference: string;
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
  useEffect(() => {
    let active = true;
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
  }, []);
  const program = config?.programs.find((item) => item.id === Number(selected));
  return (
    <>
      <PageHeader
        title="Monitoring configuration"
        subtitle="Enter department-confirmed settings. No default thresholds or evaluation criteria are assumed."
      />
      {message && <p role="alert">{message}</p>}
      <label className="mb-4 block text-sm">
        Program
        <select
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
      {program && (
        <Settings
          key={program.id}
          program={program}
          currentRubric={
            program.evaluation_rubric_id ? config?.rubrics[program.evaluation_rubric_id] : undefined
          }
        />
      )}
    </>
  );
}

function Settings({
  program,
  currentRubric,
}: {
  program: Program;
  currentRubric: Config["rubrics"][number] | undefined;
}) {
  const [name, setName] = useState("");
  const [approval, setApproval] = useState("");
  const [criteria, setCriteria] = useState([{ name: "", max_score: "", weight: "" }]);
  const [termId, setTermId] = useState("");
  const [days, setDays] = useState("");
  const [hours, setHours] = useState("");
  const [ruleApproval, setRuleApproval] = useState("");
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [rubric, setRubric] = useState(currentRubric);
  const [terms, setTerms] = useState(program.program_terms);
  const term = terms.find((item) => item.id === Number(termId));
  useEffect(() => {
    setDays(term?.monitoring_rules ? String(term.monitoring_rules.remaining_days_threshold) : "");
    setHours(term?.monitoring_rules ? String(term.monitoring_rules.remaining_hours_threshold) : "");
    setRuleApproval(term?.monitoring_rules?.approval_reference ?? "");
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
        <form
          className="space-y-3"
          onSubmit={async (event) => {
            event.preventDefault();
            setBusy(true);
            setMessage("");
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
              setMessage(
                "New rubric version saved. Existing evaluations keep their original rubric.",
              );
            } catch (cause) {
              setMessage(cause instanceof Error ? cause.message : "Unable to save rubric.");
            } finally {
              setBusy(false);
            }
          }}
        >
          <label className="block text-sm">
            Rubric name
            <input
              required
              value={name}
              onChange={(event) => setName(event.target.value)}
              className="mt-1 block w-full rounded border p-2"
            />
          </label>
          <label className="block text-sm">
            Department approval reference
            <input
              required
              value={approval}
              onChange={(event) => setApproval(event.target.value)}
              className="mt-1 block w-full rounded border p-2"
            />
          </label>
          {criteria.map((criterion, index) => (
            <div key={index} className="grid gap-2 sm:grid-cols-3">
              {(["name", "max_score", "weight"] as const).map((key) => (
                <label key={key} className="text-sm">
                  {key === "name" ? "Criterion" : key === "max_score" ? "Maximum score" : "Weight"}
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
                    className="mt-1 block w-full rounded border p-2"
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
              onClick={() =>
                setCriteria((current) => [...current, { name: "", max_score: "", weight: "" }])
              }
            >
              Add criterion
            </Button>
            <Button type="submit" disabled={busy}>
              Save new rubric version
            </Button>
          </div>
        </form>
      </Card>
      <Card>
        <CardTitle>Hours-attention thresholds</CardTitle>
        <p className="mb-3 text-sm">
          Flag when remaining days are at or below the days threshold and remaining hours are at or
          above the hours threshold. Only certified time counts.
        </p>
        <form
          className="space-y-3"
          onSubmit={async (event) => {
            event.preventDefault();
            setBusy(true);
            setMessage("");
            try {
              const rules = {
                remaining_days_threshold: Number(days),
                remaining_hours_threshold: Number(hours),
                approval_reference: ruleApproval,
              };
              await mutate(`/program-terms/${termId}/monitoring-rules`, rules, "PUT");
              setTerms((current) =>
                current.map((item) =>
                  item.id === Number(termId) ? { ...item, monitoring_rules: rules } : item,
                ),
              );
              setMessage("Monitoring thresholds saved.");
            } catch (cause) {
              setMessage(cause instanceof Error ? cause.message : "Unable to save rules.");
            } finally {
              setBusy(false);
            }
          }}
        >
          <label className="block text-sm">
            Academic term
            <select
              required
              value={termId}
              onChange={(event) => setTermId(event.target.value)}
              className="ml-2 rounded border p-2"
            >
              <option value="">Select term</option>
              {terms.map((item) => (
                <option key={item.id} value={item.id}>
                  {item.academic_term.code}
                </option>
              ))}
            </select>
          </label>
          <label className="block text-sm">
            Remaining days threshold
            <input
              required
              type="number"
              min="0"
              max="365"
              value={days}
              onChange={(event) => setDays(event.target.value)}
              className="ml-2 rounded border p-2"
            />
          </label>
          <label className="block text-sm">
            Remaining hours threshold
            <input
              required
              type="number"
              min="1"
              max="10000"
              value={hours}
              onChange={(event) => setHours(event.target.value)}
              className="ml-2 rounded border p-2"
            />
          </label>
          <label className="block text-sm">
            Department approval reference
            <input
              required
              value={ruleApproval}
              onChange={(event) => setRuleApproval(event.target.value)}
              className="mt-1 block w-full rounded border p-2"
            />
          </label>
          <Button type="submit" disabled={busy}>
            Save thresholds
          </Button>
        </form>
      </Card>
    </div>
  );
}
