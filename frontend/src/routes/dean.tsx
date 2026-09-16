import { createFileRoute } from "@tanstack/react-router";
import { AppShell } from "@/components/im/AppShell";

export const Route = createFileRoute("/dean")({
  head: () => ({
    meta: [
      { title: "Program Chair & Dean — InternMatch" },
      { name: "description", content: "Executive view of placement rates, partner network, and accreditation reporting." },
      { property: "og:title", content: "Program Chair & Dean — InternMatch" },
      { property: "og:description", content: "Executive view of placement rates, partner network, and accreditation reporting." },
    ],
  }),
  component: () => <AppShell role="dean" />,
});
