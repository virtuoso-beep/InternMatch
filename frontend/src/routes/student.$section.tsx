import { createFileRoute } from "@tanstack/react-router";
import { StudentSection } from "@/pages/student";

export const Route = createFileRoute("/student/$section")({
  component: RouteComponent,
});

function RouteComponent() {
  const { section } = Route.useParams();
  return <StudentSection section={section} />;
}
