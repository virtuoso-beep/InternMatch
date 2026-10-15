import { useEffect, useRef, useState } from "react";
import { api, mutate, upload } from "@/lib/api";
import { useSessionUser } from "./SessionGuard";
import { Button, Card, CardTitle, cx, PageHeader, Pill } from "./ui";

type Requirement = {
  id: number;
  is_required: boolean;
  due_at: string | null;
  scheduled_on: string | null;
  requirement_type: { name: string; kind: "document" | "event" };
};
type Attendance = {
  id: number;
  program_term_requirement_id: number;
  attended_on: string;
  confirmed_at: string | null;
};
type RequirementData = {
  requirements: Requirement[];
  submissions: Submission[];
  attendances: Attendance[];
};
type Submission = {
  id: number;
  program_term_requirement_id: number;
  status: string;
  revision: number;
  submitted_at?: string | null;
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
      {user.role !== "student" && (
        <PageHeader
          title="Requirements"
          subtitle="Submit documents, record event attendance, and track coordinator confirmation."
        />
      )}
      {["admin", "coordinator"].includes(user.role) && <RequirementConfiguration />}
      {error && <p role="alert">{error}</p>}
      {!enrollments && !error && <p role="status">Loading enrollments…</p>}
      {enrollments?.length === 0 && <Card>No enrollment records are available.</Card>}
      <div className="space-y-5">
        {enrollments?.map((item) =>
          user.role === "student" ? (
            <StudentRequirements key={item.id} enrollment={item} />
          ) : (
            <RequirementsForEnrollment key={item.id} enrollment={item} />
          ),
        )}
      </div>
    </>
  );
}

function StudentRequirements({ enrollment }: { enrollment: Enrollment }) {
  const [data, setData] = useState<RequirementData | null>(null);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [revision, setRevision] = useState(0);
  const [filter, setFilter] = useState("All");
  const [search, setSearch] = useState("");
  const [modal, setModal] = useState<{ kind: "upload" | "history" | "event"; id?: number } | null>(
    null,
  );
  useEffect(() => {
    let active = true;
    setError("");
    api<RequirementData>(`/enrollments/${enrollment.id}/requirements`)
      .then((value) => {
        if (active) setData(value);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [enrollment.id, revision]);
  const submissions = (id: number) =>
    [...(data?.submissions ?? [])]
      .filter((item) => item.program_term_requirement_id === id)
      .sort((a, b) => b.revision - a.revision);
  function state(requirement: Requirement) {
    if (requirement.requirement_type.kind === "event") {
      const attendance = data?.attendances.find(
        (item) => item.program_term_requirement_id === requirement.id,
      );
      return attendance?.confirmed_at ? "Approved" : attendance ? "Pending" : "Missing";
    }
    const status = submissions(requirement.id)[0]?.status;
    return status === "approved"
      ? "Approved"
      : status === "rejected"
        ? "Returned"
        : status
          ? "Pending"
          : "Missing";
  }
  const required = data?.requirements.filter((item) => item.is_required) ?? [];
  const complete = required.filter((item) => state(item) === "Approved").length;
  const visible =
    data?.requirements.filter(
      (item) =>
        (filter === "All" || state(item) === filter) &&
        item.requirement_type.name.toLowerCase().includes(search.trim().toLowerCase()),
    ) ?? [];
  return (
    <div className="space-y-6">
      <PageHeader
        title="Requirements"
        subtitle="Upload and track the documents required before and during deployment."
        action={
          <Button
            disabled={!data?.requirements.some((item) => item.requirement_type.kind === "document")}
            onClick={() => setModal({ kind: "upload" })}
            className="bg-brand text-white hover:bg-brand/90 font-medium px-4 py-2 rounded-lg shadow-sm"
          >
            Upload document
          </Button>
        }
      />

      {error && (
        <p role="alert" className="rounded-lg bg-brand-soft p-4 text-sm">
          {error}
          <button className="ml-3 underline" onClick={() => setRevision((value) => value + 1)}>
            Retry
          </button>
        </p>
      )}
      {notice && (
        <p role="status" className="rounded-lg bg-success-soft p-3 text-sm text-success">
          {notice}
        </p>
      )}
      {!data && !error && <p role="status">Loading requirements…</p>}
      {data && (
        <>
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div className="flex items-center gap-2">
              <span className="text-sm font-medium text-foreground">Status:</span>
              <div className="flex flex-wrap gap-1.5">
                {["All", "Approved", "Pending", "Missing", "Returned"].map((value) => (
                  <button
                    key={value}
                    type="button"
                    aria-pressed={filter === value}
                    onClick={() => setFilter(value)}
                    className={cx(
                      "rounded-full px-3 py-1 text-xs font-medium transition-colors",
                      filter === value
                        ? "bg-brand text-white shadow-xs"
                        : "bg-muted/70 text-muted-foreground hover:bg-muted hover:text-foreground",
                    )}
                  >
                    {value}
                  </button>
                ))}
              </div>
            </div>

            <div className="w-full sm:w-72">
              <input
                type="search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search requirements…"
                aria-label="Search requirements"
                className="w-full rounded-lg border border-border bg-card px-3.5 py-1.5 text-sm placeholder:text-muted-foreground focus:border-brand focus:outline-hidden"
              />
            </div>
          </div>

          <Card className="p-0 overflow-hidden border border-border/80 bg-card rounded-xl shadow-xs">
            {!data.requirements.length ? (
              <p className="p-8 text-center text-sm text-muted-foreground">
                No requirements have been configured for your program and term.
              </p>
            ) : !visible.length ? (
              <div className="p-8 text-center">
                <p className="mb-3 text-sm text-muted-foreground">
                  No requirements match your current search and filters.
                </p>
                <Button
                  variant="outline"
                  onClick={() => {
                    setFilter("All");
                    setSearch("");
                  }}
                >
                  Clear filters
                </Button>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full min-w-[620px] text-left text-sm border-collapse">
                  <thead>
                    <tr className="border-b border-border/60 text-xs font-semibold text-muted-foreground tracking-wider uppercase bg-muted/20">
                      <th scope="col" className="py-3.5 px-6 font-semibold">
                        DOCUMENT
                      </th>
                      <th scope="col" className="py-3.5 px-6 font-semibold">
                        STATUS
                      </th>
                      <th scope="col" className="py-3.5 px-6 font-semibold">
                        DATE
                      </th>
                      <th scope="col" className="py-3.5 px-6 font-semibold">
                        ACTION
                      </th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border/50">
                    {visible.map((item) => {
                      const current = submissions(item.id)[0];
                      const status = state(item);
                      const isEvent = item.requirement_type.kind === "event";

                      let dateLabel = "No due date";
                      if (isEvent) {
                        dateLabel = item.scheduled_on ?? "Date not announced";
                      } else if (current?.submitted_at) {
                        const formatted = new Date(current.submitted_at).toLocaleDateString(
                          "en-US",
                          {
                            month: "short",
                            day: "numeric",
                            year: "numeric",
                          },
                        );
                        dateLabel = status === "Approved" ? formatted : `Submitted ${formatted}`;
                      } else if (item.due_at) {
                        dateLabel = `Due ${new Date(item.due_at).toLocaleDateString("en-US", {
                          month: "short",
                          day: "numeric",
                          year: "numeric",
                        })}`;
                      }

                      return (
                        <tr key={item.id} className="hover:bg-muted/30 transition-colors">
                          <td className="py-4 px-6 font-medium text-foreground">
                            {item.requirement_type.name}
                          </td>
                          <td className="py-4 px-6 whitespace-nowrap">
                            {status === "Approved" && (
                              <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#DEF7EC] text-[#03543F]">
                                Approved
                              </span>
                            )}
                            {status === "Pending" && (
                              <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#FEF08A] text-[#92400E]">
                                Pending
                              </span>
                            )}
                            {status === "Missing" && (
                              <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#FEE2E2] text-[#991B1B]">
                                Missing
                              </span>
                            )}
                            {status === "Returned" && (
                              <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-[#FDE8E8] text-[#C81E1E]">
                                Returned
                              </span>
                            )}
                          </td>
                          <td className="py-4 px-6 text-muted-foreground whitespace-nowrap">
                            {dateLabel}
                          </td>
                          <td className="py-4 px-6 whitespace-nowrap">
                            {isEvent ? (
                              <button
                                className="font-semibold text-brand hover:underline cursor-pointer"
                                aria-label={`Attendance ${item.requirement_type.name}`}
                                onClick={() => setModal({ kind: "event", id: item.id })}
                              >
                                View
                              </button>
                            ) : current ? (
                              <button
                                className="font-semibold text-brand hover:underline cursor-pointer"
                                aria-label={`View ${item.requirement_type.name}`}
                                onClick={() => setModal({ kind: "history", id: item.id })}
                              >
                                View
                              </button>
                            ) : (
                              <button
                                className="font-semibold text-brand hover:underline cursor-pointer"
                                aria-label={`Upload ${item.requirement_type.name}`}
                                onClick={() => setModal({ kind: "upload", id: item.id })}
                              >
                                Upload
                              </button>
                            )}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </Card>
          {modal && (
            <RequirementDialog
              key={`${modal.kind}-${modal.id ?? "new"}`}
              mode={modal}
              data={data}
              enrollmentId={enrollment.id}
              onClose={() => setModal(null)}
              onSaved={() => {
                setRevision((value) => value + 1);
                setModal(null);
                setNotice("Record submitted. Your coordinator will review it.");
              }}
            />
          )}
        </>
      )}
    </div>
  );
}

function RequirementDialog({
  mode,
  data,
  enrollmentId,
  onClose,
  onSaved,
}: {
  mode: { kind: "upload" | "history" | "event"; id?: number };
  data: RequirementData;
  enrollmentId: number;
  onClose: () => void;
  onSaved: () => void;
}) {
  const dialog = useRef<HTMLDialogElement>(null);
  const [currentKind, setCurrentKind] = useState(mode.kind);
  const [selected, setSelected] = useState(String(mode.id ?? ""));
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  useEffect(() => {
    const element = dialog.current;
    element?.showModal();
    return () => element?.close();
  }, []);
  const requirement = data.requirements.find((item) => item.id === Number(selected));
  const history = data.submissions
    .filter((item) => item.program_term_requirement_id === Number(selected))
    .sort((a, b) => b.revision - a.revision);
  async function download(item: Submission) {
    setBusy(true);
    setError("");
    try {
      const response = await fetch(`/api/v1/submissions/${item.id}/document`, {
        credentials: "same-origin",
      });
      if (!response.ok)
        throw new Error(
          response.status === 404
            ? "The uploaded file is unavailable. Upload a replacement or contact your coordinator."
            : "Unable to download this file. Please try again.",
        );
      const url = URL.createObjectURL(await response.blob());
      const link = document.createElement("a");
      link.href = url;
      link.download = item.document.original_name;
      link.click();
      setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Unable to download.");
    } finally {
      setBusy(false);
    }
  }
  return (
    <dialog
      ref={dialog}
      aria-labelledby="requirement-dialog-title"
      onCancel={(event) => {
        event.preventDefault();
        if (!busy) onClose();
      }}
      className="fixed inset-0 m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-xl overflow-y-auto rounded-xl border bg-card p-6 text-foreground shadow-xl backdrop:bg-black/35"
    >
      <div className="flex items-center justify-between pb-3 border-b border-border">
        <h2 id="requirement-dialog-title" className="text-xl font-semibold">
          {currentKind === "upload"
            ? "Upload document"
            : (requirement?.requirement_type.name ?? "Requirement details")}
        </h2>
        <button
          type="button"
          onClick={onClose}
          disabled={busy}
          className="text-muted-foreground hover:text-foreground p-1 rounded-lg"
          aria-label="Close dialog"
        >
          ✕
        </button>
      </div>
      {error && (
        <p role="alert" className="mt-4 rounded-lg bg-brand-soft p-3 text-sm">
          {error}
        </p>
      )}
      {currentKind === "upload" ? (
        <form
          className="mt-5 space-y-5"
          onSubmit={async (event) => {
            event.preventDefault();
            if (busy) return;
            const payload = new FormData(event.currentTarget);
            const file = payload.get("file");
            setError("");
            if (!(file instanceof File) || file.size === 0) {
              setError("Choose a non-empty file.");
              return;
            }
            if (file.size > 10 * 1024 * 1024) {
              setError("Choose a file no larger than 10 MB.");
              return;
            }
            if (!/\.(pdf|jpe?g|png|docx)$/i.test(file.name)) {
              setError("Use PDF, JPG, PNG or DOCX.");
              return;
            }
            setBusy(true);
            try {
              await upload(`/enrollments/${enrollmentId}/requirements`, payload);
              onSaved();
            } catch (cause) {
              setError(
                cause instanceof Error
                  ? cause.message
                  : "Unable to upload. Your selection is retained.",
              );
            } finally {
              setBusy(false);
            }
          }}
        >
          <label className="block text-sm font-medium">
            Requirement
            <select
              name="requirement_id"
              required
              disabled={busy}
              value={selected}
              onChange={(event) => setSelected(event.target.value)}
              className="mt-2 block w-full rounded-lg border bg-background p-3"
            >
              <option value="">Select a document requirement</option>
              {data.requirements
                .filter((item) => item.requirement_type.kind === "document")
                .map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.requirement_type.name}
                  </option>
                ))}
            </select>
          </label>
          <label className="block text-sm font-medium">
            Document file
            <input
              type="file"
              name="file"
              required
              disabled={busy}
              accept=".pdf,.jpg,.jpeg,.png,.docx"
              className="mt-2 block w-full rounded-lg border p-3 text-sm"
            />
          </label>
          <p className="text-xs text-muted-foreground">
            PDF, JPG, PNG or DOCX · Maximum 10 MB. Replacements create a new revision for review and
            preserve earlier files.
          </p>
          <div className="flex justify-end gap-3">
            <Button variant="outline" disabled={busy} onClick={onClose}>
              Cancel
            </Button>
            <Button type="submit" disabled={busy}>
              {busy ? "Uploading…" : "Submit document"}
            </Button>
          </div>
        </form>
      ) : (
        <>
          {mode.kind === "event" && requirement && (
            <EventRequirement
              requirement={requirement}
              enrollmentId={enrollmentId}
              attendance={data.attendances.find(
                (item) => item.program_term_requirement_id === requirement.id,
              )}
              onSaved={onSaved}
            />
          )}
          {mode.kind === "history" && (
            <div className="mt-5 space-y-4">
              {history.map((item) => (
                <section key={item.id} className="rounded-lg border p-4">
                  <p className="break-words font-medium">
                    Revision {item.revision}: {item.document.original_name}
                  </p>
                  <p className="mt-1 text-sm text-muted-foreground">
                    {item.status.replaceAll("_", " ")}
                  </p>
                  {item.reviews.map(
                    (review, index) =>
                      review.comments && (
                        <p key={index} className="mt-2 whitespace-pre-wrap text-sm">
                          {review.comments}
                        </p>
                      ),
                  )}
                  <Button
                    className="mt-3"
                    variant="outline"
                    disabled={busy}
                    onClick={() => download(item)}
                  >
                    Download revision {item.revision}
                  </Button>
                </section>
              ))}
            </div>
          )}
          <div className="mt-6 flex flex-wrap items-center justify-between gap-3 border-t pt-4">
            {currentKind === "history" && (
              <Button variant="outline" disabled={busy} onClick={() => setCurrentKind("upload")}>
                Upload replacement revision
              </Button>
            )}
            <Button disabled={busy} onClick={onClose} className="ml-auto">
              Close
            </Button>
          </div>
        </>
      )}
    </dialog>
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
    types: { id: number; name: string; kind: string }[];
  };
  const [config, setConfig] = useState<Config | null>(null);
  const [term, setTerm] = useState("");
  const [type, setType] = useState("");
  const [required, setRequired] = useState(true);
  const [deployment, setDeployment] = useState(true);
  const [due, setDue] = useState("");
  const [scheduled, setScheduled] = useState("");
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
    setScheduled(current?.scheduled_on ?? "");
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
              scheduled_on:
                config?.types.find((item) => item.id === Number(type))?.kind === "event"
                  ? scheduled || null
                  : null,
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
        {config?.types.find((item) => item.id === Number(type))?.kind === "event" && (
          <label className="text-sm">
            Event date (leave empty until announced)
            <input
              type="date"
              value={scheduled}
              onChange={(event) => setScheduled(event.target.value)}
              className="mt-1 block w-full rounded border p-2"
            />
          </label>
        )}
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
  const [data, setData] = useState<RequirementData | null>(null);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [revision, setRevision] = useState(0);
  const [comments, setComments] = useState<Record<number, string>>({});
  useEffect(() => {
    let active = true;
    api<RequirementData>(`/enrollments/${enrollment.id}/requirements`)
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
        <p>No requirements have been configured for this program and term.</p>
      )}
      <div className="divide-y">
        {data?.requirements.map((requirement) => {
          if (requirement.requirement_type.kind === "event") {
            return (
              <EventRequirement
                key={requirement.id}
                requirement={requirement}
                enrollmentId={enrollment.id}
                attendance={data.attendances.find(
                  (item) => item.program_term_requirement_id === requirement.id,
                )}
                onSaved={() => setRevision((value) => value + 1)}
              />
            );
          }
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

function EventRequirement({
  requirement,
  enrollmentId,
  attendance,
  onSaved,
}: {
  requirement: Requirement;
  enrollmentId: number;
  attendance: Attendance | undefined;
  onSaved: () => void;
}) {
  const user = useSessionUser();
  const [date, setDate] = useState(attendance?.attended_on ?? "");
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState("");
  useEffect(() => setDate(attendance?.attended_on ?? ""), [attendance?.attended_on]);
  async function save(confirm: boolean) {
    setBusy(true);
    setMessage("");
    try {
      const base = `/enrollments/${enrollmentId}/event-attendances`;
      await mutate(
        confirm ? `${base}/${attendance?.id}/confirm` : base,
        confirm ? {} : { requirement_id: requirement.id, attended_on: date },
      );
      onSaved();
      setMessage(
        confirm
          ? "Attendance confirmed."
          : "Attendance recorded; awaiting coordinator confirmation.",
      );
    } catch (cause) {
      setMessage(cause instanceof Error ? cause.message : "Unable to save attendance.");
    } finally {
      setBusy(false);
    }
  }
  return (
    <section className="py-4">
      <h3 className="font-semibold">
        {requirement.requirement_type.name}
        {requirement.is_required ? " (required)" : " (optional)"}
      </h3>
      <p className="text-sm">
        {requirement.scheduled_on
          ? `Scheduled: ${requirement.scheduled_on}`
          : "Date not yet announced"}
      </p>
      <p className="text-sm">
        Status:{" "}
        {attendance?.confirmed_at
          ? "Confirmed"
          : attendance
            ? "Attended — awaiting confirmation"
            : "Not attended"}
        {attendance ? ` — Attended ${attendance.attended_on}` : ""}
      </p>
      {user.role === "student" && !attendance?.confirmed_at && (
        <form
          className="my-3 flex flex-wrap items-end gap-3"
          onSubmit={(event) => {
            event.preventDefault();
            void save(false);
          }}
        >
          <label className="text-sm">
            Attendance date
            <input
              aria-label={`${requirement.requirement_type.name} attendance date`}
              className="mt-1 block rounded border p-2"
              type="date"
              required
              value={date}
              onChange={(event) => setDate(event.target.value)}
            />
          </label>
          <Button type="submit" disabled={busy}>
            Record attendance
          </Button>
        </form>
      )}
      {user.role === "coordinator" && attendance && !attendance.confirmed_at && (
        <Button variant="outline" className="mt-3" disabled={busy} onClick={() => void save(true)}>
          Confirm attendance
        </Button>
      )}
      {message && (
        <p role="status" className="mt-2 text-sm">
          {message}
        </p>
      )}
    </section>
  );
}
