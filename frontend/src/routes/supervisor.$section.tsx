import { createFileRoute } from "@tanstack/react-router";
import { SupervisorSection } from "@/pages/supervisor";

export const Route = createFileRoute("/supervisor/$section")({
  component: RouteComponent,
});

function RouteComponent() {
  const { section } = Route.useParams();
  return <SupervisorSection section={section} />;
}
