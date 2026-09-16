import { createFileRoute } from "@tanstack/react-router";
import { DeanSection } from "@/pages/dean";

export const Route = createFileRoute("/dean/")({
  component: () => <DeanSection section="" />,
});
