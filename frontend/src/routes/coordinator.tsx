import { createFileRoute } from "@tanstack/react-router";
import { AppShell } from "@/components/im/AppShell";

export const Route = createFileRoute("/coordinator")({
  head: () => ({
    meta: [
      { title: "Practicum Coordinator — InternMatch" },
      { name: "description", content: "Review recommendations, approve placements, and monitor practicum compliance." },
      { property: "og:title", content: "Practicum Coordinator — InternMatch" },
      { property: "og:description", content: "Review recommendations, approve placements, and monitor practicum compliance." },
    ],
  }),
  component: () => <AppShell role="coordinator" />,
});
