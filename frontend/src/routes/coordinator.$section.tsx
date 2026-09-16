import { createFileRoute } from "@tanstack/react-router";
import { CoordinatorSection } from "@/pages/coordinator";

export const Route = createFileRoute("/coordinator/$section")({
  component: RouteComponent,
});

function RouteComponent() {
  const { section } = Route.useParams();
  return <CoordinatorSection section={section} />;
}
