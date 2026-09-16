import { createFileRoute } from "@tanstack/react-router";
import { StudentSection } from "@/pages/student";

export const Route = createFileRoute("/student/")({
  component: () => <StudentSection section="" />,
});
