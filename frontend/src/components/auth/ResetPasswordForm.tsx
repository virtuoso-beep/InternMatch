import { Link } from "@tanstack/react-router";
import { AlertCircle, Check, CheckCircle2, Circle, Loader2, Lock } from "lucide-react";
import { useRef, useState, type FormEvent } from "react";
import { cx } from "@/components/im/ui";
import { ApiError, mutate } from "@/lib/api";
import { PASSWORD_RULES, validateNewPassword } from "@/lib/signIn";
import { PasswordField } from "./AuthField";
import { authButtonClass } from "./authStyles";

type Field = "password" | "confirmation";

/** Choose a new password from an emailed reset link (token and email come from the link). */
export function ResetPasswordForm({ token, email }: { token: string; email: string }) {
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [errors, setErrors] = useState<Partial<Record<Field, string>>>({});
  const [attempted, setAttempted] = useState(false);
  const [alert, setAlert] = useState("");
  const [expired, setExpired] = useState(false);
  const [done, setDone] = useState(false);
  const [busy, setBusy] = useState(false);
  const passwordRef = useRef<HTMLInputElement>(null);
  const confirmationRef = useRef<HTMLInputElement>(null);

  if (!token || !email) {
    return <InvalidLink />;
  }
  if (done) {
    return (
      <div role="status" className="mt-8 space-y-6">
        <p className="flex gap-3 rounded-lg border border-success/30 bg-success-soft px-4 py-3 text-sm text-foreground">
          <CheckCircle2 aria-hidden="true" size={18} className="mt-0.5 shrink-0 text-success" />
          Your password has been changed. Other devices signed in to your account were signed out.
        </p>
        <Link to="/auth" className={authButtonClass}>
          Sign in with your new password
        </Link>
      </div>
    );
  }
  if (expired) return <InvalidLink />;

  const recheck = (next: { password?: string; confirmation?: string }) => {
    if (attempted)
      setErrors(validateNewPassword(next.password ?? password, next.confirmation ?? confirmation));
  };

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    if (busy) return;
    setAttempted(true);
    const found = validateNewPassword(password, confirmation);
    setErrors(found);
    if (found.password) return passwordRef.current?.focus();
    if (found.confirmation) return confirmationRef.current?.focus();
    setAlert("");
    setBusy(true);
    try {
      await mutate("/reset-password", {
        token,
        email,
        password,
        password_confirmation: confirmation,
      });
      setPassword("");
      setConfirmation("");
      setDone(true);
    } catch (cause) {
      if (cause instanceof ApiError && cause.status === 422 && cause.errors?.["token"]) {
        setExpired(true);
      } else if (cause instanceof ApiError && cause.status === 422 && cause.errors?.["password"]) {
        setErrors({ password: cause.errors["password"][0] ?? "Choose a stronger password." });
      } else {
        setAlert(cause instanceof Error ? cause.message : "Your password could not be changed.");
      }
    } finally {
      setBusy(false);
    }
  };

  return (
    <form className="mt-8 space-y-6" onSubmit={submit} noValidate>
      <p className="text-sm text-muted-foreground">
        Account: <span className="font-medium text-foreground">{email}</span>
      </p>
      <div>
        <PasswordField
          ref={passwordRef}
          id="new-password"
          label="New password"
          icon={Lock}
          autoComplete="new-password"
          value={password}
          onChange={(e) => {
            setPassword(e.target.value);
            recheck({ password: e.target.value });
          }}
          error={errors.password}
          aria-describedby="password-rules"
        />
        <ul
          id="password-rules"
          className="mt-3 grid gap-1 text-sm sm:grid-cols-2"
          aria-label="Password rules"
        >
          {PASSWORD_RULES.map((rule) => {
            const met = rule.test(password);
            return (
              <li
                key={rule.id}
                className={cx(
                  "flex items-center gap-2",
                  met ? "text-success" : "text-muted-foreground",
                )}
              >
                {met ? (
                  <Check aria-hidden="true" size={14} />
                ) : (
                  <Circle aria-hidden="true" size={14} />
                )}
                {rule.label}
                <span className="sr-only">{met ? ": met" : ": not yet met"}</span>
              </li>
            );
          })}
        </ul>
      </div>
      <PasswordField
        ref={confirmationRef}
        id="confirm-password"
        label="Confirm new password"
        icon={Lock}
        autoComplete="new-password"
        value={confirmation}
        onChange={(e) => {
          setConfirmation(e.target.value);
          recheck({ confirmation: e.target.value });
        }}
        error={errors.confirmation}
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
      <button type="submit" disabled={busy} className={authButtonClass}>
        {busy ? (
          <>
            <Loader2 aria-hidden="true" size={16} className="animate-spin" />
            Saving…
          </>
        ) : (
          "Set new password"
        )}
      </button>
    </form>
  );
}

function InvalidLink() {
  return (
    <div role="alert" className="mt-8 space-y-6">
      <p className="flex gap-3 rounded-lg border border-brand/20 bg-brand-soft px-4 py-3 text-sm text-brand">
        <AlertCircle aria-hidden="true" size={16} className="mt-0.5 shrink-0" />
        This password reset link is invalid or has expired. Links work once and expire after 60
        minutes.
      </p>
      <Link to="/forgot-password" className={authButtonClass}>
        Request a new link
      </Link>
      <p className="text-center">
        <Link to="/auth" className="text-sm font-semibold text-brand hover:underline">
          ← Back to sign in
        </Link>
      </p>
    </div>
  );
}
