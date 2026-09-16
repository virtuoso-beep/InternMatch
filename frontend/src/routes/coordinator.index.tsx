import { createFileRoute } from "@tanstack/react-router";
import { CoordinatorSection } from "@/pages/coordinator";

export const Route = createFileRoute("/coordinator/")({
  component: () => <CoordinatorSection section="" />,
});
