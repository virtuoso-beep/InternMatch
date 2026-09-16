import { createFileRoute } from "@tanstack/react-router";
import { AdminSection } from "@/pages/admin";

export const Route = createFileRoute("/admin/$section")({
  component: RouteComponent,
});

function RouteComponent() {
  const { section } = Route.useParams();
  return <AdminSection section={section} />;
}
