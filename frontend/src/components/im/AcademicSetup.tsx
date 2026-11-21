import { useEffect, useState } from "react";
import { Plus } from "lucide-react";
import { toast } from "sonner";
import { api, mutate } from "@/lib/api";
import type { PlacementReference } from "@/lib/placement";
import { Button, Card, CardTitle, FormField, FormGrid, FormModal, FormSection } from "./ui";
import { inputClass, toFormError, type FormError } from "./formUtils";
import { RequirementConfiguration } from "./RequirementWorkspace";

const emptyTerm = { code: "", academic_year: "", name: "", starts_on: "", ends_on: "" };
const emptyEnrollment = {
  student_email: "",
  student_number: "",
  program_id: "",
  academic_term_id: "",
  year_level: "",
  enrolled_on: "",
  target_completion_on: "",
};

export function AcademicSetup() {
  const [reference, setReference] = useState<PlacementReference | null>(null);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  const [open, setOpen] = useState<"term" | "enrollment" | null>(null);
  const [term, setTerm] = useState(emptyTerm);
  const [enrollment, setEnrollment] = useState(emptyEnrollment);
  useEffect(() => {
    let active = true;
    api<PlacementReference>("/placement-reference")
      .then((value) => {
        if (active) setReference(value);
      })
      .catch((cause: Error) => {
        if (active) setMessage(cause.message);
      });
    return () => {
      active = false;
    };
  }, []);
  const start = (kind: "term" | "enrollment") => {
    setError(null);
    if (kind === "term") setTerm(emptyTerm);
    else setEnrollment(emptyEnrollment);
    setOpen(kind);
  };
  return (
    <>
      <RequirementConfiguration />
      <div className="mb-6 grid gap-4 lg:grid-cols-2">
        <Card>
          <CardTitle>Academic terms</CardTitle>
          <p className="mb-3 text-sm">Create a term before enrolling students into it.</p>
          <Button onClick={() => start("term")}>
            <Plus size={16} aria-hidden="true" />
            Create academic term
          </Button>
        </Card>
        <Card>
          <CardTitle>Enroll a student account</CardTitle>
          <p className="mb-3 text-sm">
            Use the email of the existing student account. Program hours must be
            department-confirmed.
          </p>
          <Button onClick={() => start("enrollment")}>
            <Plus size={16} aria-hidden="true" />
            Enroll student
          </Button>
        </Card>
        {message && (
          <p role="status" className="lg:col-span-2">
            {message}
          </p>
        )}
      </div>
      <FormModal
        open={open === "term"}
        onClose={() => setOpen(null)}
        title="Create academic term"
        description="The new term starts inactive until an administrator sets it active."
        submitLabel="Create term"
        busy={busy}
        error={error}
        onSubmit={async () => {
          setBusy(true);
          setError(null);
          try {
            await mutate("/academic-terms", term);
            setReference(await api<PlacementReference>("/placement-reference"));
            setOpen(null);
            toast.success("Academic term created");
          } catch (cause) {
            setError(toFormError(cause, "Unable to save term."));
          } finally {
            setBusy(false);
          }
        }}
      >
        <FormGrid>
          {(
            [
              ["code", "Term code"],
              ["academic_year", "Academic year"],
              ["name", "Term name"],
              ["starts_on", "Term start"],
              ["ends_on", "Term end"],
            ] as const
          ).map(([key, label]) => (
            <FormField key={key} label={label} required>
              <input
                required
                name={key}
                className={inputClass}
                type={key.endsWith("_on") ? "date" : "text"}
                value={term[key]}
                onChange={(event) =>
                  setTerm((current) => ({ ...current, [key]: event.target.value }))
                }
              />
            </FormField>
          ))}
        </FormGrid>
      </FormModal>
      <FormModal
        open={open === "enrollment"}
        onClose={() => setOpen(null)}
        size="lg"
        title="Enroll student"
        description="Enroll an existing student account in a program and term."
        submitLabel="Save enrollment"
        busy={busy}
        error={error}
        onSubmit={async () => {
          setBusy(true);
          setError(null);
          try {
            await mutate("/enrollments", {
              ...enrollment,
              program_id: Number(enrollment.program_id),
              academic_term_id: Number(enrollment.academic_term_id),
              year_level: Number(enrollment.year_level),
              target_completion_on: enrollment.target_completion_on || null,
            });
            setOpen(null);
            toast.success("Student enrolled");
          } catch (cause) {
            setError(toFormError(cause, "Unable to enroll student."));
          } finally {
            setBusy(false);
          }
        }}
      >
        <FormSection title="Student">
          <FormGrid>
            <FormField label="Student email" required>
              <input
                required
                type="email"
                name="student_email"
                className={inputClass}
                value={enrollment.student_email}
                onChange={(event) =>
                  setEnrollment((current) => ({ ...current, student_email: event.target.value }))
                }
              />
            </FormField>
            <FormField label="Institutional student number" required>
              <input
                required
                name="student_number"
                className={inputClass}
                value={enrollment.student_number}
                onChange={(event) =>
                  setEnrollment((current) => ({ ...current, student_number: event.target.value }))
                }
              />
            </FormField>
          </FormGrid>
        </FormSection>
        <FormSection title="Program and term">
          <FormGrid>
            <FormField label="Program" required>
              <select
                required
                name="program_id"
                className={inputClass}
                value={enrollment.program_id}
                onChange={(event) =>
                  setEnrollment((current) => ({ ...current, program_id: event.target.value }))
                }
              >
                <option value="">Select program</option>
                {reference?.programs.map((program) => (
                  <option key={program.id} value={program.id}>
                    {program.code}
                    {program.required_ojt_hours === null ? " — hours unconfirmed" : ""}
                  </option>
                ))}
              </select>
            </FormField>
            <FormField label="Academic term" required>
              <select
                required
                name="academic_term_id"
                className={inputClass}
                value={enrollment.academic_term_id}
                onChange={(event) =>
                  setEnrollment((current) => ({ ...current, academic_term_id: event.target.value }))
                }
              >
                <option value="">Select term</option>
                {reference?.terms.map((item) => (
                  <option key={item.id} value={item.id}>
                    {item.code} · {item.name}
                  </option>
                ))}
              </select>
            </FormField>
            <FormField label="Year level" required>
              <input
                required
                type="number"
                min="1"
                name="year_level"
                className={inputClass}
                value={enrollment.year_level}
                onChange={(event) =>
                  setEnrollment((current) => ({ ...current, year_level: event.target.value }))
                }
              />
            </FormField>
            <FormField label="Enrollment date" required>
              <input
                required
                type="date"
                name="enrolled_on"
                className={inputClass}
                value={enrollment.enrolled_on}
                onChange={(event) =>
                  setEnrollment((current) => ({ ...current, enrolled_on: event.target.value }))
                }
              />
            </FormField>
            <FormField label="Target completion date">
              <input
                type="date"
                name="target_completion_on"
                className={inputClass}
                value={enrollment.target_completion_on}
                onChange={(event) =>
                  setEnrollment((current) => ({
                    ...current,
                    target_completion_on: event.target.value,
                  }))
                }
              />
            </FormField>
          </FormGrid>
        </FormSection>
      </FormModal>
    </>
  );
}
