import { createFileRoute } from "@tanstack/react-router";
import { DeanSection } from "@/pages/dean";

export const Route = createFileRoute("/dean/$section")({
  component: RouteComponent,
});

function RouteComponent() {
  const { section } = Route.useParams();
  return <DeanSection section={section} />;
}
