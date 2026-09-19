import { useEffect, useState } from "react";
import { api, mutate, upload } from "@/lib/api";
import { useSessionUser } from "./SessionGuard";
import { Button, Card, CardTitle, PageHeader } from "./ui";

type Requirement = {
  id: number;
  is_required: boolean;
  due_at: string | null;
  requirement_type: { name: string };
};
type Submission = {
  id: number;
  program_term_requirement_id: number;
  status: string;
  revision: number;
  document: { original_name: string };
  reviews: { comments: string | null }[];
};
type Enrollment = {
  id: number;
  student_id: number;
  program_term: { program: { code: string }; academic_term: { name: string } };
  student?: { student_number: string; user: { name: string } };
};
export function RequirementWorkspace() {
  const user = useSessionUser();
  const [enrollments, setEnrollments] = useState<Enrollment[] | null>(null);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    api<{ data: Enrollment[] }>("/enrollments")
      .then(({ data }) => {
        if (active) setEnrollments(data);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, []);
  return (
    <>
      <PageHeader title="Requirements" subtitle="Submit documents and track coordinator review." />
      {["admin", "coordinator"].includes(user.role) && <RequirementConfiguration />}
      {error && <p role="alert">{error}</p>}
      {!enrollments && !error && <p role="status">Loading enrollments…</p>}
      {enrollments?.length === 0 && <Card>No enrollment records are available.</Card>}
      <div className="space-y-5">
        {enrollments?.map((item) => (
          <RequirementsForEnrollment key={item.id} enrollment={item} />
        ))}
      </div>
    </>
  );
}

export function RequirementConfiguration() {
  type Config = {
    program_terms: {
      id: number;
      program: { code: string };
      academic_term: { code: string };
      requirements: (Requirement & {
        requirement_type_id: number;
        required_before_deployment: boolean;
      })[];
    }[];
    types: { id: number; name: string }[];
  };
  const [config, setConfig] = useState<Config | null>(null);
  const [term, setTerm] = useState("");
  const [type, setType] = useState("");
  const [required, setRequired] = useState(true);
  const [deployment, setDeployment] = useState(true);
  const [due, setDue] = useState("");
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  useEffect(() => {
    let active = true;
    api<Config>("/requirement-configuration")
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
  const selected = config?.program_terms.find((item) => item.id === Number(term));
  useEffect(() => {
    const current = selected?.requirements.find(
      (item) => item.requirement_type_id === Number(type),
    );
    setRequired(current?.is_required ?? true);
    setDeployment(current?.required_before_deployment ?? true);
    const date = current?.due_at ? new Date(current.due_at) : null;
    setDue(
      date
        ? new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16)
        : "",
    );
  }, [selected, type]);
  return (
    <Card className="mb-5">
      <CardTitle>Configure program requirements</CardTitle>
      <p className="mb-3 text-sm">
        Select an enrolled program and term. Existing requirements are updated when saved.
      </p>
      <form
        className="grid gap-3 sm:grid-cols-2"
        onSubmit={async (event) => {
          event.preventDefault();
          setBusy(true);
          setMessage("");
          try {
            await mutate(`/program-terms/${term}/requirements`, {
              requirement_type_id: Number(type),
              is_required: required,
              required_before_deployment: deployment,
              due_at: due ? new Date(due).toISOString() : null,
            });
            setConfig(await api<Config>("/requirement-configuration"));
            setMessage("Requirement configuration saved.");
          } catch (cause) {
            setMessage(cause instanceof Error ? cause.message : "Unable to save requirement.");
          } finally {
            setBusy(false);
          }
        }}
      >
        <label className="text-sm">
          Program and term
          <select
            required
            value={term}
            onChange={(event) => setTerm(event.target.value)}
            className="mt-1 block w-full rounded border p-2"
          >
            <option value="">Select program and term</option>
            {config?.program_terms.map((item) => (
              <option key={item.id} value={item.id}>
                {item.program.code} · {item.academic_term.code}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm">
          Requirement type
          <select
            required
            value={type}
            onChange={(event) => setType(event.target.value)}
            className="mt-1 block w-full rounded border p-2"
          >
            <option value="">Select requirement</option>
            {config?.types.map((item) => (
              <option key={item.id} value={item.id}>
                {item.name}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm">
          Due date and time (local)
          <input
            type="datetime-local"
            value={due}
            onChange={(event) => setDue(event.target.value)}
            className="mt-1 block w-full rounded border p-2"
          />
        </label>
        <div className="space-y-2 text-sm">
          <label className="block">
            <input
              type="checkbox"
              checked={required}
              onChange={(event) => setRequired(event.target.checked)}
            />{" "}
            Required for completion
          </label>
          <label className="block">
            <input
              type="checkbox"
              checked={deployment}
              onChange={(event) => setDeployment(event.target.checked)}
            />{" "}
            Required before deployment
          </label>
        </div>
        <Button type="submit" disabled={busy || !config}>
          Save requirement
        </Button>
      </form>
      {selected && (
        <ul className="mt-3 text-sm">
          {selected.requirements.map((item) => (
            <li key={item.id}>
              {item.requirement_type.name} ·{" "}
              {item.required_before_deployment
                ? "Before deployment"
                : item.is_required
                  ? "Before completion"
                  : "Optional"}
            </li>
          ))}
        </ul>
      )}
      {message && (
        <p role="status" className="mt-3">
          {message}
        </p>
      )}
    </Card>
  );
}

function RequirementsForEnrollment({ enrollment }: { enrollment: Enrollment }) {
  const user = useSessionUser();
  const [data, setData] = useState<{
    requirements: Requirement[];
    submissions: Submission[];
  } | null>(null);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [revision, setRevision] = useState(0);
  const [comments, setComments] = useState<Record<number, string>>({});
  useEffect(() => {
    let active = true;
    api<{ requirements: Requirement[]; submissions: Submission[] }>(
      `/enrollments/${enrollment.id}/requirements`,
    )
      .then((value) => {
        if (active) setData(value);
      })
      .catch((cause: Error) => {
        if (active) setMessage(cause.message);
      });
    return () => {
      active = false;
    };
  }, [enrollment.id, revision]);
  return (
    <Card>
      <CardTitle>
        {enrollment.student?.user.name ?? enrollment.program_term.program.code} ·{" "}
        {enrollment.program_term.academic_term.name}
      </CardTitle>
      {message && (
        <p role="status" className="my-3">
          {message}
        </p>
      )}
      {data?.requirements.length === 0 && (
        <p>No document requirements have been configured for this program and term.</p>
      )}
      <div className="divide-y">
        {data?.requirements.map((requirement) => {
          const submissions = data.submissions.filter(
            (item) => item.program_term_requirement_id === requirement.id,
          );
          const latest = submissions[0];
          return (
            <section key={requirement.id} className="py-4">
              <h3 className="font-semibold">
                {requirement.requirement_type.name}
                {requirement.is_required ? " (required)" : " (optional)"}
              </h3>
              <p className="text-sm">
                Status: {latest?.status.replaceAll("_", " ") ?? "Not submitted"}
                {requirement.due_at
                  ? ` · Due ${new Date(requirement.due_at).toLocaleString()}`
                  : ""}
              </p>
              {user.role === "student" && (
                <form
                  className="my-3 flex flex-wrap gap-3"
                  onSubmit={async (event) => {
                    event.preventDefault();
                    const form = event.currentTarget;
                    const payload = new FormData(form);
                    payload.set("requirement_id", String(requirement.id));
                    setBusy(true);
                    setMessage("");
                    try {
                      await upload(`/enrollments/${enrollment.id}/requirements`, payload);
                      form.reset();
                      setRevision((value) => value + 1);
                      setMessage("Document submitted for review.");
                    } catch (cause) {
                      setMessage(
                        cause instanceof Error ? cause.message : "Unable to submit document.",
                      );
                    } finally {
                      setBusy(false);
                    }
                  }}
                >
                  <input
                    aria-label={`${requirement.requirement_type.name} document`}
                    type="file"
                    name="file"
                    required
                    accept=".pdf,.jpg,.jpeg,.png,.docx"
                  />
                  <Button type="submit" disabled={busy}>
                    Submit document
                  </Button>
                </form>
              )}
              <ul className="space-y-2 text-sm">
                {submissions.map((item) => (
                  <li key={item.id}>
                    <a
                      className="font-medium underline"
                      href={`/api/v1/submissions/${item.id}/document`}
                    >
                      Revision {item.revision}: {item.document.original_name}
                    </a>{" "}
                    · {item.status.replaceAll("_", " ")}
                    {item.reviews.map(
                      (review, i) => review.comments && <p key={i}>{review.comments}</p>,
                    )}
                  </li>
                ))}
              </ul>
              {user.role === "coordinator" &&
                latest &&
                ["submitted", "under_review"].includes(latest.status) && (
                  <div className="mt-3 space-y-3">
                    <label className="block text-sm">
                      Review comments
                      <textarea
                        className="mt-1 block w-full rounded border p-2"
                        value={comments[latest.id] ?? ""}
                        onChange={(event) =>
                          setComments((current) => ({
                            ...current,
                            [latest.id]: event.target.value,
                          }))
                        }
                      />
                    </label>
                    <div className="flex flex-wrap gap-2">
                      {[
                        ["under_review", "Start review"],
                        ["approved", "Approve"],
                        ["rejected", "Reject"],
                      ].map(([status, label]) => (
                        <Button
                          key={status}
                          disabled={busy}
                          variant="outline"
                          onClick={async () => {
                            setBusy(true);
                            setMessage("");
                            try {
                              await mutate(`/submissions/${latest.id}/review`, {
                                status,
                                comments: comments[latest.id] || null,
                              });
                              setRevision((value) => value + 1);
                              setMessage("Review saved.");
                            } catch (cause) {
                              setMessage(
                                cause instanceof Error ? cause.message : "Unable to save review.",
                              );
                            } finally {
                              setBusy(false);
                            }
                          }}
                        >
                          {label}
                        </Button>
                      ))}
                    </div>
                  </div>
                )}
            </section>
          );
        })}
      </div>
    </Card>
  );
}
