import { createFileRoute } from "@tanstack/react-router";
import { AuthFrame } from "@/components/auth/AuthFrame";
import { ResetPasswordForm } from "@/components/auth/ResetPasswordForm";

export const Route = createFileRoute("/reset-password")({
  // The emailed link carries the one-time token and the account email.
  validateSearch: (search: Record<string, unknown>): { token?: string; email?: string } => ({
    ...(typeof search["token"] === "string" ? { token: search["token"] } : {}),
    ...(typeof search["email"] === "string" ? { email: search["email"] } : {}),
  }),
  head: () => ({
    meta: [
      { title: "Choose a new password — InternMatch UMTC" },
      { name: "robots", content: "noindex" },
      // Keep the one-time token out of Referer headers sent to other sites.
      { name: "referrer", content: "no-referrer" },
    ],
  }),
  component: ResetPasswordPage,
});

function ResetPasswordPage() {
  const { token, email } = Route.useSearch();
  return (
    <AuthFrame
      title="Choose a new password"
      subtitle="Use at least 12 characters with uppercase and lowercase letters, a number and a symbol."
      skipLabel="Skip to the form"
    >
      <ResetPasswordForm token={token ?? ""} email={email ?? ""} />
    </AuthFrame>
  );
}
