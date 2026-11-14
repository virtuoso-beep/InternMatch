import { createFileRoute } from "@tanstack/react-router";
import { AdminSection } from "@/pages/admin";

export const Route = createFileRoute("/admin/$section")({
  component: AdminPage,
});
function AdminPage() {
  const { section } = Route.useParams();
  return <AdminSection section={section} />;
}
