import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { useState } from "react";
import { ROLES } from "@/lib/internmatch";

import sealAsset from "@/assets/umtc-seal.png.asset.json";
import { login } from "@/lib/api";

export const Route = createFileRoute("/auth")({
  head: () => ({
    meta: [
      { title: "Sign in — InternMatch UMTC" },
      {
        name: "description",
        content:
          "Sign in to InternMatch with your institutional credentials to manage competency profiles, deployments, hours, evaluations, and placement analytics.",
      },
      { property: "og:title", content: "Sign in — InternMatch UMTC" },
      { property: "og:description", content: "Evidence-based internship placement for UM Tagum College." },
      { property: "og:type", content: "website" },
      { name: "twitter:card", content: "summary_large_image" },
    ],
  }),
  component: AuthPage,
});

const HIGHLIGHTS = [
  "Role-based access to protected records",
  "Audit-logged placement actions",
  "Coordinator-controlled final decisions",
];

function AuthPage() {
  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false);
  const [keepSignedIn, setKeepSignedIn] = useState(false);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email.trim() || !email.includes("@")) {
      setError("Enter your institutional email address.");
      return;
    }
    if (!password) {
      setError("Enter your password.");
      return;
    }
    setError(null);
    setSubmitting(true);
    try {
      const user = await login(email.trim(), password, keepSignedIn);
      setPassword("");
      await navigate({ to: ROLES[user.role].base });
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Sign-in failed. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="grid min-h-screen lg:grid-cols-2">
      {/* Left brand panel */}
      <aside className="brand-gradient relative flex flex-col justify-between px-8 py-8 text-brand-foreground lg:px-14 lg:py-10">
        <div className="flex items-center gap-3">
          <img src={sealAsset.url} alt="UM Tagum College seal" className="size-14 drop-shadow-md" />
          <div>
            <p className="text-xl font-extrabold tracking-tight">
              Intern<span className="text-warn">Match</span>
            </p>
            <p className="text-[10px] font-semibold tracking-[0.22em] uppercase opacity-85">UM Tagum College</p>
          </div>
        </div>

        <div className="py-12 lg:py-0">
          <h1 className="max-w-md text-4xl leading-[1.08] font-extrabold tracking-tight lg:text-[2.9rem]">
            Evidence-based internship placement for UMTC.
          </h1>
          <p className="mt-5 max-w-md text-sm leading-relaxed opacity-90">
            Sign in to manage competency profiles, host establishments, deployment assignments, certified hours,
            supervisor evaluations, and placement analytics.
          </p>
          <ul className="mt-8 space-y-3.5">
            {HIGHLIGHTS.map((h) => (
              <li key={h} className="flex items-center gap-3 text-sm font-medium">
                <span className="size-1.5 shrink-0 rounded-full bg-warn" />
                {h}
              </li>
            ))}
          </ul>
        </div>

        <p className="text-xs opacity-75">University of Mindanao Tagum College · Department of Computing Education</p>
      </aside>

      {/* Right form panel */}
      <main className="flex items-center justify-center bg-card px-6 py-12">
        <div className="w-full max-w-md">
          <h2 className="text-3xl font-extrabold tracking-tight text-foreground">Sign in to your account</h2>
          <p className="mt-2 text-sm text-muted-foreground">
            Use the institutional credentials issued by the system administrator.
          </p>

          <form className="mt-8 space-y-5" onSubmit={submit} noValidate>
            {/* Email */}
            <label className="block">
              <span className="mb-1.5 block text-sm font-semibold text-foreground">Institutional email</span>
              <div className="relative">
                <svg
                  className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  aria-hidden="true"
                >
                  <rect x="3" y="5" width="18" height="14" rx="2" />
                  <path d="m3 7 9 6 9-6" />
                </svg>
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="name.000000.tc@umindanao.edu.ph"
                  autoComplete="email"
                  className="w-full rounded-lg border border-input bg-background py-3 pr-4 pl-10 text-sm outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/20"
                />
              </div>
            </label>

            {/* Password */}
            <label className="block">
              <span className="mb-1.5 flex items-center justify-between">
                <span className="text-sm font-semibold text-foreground">Password</span>
                <button
                  type="button"
                  className="text-xs font-semibold text-brand hover:underline"
                  onClick={() => setError("Password resets are handled by the system administrator.")}
                >
                  Forgot password?
                </button>
              </span>
              <div className="relative">
                <svg
                  className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="2"
                  aria-hidden="true"
                >
                  <rect x="4" y="11" width="16" height="9" rx="2" />
                  <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                </svg>
                <input
                  type={showPassword ? "text" : "password"}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  autoComplete="current-password"
                  className="w-full rounded-lg border border-input bg-background py-3 pr-11 pl-10 text-sm outline-none transition-colors focus:border-brand focus:ring-2 focus:ring-brand/20"
                />
                <button
                  type="button"
                  aria-label={showPassword ? "Hide password" : "Show password"}
                  onClick={() => setShowPassword((v) => !v)}
                  className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
                >
                  {showPassword ? (
                    <svg className="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                      <circle cx="12" cy="12" r="3" />
                      <path d="m4 4 16 16" />
                    </svg>
                  ) : (
                    <svg className="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                  )}
                </button>
              </div>
            </label>

            {/* Keep signed in */}
            <label className="flex cursor-pointer items-center gap-2.5 select-none">
              <input
                type="checkbox"
                checked={keepSignedIn}
                onChange={(e) => setKeepSignedIn(e.target.checked)}
                className="size-4 accent-[var(--brand)]"
              />
              <span className="text-sm text-foreground">Keep me signed in on this device</span>
            </label>

            {error && (
              <p role="alert" className="rounded-md bg-brand-soft px-3.5 py-2.5 text-sm font-medium text-brand">
                {error}
              </p>
            )}

            <button
              type="submit"
              disabled={submitting}
              className="w-full rounded-lg bg-brand py-3 text-sm font-bold text-brand-foreground shadow-sm transition-all hover:opacity-90 active:scale-[0.99] disabled:opacity-70"
            >
              {submitting ? "Signing in…" : "Sign in"}
            </button>

            <p className="pt-1 text-center text-sm text-muted-foreground">
              No account yet? Accounts are provisioned by the system administrator.
            </p>

            <p className="pt-3 text-center">
              <Link to="/" className="text-sm font-semibold text-brand hover:underline">
                ← Back to overview
              </Link>
            </p>
          </form>
        </div>
      </main>
    </div>
  );
}
