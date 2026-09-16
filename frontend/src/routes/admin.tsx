import { createFileRoute } from "@tanstack/react-router";
import { AppShell } from "@/components/im/AppShell";

export const Route = createFileRoute("/admin")({
  head: () => ({
    meta: [
      { title: "System Administrator — InternMatch" },
      { name: "description", content: "Manage accounts, roles, records, audit trail, and backups for InternMatch." },
      { property: "og:title", content: "System Administrator — InternMatch" },
      { property: "og:description", content: "Manage accounts, roles, records, audit trail, and backups for InternMatch." },
    ],
  }),
  component: () => <AppShell role="admin" />,
});
