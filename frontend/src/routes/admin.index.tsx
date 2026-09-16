import { createFileRoute } from "@tanstack/react-router";
import { AdminSection } from "@/pages/admin";

export const Route = createFileRoute("/admin/")({
  component: () => <AdminSection section="" />,
});
