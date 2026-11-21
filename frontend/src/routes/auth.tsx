import { createFileRoute } from "@tanstack/react-router";
import { SignInForm } from "@/components/auth/SignInForm";
import { AuthFrame } from "@/components/auth/AuthFrame";

export const Route = createFileRoute("/auth")({
  // The page the person was trying to open; honoured after sign-in only inside their own portal.
  validateSearch: (search: Record<string, unknown>): { redirect?: string } =>
    typeof search["redirect"] === "string" ? { redirect: search["redirect"] } : {},
  head: () => ({
    meta: [
      { title: "Sign in — InternMatch UMTC" },
      {
        name: "description",
        content:
          "Sign in to InternMatch with your institutional credentials to manage competency profiles, deployments, hours, evaluations, and placement analytics.",
      },
      { property: "og:title", content: "Sign in — InternMatch UMTC" },
      {
        property: "og:description",
        content: "Evidence-based internship placement for UM Tagum College.",
      },
      { property: "og:type", content: "website" },
      { name: "twitter:card", content: "summary" },
    ],
  }),
  component: AuthPage,
});

function AuthPage() {
  const { redirect } = Route.useSearch();
  return (
    <AuthFrame
      title="Sign in to your account"
      subtitle="Use the institutional credentials issued by the system administrator."
      skipLabel="Skip to sign in"
    >
      <SignInForm redirect={redirect} />
    </AuthFrame>
  );
}
