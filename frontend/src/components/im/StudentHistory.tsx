import { useEffect, useRef, useState } from "react";
import { api } from "@/lib/api";
import { Button } from "./ui";

type History = {
  student: {
    student_number: string;
    user: {
      name: string;
      email: string | null;
      profile: null | { address: string | null; contact_number: string | null; bio: string | null };
    };
  };
  enrollments: {
    id: number;
    year_level: number;
    status: string;
    required_minutes: number;
    enrolled_on: string | null;
    program_term: { program: { code: string }; academic_term: { name: string } };
    placements: {
      id: number;
      status: string;
      starts_on: string;
      ends_on: string | null;
      host_establishment: { name: string };
      opportunity: { title: string } | null;
    }[];
  }[];
};
export function StudentHistory({
  enrollment,
  onClose,
}: {
  enrollment: number;
  onClose: () => void;
}) {
  const dialog = useRef<HTMLDialogElement>(null);
  const [data, setData] = useState<History | null>(null),
    [error, setError] = useState("");
  useEffect(() => {
    dialog.current?.showModal();
    let active = true;
    api<History>(`/coordinator-students/${enrollment}/history`)
      .then((value) => {
        if (active) setData(value);
      })
      .catch((e) => {
        if (active) setError(e.message);
      });
    return () => {
      active = false;
    };
  }, [enrollment]);
  return (
    <dialog
      ref={dialog}
      onCancel={onClose}
      aria-label="Student profile and history"
      className="m-auto max-h-[85vh] w-[min(95vw,800px)] overflow-y-auto rounded-xl border bg-card p-6 text-foreground backdrop:bg-black/40"
    >
      <div className="flex justify-between gap-4">
        <h2 className="text-xl font-semibold">Student profile and history</h2>
        <Button variant="outline" onClick={onClose}>
          Close
        </Button>
      </div>
      {error && <p role="alert">{error}</p>}
      {!data && !error && <p>Loading profile…</p>}
      {data && (
        <>
          <h3 className="mt-5 text-lg font-semibold">{data.student.user.name}</h3>
          <p>Student ID: {data.student.student_number}</p>
          <p>Email: {data.student.user.email || "Not provided"}</p>
          <p>Contact: {data.student.user.profile?.contact_number || "Not provided"}</p>
          <p>Address: {data.student.user.profile?.address || "Not provided"}</p>
          {data.student.user.profile?.bio && <p>{data.student.user.profile.bio}</p>}
          <h3 className="mt-6 font-semibold">Enrollment and placement history</h3>
          {data.enrollments.map((record) => (
            <section key={record.id} className="my-3 rounded-lg border p-4">
              <h4 className="font-semibold">
                {record.program_term.program.code} · {record.program_term.academic_term.name}
              </h4>
              <p>
                Year {record.year_level} · {record.status} · {record.required_minutes / 60} required
                OJT hours
              </p>
              <p>Enrolled: {record.enrolled_on?.slice(0, 10) || "Not recorded"}</p>
              {record.placements.length === 0 ? (
                <p className="mt-2">Not Placed · Not assigned</p>
              ) : (
                record.placements.map((placement) => (
                  <div key={placement.id} className="mt-3 border-t pt-2">
                    <p>
                      {placement.host_establishment.name} · {placement.status}
                    </p>
                    <p>{placement.opportunity?.title || "No opportunity recorded"}</p>
                    <p>
                      {placement.starts_on?.slice(0, 10)} —{" "}
                      {placement.ends_on?.slice(0, 10) || "No end date"}
                    </p>
                  </div>
                ))
              )}
            </section>
          ))}
        </>
      )}
    </dialog>
  );
}
