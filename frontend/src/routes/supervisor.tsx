import { createFileRoute } from "@tanstack/react-router";
import { AppShell } from "@/components/im/AppShell";

export const Route = createFileRoute("/supervisor")({
  head: () => ({
    meta: [
      { title: "Host Supervisor — InternMatch" },
      { name: "description", content: "Verify intern attendance, submit evaluations, and manage internship slots." },
      { property: "og:title", content: "Host Supervisor — InternMatch" },
      { property: "og:description", content: "Verify intern attendance, submit evaluations, and manage internship slots." },
    ],
  }),
  component: () => <AppShell role="supervisor" />,
});
