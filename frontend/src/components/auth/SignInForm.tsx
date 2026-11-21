import { Link, useNavigate } from "@tanstack/react-router";
import { AlertCircle, Loader2, Lock, Mail, WifiOff } from "lucide-react";
import { useEffect, useRef, useState, type FormEvent } from "react";
import { ApiError, login } from "@/lib/api";
import { ROLES } from "@/lib/internmatch";
import { managementUrl } from "@/lib/management";
import {
  signInDestination,
  validateSignIn,
  type SignInField,
  type SignInValues,
} from "@/lib/signIn";
import { AuthField, PasswordField } from "./AuthField";

type Alert = { kind: "credentials" | "network" | "error"; message: string };

const formatWait = (seconds: number) =>
  `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, "0")}`;

/**
 * Sign-in form. Client rules mirror the server (LoginRequest); the server stays authoritative and returns
 * one generic message for unknown accounts, wrong passwords and accounts not yet activated.
 */
export function SignInForm({ redirect }: { redirect?: string | undefined }) {
  const navigate = useNavigate();
  const [values, setValues] = useState<SignInValues>({ identifier: "", password: "" });
  const [keepSignedIn, setKeepSignedIn] = useState(false);
  const [errors, setErrors] = useState<Partial<Record<SignInField, string>>>({});
  const [attempted, setAttempted] = useState(false);
  const [alert, setAlert] = useState<Alert | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [lockedUntil, setLockedUntil] = useState(0);
  const [now, setNow] = useState(() => Date.now());
  const [ready, setReady] = useState(false);
  const identifierRef = useRef<HTMLInputElement>(null);
  const passwordRef = useRef<HTMLInputElement>(null);

  useEffect(() => setReady(true), []);
  const waitSeconds = Math.max(0, Math.ceil((lockedUntil - now) / 1000));
  useEffect(() => {
    if (!lockedUntil) return;
    const timer = window.setInterval(() => {
      const current = Date.now();
      setNow(current);
      if (current >= lockedUntil) {
        setLockedUntil(0);
        setAlert(null);
      }
    }, 1000);
    return () => window.clearInterval(timer);
  }, [lockedUntil]);

  const update = (field: SignInField, value: string) => {
    const next = { ...values, [field]: value };
    setValues(next);
    // After the first submit, re-check as the person types so errors clear as soon as they are fixed.
    if (attempted) setErrors(validateSignIn(next));
  };

  const submit = async (event?: FormEvent) => {
    event?.preventDefault();
    if (submitting || waitSeconds > 0) return;
    setAttempted(true);
    const found = validateSignIn(values);
    setErrors(found);
    if (found.identifier) return identifierRef.current?.focus();
    if (found.password) return passwordRef.current?.focus();

    setAlert(null);
    setSubmitting(true);
    try {
      const user = await login(values.identifier.trim(), values.password, keepSignedIn);
      setValues((current) => ({ ...current, password: "" }));
      if (user.role === "admin") window.location.assign(managementUrl());
      else
        await navigate({ href: signInDestination(ROLES[user.role].base, redirect), replace: true });
    } catch (cause) {
      setValues((current) => ({ ...current, password: "" }));
      if (!(cause instanceof ApiError)) {
        setAlert({ kind: "error", message: "Sign-in failed. Please try again." });
      } else if (cause.status === 0) {
        setAlert({ kind: "network", message: cause.message });
      } else if (cause.status === 429) {
        const until = Date.now() + (cause.retryAfter ?? 60) * 1000;
        setNow(Date.now());
        setLockedUntil(until);
        setAlert({
          kind: "error",
          message: "Too many sign-in attempts. For your security, sign-in is paused for a moment.",
        });
      } else if (cause.status === 422) {
        setAlert({
          kind: "credentials",
          message:
            cause.errors?.["email"]?.[0] ?? "The provided credentials could not be authenticated.",
        });
        passwordRef.current?.focus();
      } else {
        setAlert({ kind: "error", message: cause.message });
      }
    } finally {
      setSubmitting(false);
    }
  };

  const locked = waitSeconds > 0;
  return (
    <form
      className="mt-8 space-y-6"
      onSubmit={submit}
      noValidate
      aria-describedby={alert ? "sign-in-alert" : undefined}
    >
      <AuthField
        ref={identifierRef}
        id="identifier"
        label="Student ID or email"
        icon={Mail}
        type="text"
        inputMode="email"
        autoComplete="username"
        autoCapitalize="none"
        spellCheck={false}
        placeholder="6-digit Student ID or email address"
        value={values.identifier}
        onChange={(e) => update("identifier", e.target.value)}
        error={errors.identifier}
      />

      <PasswordField
        ref={passwordRef}
        id="password"
        label="Password"
        icon={Lock}
        autoComplete="current-password"
        placeholder="••••••••"
        value={values.password}
        onChange={(e) => update("password", e.target.value)}
        error={errors.password}
        labelAction={
          <Link
            to="/forgot-password"
            className="rounded-sm text-xs font-semibold text-brand underline-offset-4 transition-colors hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
          >
            Forgot password?
          </Link>
        }
      />

      <label className="flex w-fit cursor-pointer items-center gap-3 select-none">
        <input
          type="checkbox"
          checked={keepSignedIn}
          onChange={(e) => setKeepSignedIn(e.target.checked)}
          className="size-4 rounded accent-[var(--brand)]"
        />
        <span className="text-sm text-foreground">Keep me signed in on this device</span>
      </label>

      <div aria-live="polite">
        {alert && (
          <div
            id="sign-in-alert"
            role="alert"
            className="flex gap-3 rounded-lg border border-brand/20 bg-brand-soft px-4 py-3 text-sm text-brand animate-in fade-in slide-in-from-top-1 duration-300"
          >
            {alert.kind === "network" ? (
              <WifiOff aria-hidden="true" size={16} className="mt-0.5 shrink-0" />
            ) : (
              <AlertCircle aria-hidden="true" size={16} className="mt-0.5 shrink-0" />
            )}
            <div className="space-y-1">
              <p className="font-medium">{alert.message}</p>
              {alert.kind === "credentials" && (
                <p className="text-brand/85">
                  If you received an activation email, use its link first.
                </p>
              )}
              {alert.kind === "network" && (
                <button
                  type="button"
                  onClick={() => void submit()}
                  className="font-semibold underline underline-offset-4"
                >
                  Try again
                </button>
              )}
            </div>
          </div>
        )}
      </div>

      <button
        type="submit"
        disabled={submitting || !ready || locked}
        className="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-brand text-sm font-bold text-brand-foreground shadow-sm transition-[background-color,box-shadow,transform] duration-fast ease-out hover:bg-brand/90 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70 disabled:shadow-none"
      >
        {submitting ? (
          <>
            <Loader2 aria-hidden="true" size={16} className="animate-spin" />
            Signing in…
          </>
        ) : locked ? (
          `Try again in ${formatWait(waitSeconds)}`
        ) : (
          "Sign in"
        )}
      </button>

      <p className="text-center text-sm text-muted-foreground">
        No account yet? Accounts are provisioned by the system administrator.
      </p>

      <p className="text-center">
        <Link
          to="/"
          className="rounded-sm text-sm font-semibold text-brand underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
        >
          ← Back to overview
        </Link>
      </p>
    </form>
  );
}
