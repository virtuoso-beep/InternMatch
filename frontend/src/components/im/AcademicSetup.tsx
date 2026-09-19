import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import type { PlacementReference } from "@/lib/placement";
import { Button, Card, CardTitle } from "./ui";
import { RequirementConfiguration } from "./RequirementWorkspace";

export function AcademicSetup() {
  const [reference, setReference] = useState<PlacementReference | null>(null);
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [term, setTerm] = useState({
    code: "",
    academic_year: "",
    name: "",
    starts_on: "",
    ends_on: "",
  });
  const [enrollment, setEnrollment] = useState({
    student_email: "",
    student_number: "",
    program_id: "",
    academic_term_id: "",
    year_level: "",
    enrolled_on: "",
    target_completion_on: "",
  });
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
  return (
    <>
      <RequirementConfiguration />
      <div className="mb-6 grid gap-4 lg:grid-cols-2">
        <Card>
          <CardTitle>Create academic term</CardTitle>
          <form
            className="space-y-3"
            onSubmit={async (event) => {
              event.preventDefault();
              setBusy(true);
              setMessage("");
              try {
                await mutate("/academic-terms", term);
                setReference(await api<PlacementReference>("/placement-reference"));
                setMessage("Academic term saved.");
              } catch (cause) {
                setMessage(cause instanceof Error ? cause.message : "Unable to save term.");
              } finally {
                setBusy(false);
              }
            }}
          >
            {(
              [
                ["code", "Term code"],
                ["academic_year", "Academic year"],
                ["name", "Term name"],
                ["starts_on", "Term start"],
                ["ends_on", "Term end"],
              ] as const
            ).map(([key, label]) => (
              <label key={key} className="block text-sm">
                {label}
                <input
                  required
                  className="mt-1 block w-full rounded border p-2"
                  type={key.endsWith("_on") ? "date" : "text"}
                  value={term[key]}
                  onChange={(event) =>
                    setTerm((current) => ({ ...current, [key]: event.target.value }))
                  }
                />
              </label>
            ))}
            <Button type="submit" disabled={busy}>
              Create term
            </Button>
          </form>
        </Card>
        <Card>
          <CardTitle>Enroll a student account</CardTitle>
          <p className="mb-3 text-sm">
            Use the email of the existing student account. Program hours must be
            department-confirmed.
          </p>
          <form
            className="space-y-3"
            onSubmit={async (event) => {
              event.preventDefault();
              setBusy(true);
              setMessage("");
              try {
                await mutate("/enrollments", {
                  ...enrollment,
                  program_id: Number(enrollment.program_id),
                  academic_term_id: Number(enrollment.academic_term_id),
                  year_level: Number(enrollment.year_level),
                  target_completion_on: enrollment.target_completion_on || null,
                });
                setMessage("Student enrollment saved.");
              } catch (cause) {
                setMessage(cause instanceof Error ? cause.message : "Unable to enroll student.");
              } finally {
                setBusy(false);
              }
            }}
          >
            {(
              [
                ["student_email", "Student email"],
                ["student_number", "Institutional student number"],
                ["year_level", "Year level"],
                ["enrolled_on", "Enrollment date"],
                ["target_completion_on", "Target completion date"],
              ] as const
            ).map(([key, label]) => (
              <label key={key} className="block text-sm">
                {label}
                <input
                  required={key !== "target_completion_on"}
                  className="mt-1 block w-full rounded border p-2"
                  type={
                    key.endsWith("_on")
                      ? "date"
                      : key === "student_number"
                        ? "text"
                        : key === "student_email"
                          ? "email"
                          : "number"
                  }
                  min="1"
                  value={enrollment[key]}
                  onChange={(event) =>
                    setEnrollment((current) => ({ ...current, [key]: event.target.value }))
                  }
                />
              </label>
            ))}
            <label className="block text-sm">
              Program
              <select
                required
                className="mt-1 block w-full rounded border p-2"
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
            </label>
            <label className="block text-sm">
              Academic term
              <select
                required
                className="mt-1 block w-full rounded border p-2"
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
            </label>
            <Button type="submit" disabled={busy}>
              Save enrollment
            </Button>
          </form>
        </Card>
        {message && (
          <p role="status" className="lg:col-span-2">
            {message}
          </p>
        )}
      </div>
    </>
  );
}
