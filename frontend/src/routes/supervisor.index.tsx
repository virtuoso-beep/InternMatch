import { createFileRoute } from "@tanstack/react-router";
import { SupervisorSection } from "@/pages/supervisor";

export const Route = createFileRoute("/supervisor/")({
  component: () => <SupervisorSection section="" />,
});
