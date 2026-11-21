import { createFileRoute } from "@tanstack/react-router";
import { AuthFrame } from "@/components/auth/AuthFrame";
import { ForgotPasswordForm } from "@/components/auth/ForgotPasswordForm";

export const Route = createFileRoute("/forgot-password")({
  head: () => ({
    meta: [
      { title: "Forgot password — InternMatch UMTC" },
      { name: "description", content: "Request a link to reset your InternMatch password." },
      { name: "robots", content: "noindex" },
    ],
  }),
  component: ForgotPasswordPage,
});

function ForgotPasswordPage() {
  return (
    <AuthFrame
      title="Reset your password"
      subtitle="Enter the email address on your InternMatch account. If it belongs to an active account, we will email you a link to choose a new password."
      skipLabel="Skip to the form"
    >
      <ForgotPasswordForm />
    </AuthFrame>
  );
}
