import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { useEffect, useRef, useState } from "react";
import { ROLES, ROLE_ORDER, type RoleKey } from "@/lib/internmatch";
import { cx } from "@/components/im/ui";
import sealAsset from "@/assets/umtc-seal.png.asset.json";

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
  const [role, setRole] = useState<RoleKey>("student");
  const [menuOpen, setMenuOpen] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [keepSignedIn, setKeepSignedIn] = useState(false);
  const [email, setEmail] = useState("j.delacruz.000000.tc@umindanao.edu.ph");
  const [password, setPassword] = useState("password");
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!menuOpen) return;
    const onDown = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) setMenuOpen(false);
    };
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setMenuOpen(false);
    };
    document.addEventListener("mousedown", onDown);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onDown);
      document.removeEventListener("keydown", onKey);
    };
  }, [menuOpen]);

  const submit = (e: React.FormEvent) => {
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
    window.setTimeout(() => navigate({ to: ROLES[role].base }), 450);
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
            {/* Role dropdown */}
            <div ref={menuRef} className="relative">
              <span className="mb-1.5 block text-sm font-semibold text-foreground">Sign in as</span>
              <button
                type="button"
                aria-haspopup="listbox"
                aria-expanded={menuOpen}
                onClick={() => setMenuOpen((v) => !v)}
                className={cx(
                  "flex w-full items-center justify-between rounded-lg border bg-background px-4 py-3 text-sm transition-colors",
                  menuOpen ? "border-brand ring-2 ring-brand/20" : "border-input hover:border-brand/60",
                )}
              >
                <span className="font-medium text-foreground">{ROLES[role].title}</span>
                <svg
                  className={cx("size-4 text-muted-foreground transition-transform", menuOpen && "rotate-180")}
                  viewBox="0 0 20 20"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  <path
                    fillRule="evenodd"
                    d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                    clipRule="evenodd"
                  />
                </svg>
              </button>
              {menuOpen && (
                <ul
                  role="listbox"
                  className="absolute z-20 mt-2 w-full overflow-hidden rounded-lg border border-border bg-card py-1 shadow-lg"
                >
                  {ROLE_ORDER.map((r) => (
                    <li key={r}>
                      <button
                        type="button"
                        role="option"
                        aria-selected={r === role}
                        onClick={() => {
                          setRole(r);
                          setMenuOpen(false);
                        }}
                        className={cx(
                          "flex w-full items-center justify-between px-4 py-2.5 text-left text-sm transition-colors",
                          r === role ? "bg-brand-soft font-semibold text-brand" : "text-foreground hover:bg-muted",
                        )}
                      >
                        {ROLES[r].title}
                        {r === role && (
                          <svg className="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path
                              fillRule="evenodd"
                              d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0Z"
                              clipRule="evenodd"
                            />
                          </svg>
                        )}
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </div>

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
