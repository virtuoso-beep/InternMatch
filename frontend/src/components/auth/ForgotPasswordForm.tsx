import { Link } from "@tanstack/react-router";
import { AlertCircle, CheckCircle2, Loader2, Mail } from "lucide-react";
import { useEffect, useRef, useState, type FormEvent } from "react";
import { ApiError, mutate } from "@/lib/api";
import { forgotPasswordSchema } from "@/lib/signIn";
import { AuthField } from "./AuthField";
import { authButtonClass as buttonClass } from "./authStyles";

/** Request a reset link. The answer is the same whether or not the address has an account. */
export function ForgotPasswordForm() {
  const [email, setEmail] = useState("");
  const [error, setError] = useState<string | undefined>();
  const [alert, setAlert] = useState("");
  const [sent, setSent] = useState<{ message: string; minutes: number } | null>(null);
  const [busy, setBusy] = useState(false);
  const [lockedUntil, setLockedUntil] = useState(0);
  const [now, setNow] = useState(() => Date.now());
  const input = useRef<HTMLInputElement>(null);
  const wait = Math.max(0, Math.ceil((lockedUntil - now) / 1000));

  useEffect(() => {
    if (!lockedUntil) return;
    const timer = window.setInterval(() => setNow(Date.now()), 1000);
    return () => window.clearInterval(timer);
  }, [lockedUntil]);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    if (busy || wait > 0) return;
    const parsed = forgotPasswordSchema.safeParse({ email });
    if (!parsed.success) {
      setError(parsed.error.issues[0]?.message ?? "Enter a valid email address.");
      input.current?.focus();
      return;
    }
    setError(undefined);
    setAlert("");
    setBusy(true);
    try {
      const result = await mutate<{ message: string; expires_in_minutes: number }>(
        "/forgot-password",
        {
          email: parsed.data.email,
        },
      );
      setSent({ message: result.message, minutes: result.expires_in_minutes });
    } catch (cause) {
      if (cause instanceof ApiError && cause.status === 429) {
        setNow(Date.now());
        setLockedUntil(Date.now() + (cause.retryAfter ?? 60) * 1000);
        setAlert("Too many reset requests. Please wait before trying again.");
      } else if (cause instanceof ApiError && cause.status === 422) {
        setError(cause.errors?.["email"]?.[0] ?? "Enter a valid email address.");
      } else {
        setAlert(cause instanceof Error ? cause.message : "The request could not be sent.");
      }
    } finally {
      setBusy(false);
    }
  };

  if (sent) {
    return (
      <div role="status" className="mt-8 space-y-6">
        <p className="flex gap-3 rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-foreground">
          <CheckCircle2 aria-hidden="true" size={18} className="mt-0.5 shrink-0 text-success" />
          <span>
            {sent.message} Check your inbox for an email from InternMatch. The link works once and
            expires in {sent.minutes} minutes.
          </span>
        </p>
        <p className="text-sm text-muted-foreground">
          No email after a few minutes? Check your spam folder, or ask your practicum coordinator or
          the system administrator. If your account was never activated, use your activation link
          instead.
        </p>
        <Link
          to="/auth"
          className="block text-center text-sm font-semibold text-brand hover:underline"
        >
          ← Back to sign in
        </Link>
      </div>
    );
  }

  return (
    <form className="mt-8 space-y-6" onSubmit={submit} noValidate>
      <AuthField
        ref={input}
        id="email"
        label="Email address"
        icon={Mail}
        type="email"
        autoComplete="email"
        autoCapitalize="none"
        spellCheck={false}
        placeholder="The email address on your account"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        error={error}
      />
      <div aria-live="polite">
        {alert && (
          <p
            role="alert"
            className="flex gap-3 rounded-lg border border-brand/20 bg-brand-soft px-4 py-3 text-sm font-medium text-brand"
          >
            <AlertCircle aria-hidden="true" size={16} className="mt-0.5 shrink-0" />
            {alert}
          </p>
        )}
      </div>
      <button type="submit" disabled={busy || wait > 0} className={buttonClass}>
        {busy ? (
          <>
            <Loader2 aria-hidden="true" size={16} className="animate-spin" />
            Sending…
          </>
        ) : wait > 0 ? (
          `Try again in ${Math.floor(wait / 60)}:${String(wait % 60).padStart(2, "0")}`
        ) : (
          "Send reset link"
        )}
      </button>
      <p className="text-center">
        <Link to="/auth" className="text-sm font-semibold text-brand hover:underline">
          ← Back to sign in
        </Link>
      </p>
    </form>
  );
}
