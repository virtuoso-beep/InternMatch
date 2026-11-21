import { useEffect, useState } from "react";
import { Download, Plus, Upload } from "lucide-react";
import { toast } from "sonner";
import { ConfirmButton, ReasonButton } from "./actions";
import { api, mutate, upload } from "@/lib/api";
import { RequirementCompliance, type ComplianceSummary } from "./RequirementCompliance";
import { SyntheticToggle } from "./SyntheticToggle";
import { withSynthetic } from "@/lib/synthetic";
import { useSessionUser } from "./SessionGuard";
import {
  Button,
  Card,
  CardTitle,
  cx,
  FormField,
  FormGrid,
  FormModal,
  PageHeader,
  Pill,
  ViewModal,
} from "./ui";
import { inputClass, toFormError, type FormError } from "./formUtils";

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
  const isStudent = user.role === "student";
  useEffect(() => {
    if (!isStudent) return;
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
  }, [isStudent]);
  return (
    <>
      {user.role !== "student" && (
        <PageHeader
          title={user.role === "coordinator" ? "Requirements tracking" : "Requirements"}
          subtitle="Submit documents, record event attendance, and track coordinator confirmation."
        />
      )}
      {["admin", "coordinator"].includes(user.role) && <RequirementConfiguration />}
      {user.role === "student" ? (
        <>
          {error && <p role="alert">{error}</p>}
          {!enrollments && !error && <p role="status">Loading enrollments…</p>}
          {enrollments?.length === 0 && <Card>No enrollment records are available.</Card>}
          <div className="space-y-5">
            {enrollments?.map((item) => (
              <StudentRequirements key={item.id} enrollment={item} />
            ))}
          </div>
        </>
      ) : (
        <RequirementTracking />
      )}
    </>
  );
}

function StudentRequirements({ enrollment }: { enrollment: Enrollment }) {
  const [data, setData] = useState<RequirementData | null>(null);
  const [error, setError] = useState("");
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
            className="bg-brand text-brand-foreground hover:bg-brand/90 font-medium px-4 py-2 rounded-lg shadow-sm"
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
                        ? "bg-brand text-brand-foreground shadow-xs"
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
                    <tr className="border-b border-border/60 text-xs font-semibold text-muted-foreground bg-muted/20">
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
                            {status === "Approved" && <Pill tone="success">Approved</Pill>}
                            {status === "Pending" && <Pill tone="warn">Pending</Pill>}
                            {status === "Missing" && <Pill tone="brand">Missing</Pill>}
                            {status === "Returned" && <Pill tone="brand">Returned</Pill>}
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
  const [file, setFile] = useState<File | null>(null);
  const [currentKind, setCurrentKind] = useState(mode.kind);
  const [selected, setSelected] = useState(String(mode.id ?? ""));
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
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
  if (currentKind === "upload") {
    return (
      <FormModal
        open
        onClose={onClose}
        title="Upload document"
        description="PDF, JPG, PNG or DOCX, up to 10 MB. Replacements create a new revision and keep earlier files."
        submitLabel="Submit document"
        busyLabel="Uploading…"
        busy={busy}
        error={error ? { message: error, fields: {} } : null}
        onSubmit={async () => {
          setError("");
          if (!file || file.size === 0) {
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
          const payload = new FormData();
          payload.set("requirement_id", selected);
          payload.set("file", file);
          setBusy(true);
          try {
            await upload(`/enrollments/${enrollmentId}/requirements`, payload);
            onSaved();
            toast.success("Document submitted", {
              description: "Your coordinator will review it.",
            });
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
        <FormField label="Requirement" required>
          <select
            name="requirement_id"
            required
            disabled={busy}
            value={selected}
            onChange={(event) => setSelected(event.target.value)}
            className={inputClass}
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
        </FormField>
        <FormField label="Document file" required helper="PDF, JPG, PNG or DOCX. Maximum 10 MB.">
          <input
            type="file"
            name="file"
            required
            disabled={busy}
            accept=".pdf,.jpg,.jpeg,.png,.docx"
            onChange={(event) => setFile(event.target.files?.[0] ?? null)}
            className={inputClass}
          />
        </FormField>
      </FormModal>
    );
  }
  return (
    <ViewModal
      open
      onClose={onClose}
      title={requirement?.requirement_type.name ?? "Requirement details"}
      size="md"
      actions={
        currentKind === "history" ? (
          <Button variant="outline" disabled={busy} onClick={() => setCurrentKind("upload")}>
            <Upload size={16} aria-hidden="true" />
            Upload replacement revision
          </Button>
        ) : undefined
      }
    >
      {error && (
        <p role="alert" className="mb-4 rounded-lg bg-brand-soft p-3 text-sm">
          {error}
        </p>
      )}
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
        <div className="space-y-4">
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
                <Download size={16} aria-hidden="true" />
                Download revision {item.revision}
              </Button>
            </section>
          ))}
        </div>
      )}
    </ViewModal>
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
  const [open, setOpen] = useState(false);
  const [formError, setFormError] = useState<FormError | null>(null);
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
      <Button
        disabled={!config}
        onClick={() => {
          setFormError(null);
          setOpen(true);
        }}
      >
        <Plus size={16} aria-hidden="true" />
        Configure requirement
      </Button>
      <FormModal
        open={open}
        onClose={() => setOpen(false)}
        title="Configure requirement"
        description="Saving updates the requirement if it already exists for this program and term."
        submitLabel="Save requirement"
        busy={busy}
        submitDisabled={!config}
        error={formError}
        onSubmit={async () => {
          setBusy(true);
          setFormError(null);
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
            setOpen(false);
            toast.success("Requirement saved");
          } catch (cause) {
            setFormError(toFormError(cause, "Unable to save requirement."));
          } finally {
            setBusy(false);
          }
        }}
      >
        <FormGrid>
          <FormField label="Program and term" required>
            <select
              required
              name="program_term_id"
              value={term}
              onChange={(event) => setTerm(event.target.value)}
              className={inputClass}
            >
              <option value="">Select program and term</option>
              {config?.program_terms.map((item) => (
                <option key={item.id} value={item.id}>
                  {item.program.code} · {item.academic_term.code}
                </option>
              ))}
            </select>
          </FormField>
          <FormField label="Requirement type" required>
            <select
              required
              name="requirement_type_id"
              value={type}
              onChange={(event) => setType(event.target.value)}
              className={inputClass}
            >
              <option value="">Select requirement</option>
              {config?.types.map((item) => (
                <option key={item.id} value={item.id}>
                  {item.name}
                </option>
              ))}
            </select>
          </FormField>
          <FormField label="Due date and time (local)">
            <input
              type="datetime-local"
              name="due_at"
              value={due}
              onChange={(event) => setDue(event.target.value)}
              className={inputClass}
            />
          </FormField>
          {config?.types.find((item) => item.id === Number(type))?.kind === "event" && (
            <FormField label="Event date" helper="Leave empty until the date is announced.">
              <input
                type="date"
                name="scheduled_on"
                value={scheduled}
                onChange={(event) => setScheduled(event.target.value)}
                className={inputClass}
              />
            </FormField>
          )}
        </FormGrid>
        <div className="space-y-2 text-sm">
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              name="is_required"
              checked={required}
              onChange={(event) => setRequired(event.target.checked)}
            />
            Required for completion
          </label>
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              name="required_before_deployment"
              checked={deployment}
              onChange={(event) => setDeployment(event.target.checked)}
            />
            Required before deployment
          </label>
        </div>
      </FormModal>
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

type TrackingRow = Enrollment & RequirementData & { is_synthetic?: boolean };
type TrackingPage = {
  data: TrackingRow[];
  current_page: number;
  last_page: number;
  total: number;
  summary: ComplianceSummary;
};

/**
 * Coordinator and admin view: one request per page of 20 enrollments (GET /requirement-tracking), with the
 * compliance summary computed on the server. Replaces one request per enrollment.
 */
function RequirementTracking() {
  const [data, setData] = useState<TrackingPage | null>(null);
  const [error, setError] = useState("");
  const [page, setPage] = useState(1);
  const [query, setQuery] = useState("");
  const [search, setSearch] = useState("");
  const [includeSynthetic, setIncludeSynthetic] = useState(false);
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    setError("");
    const params = new URLSearchParams({ page: String(page) });
    if (search) params.set("search", search);
    api<TrackingPage>(withSynthetic(`/requirement-tracking?${params}`, includeSynthetic))
      .then((value) => {
        if (active) setData(value);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [page, search, includeSynthetic, revision]);
  const refresh = () => setRevision((value) => value + 1);
  return (
    <>
      <div className="mb-4 flex flex-wrap items-end gap-3">
        <form
          className="flex flex-wrap items-end gap-2"
          onSubmit={(event) => {
            event.preventDefault();
            setPage(1);
            setSearch(query.trim());
          }}
        >
          <label className="text-sm">
            Find student
            <input
              type="search"
              className="mt-1 block rounded-lg border border-input bg-card px-3 py-2 text-sm"
              placeholder="Name or student ID"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
            />
          </label>
          <Button type="submit" variant="outline">
            Search
          </Button>
        </form>
        <SyntheticToggle
          checked={includeSynthetic}
          onChange={(value) => {
            setPage(1);
            setIncludeSynthetic(value);
          }}
        />
      </div>
      {error && (
        <p role="alert">
          {error}{" "}
          <button className="underline" onClick={refresh}>
            Retry
          </button>
        </p>
      )}
      {!data && !error && <p role="status">Loading requirement tracking…</p>}
      {data && <RequirementCompliance summary={data.summary} onRefresh={refresh} />}
      {data?.data.length === 0 && <Card>No enrollment records match.</Card>}
      <div className="space-y-5">
        {data?.data.map((row) => (
          <RequirementsForEnrollment key={row.id} record={row} onChanged={refresh} />
        ))}
      </div>
      {data && data.last_page > 1 && (
        <div className="mt-4 flex items-center gap-3">
          <Button variant="outline" disabled={page === 1} onClick={() => setPage((v) => v - 1)}>
            Previous
          </Button>
          <span className="text-sm">
            Page {data.current_page} of {data.last_page} · {data.total} enrollments
          </span>
          <Button
            variant="outline"
            disabled={page === data.last_page}
            onClick={() => setPage((v) => v + 1)}
          >
            Next
          </Button>
        </div>
      )}
    </>
  );
}

function RequirementsForEnrollment({
  record,
  onChanged,
}: {
  record: TrackingRow;
  onChanged: () => void;
}) {
  const user = useSessionUser();
  const enrollment = record;
  const data: RequirementData = record;
  return (
    <Card>
      <CardTitle>
        {enrollment.student?.user?.name ?? enrollment.program_term.program.code} ·{" "}
        {enrollment.program_term.academic_term.name}
      </CardTitle>
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
                onSaved={onChanged}
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
                  <div className="mt-3 flex flex-wrap gap-2">
                    {latest.status === "submitted" && (
                      <ConfirmButton
                        size="sm"
                        title={`Start reviewing ${requirement.requirement_type.name}?`}
                        description={`Mark revision ${latest.revision} (${latest.document.original_name}) as under review. The student sees the new status.`}
                        confirmLabel="Start review"
                        success="Review started"
                        onConfirm={() =>
                          mutate(`/submissions/${latest.id}/review`, {
                            status: "under_review",
                            comments: null,
                          })
                        }
                        onDone={onChanged}
                      >
                        Start review
                      </ConfirmButton>
                    )}
                    <ConfirmButton
                      size="sm"
                      variant="primary"
                      title={`Approve ${requirement.requirement_type.name}?`}
                      description={`Approve revision ${latest.revision} (${latest.document.original_name}) for ${enrollment.student?.user?.name ?? "this student"}. The student is notified.`}
                      confirmLabel="Approve"
                      success="Requirement approved"
                      onConfirm={() =>
                        mutate(`/submissions/${latest.id}/review`, {
                          status: "approved",
                          comments: null,
                        })
                      }
                      onDone={onChanged}
                    >
                      Approve
                    </ConfirmButton>
                    <ReasonButton
                      size="sm"
                      title={`Return ${requirement.requirement_type.name} for correction`}
                      description="The student sees this reason and can upload a corrected revision."
                      submitLabel="Return document"
                      field="comments"
                      success="Document returned to the student"
                      onSubmit={(reason) =>
                        mutate(`/submissions/${latest.id}/review`, {
                          status: "rejected",
                          comments: reason,
                        })
                      }
                      onDone={onChanged}
                    >
                      Return for correction
                    </ReasonButton>
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
          className="my-3 space-y-3"
          onSubmit={(event) => {
            event.preventDefault();
            void save(false);
          }}
        >
          <FormField label="Attendance date" required>
            <input
              aria-label={`${requirement.requirement_type.name} attendance date`}
              name="attended_on"
              className={inputClass}
              type="date"
              required
              value={date}
              onChange={(event) => setDate(event.target.value)}
            />
          </FormField>
          <Button type="submit" disabled={busy}>
            {busy ? "Saving…" : "Record attendance"}
          </Button>
        </form>
      )}
      {user.role === "coordinator" && attendance && !attendance.confirmed_at && (
        <ConfirmButton
          className="mt-3"
          title={`Confirm attendance at ${requirement.requirement_type.name}?`}
          description={`The student recorded attendance on ${attendance.attended_on}. Confirming completes this requirement.`}
          confirmLabel="Confirm attendance"
          success="Attendance confirmed"
          onConfirm={() =>
            mutate(`/enrollments/${enrollmentId}/event-attendances/${attendance.id}/confirm`, {})
          }
          onDone={onSaved}
        >
          Confirm attendance
        </ConfirmButton>
      )}
      {message && (
        <p role="status" className="mt-2 text-sm">
          {message}
        </p>
      )}
    </section>
  );
}
