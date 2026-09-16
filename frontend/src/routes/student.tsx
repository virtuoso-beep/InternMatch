import { createFileRoute } from "@tanstack/react-router";
import { AppShell } from "@/components/im/AppShell";

export const Route = createFileRoute("/student")({
  head: () => ({
    meta: [
      { title: "Student Portal — InternMatch" },
      { name: "description", content: "Track recommendations, requirements, and internship hours as a practicum student." },
      { property: "og:title", content: "Student Portal — InternMatch" },
      { property: "og:description", content: "Track recommendations, requirements, and internship hours as a practicum student." },
    ],
  }),
  component: () => <AppShell role="student" />,
});
