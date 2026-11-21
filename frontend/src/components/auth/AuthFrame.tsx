import type { ReactNode } from "react";
import { Seal } from "@/components/landing/Brand";

const HIGHLIGHTS = [
  "Role-based access to protected records",
  "Audit-logged placement actions",
  "Coordinator-controlled final decisions",
];

/** The sign-in layout: maroon brand panel on the left, the form panel on the right. */
export function AuthFrame({
  title,
  subtitle,
  skipLabel,
  children,
}: {
  title: string;
  subtitle: ReactNode;
  skipLabel: string;
  children: ReactNode;
}) {
  return (
    <div className="grid min-h-svh lg:grid-cols-2">
      <a
        href="#auth-form"
        className="sr-only z-50 rounded-md bg-card px-4 py-2 text-sm font-semibold text-foreground shadow-lg focus:not-sr-only focus:fixed focus:top-4 focus:left-4"
      >
        {skipLabel}
      </a>

      <aside className="brand-gradient relative flex flex-col justify-between gap-12 px-8 py-8 text-brand-foreground lg:px-16 lg:py-12">
        <div className="flex items-center gap-4 animate-in fade-in duration-500 motion-reduce:animate-none">
          <Seal className="size-14 drop-shadow-md" />
          <div>
            <p className="text-xl font-extrabold tracking-tight">
              Intern<span className="text-warn">Match</span>
            </p>
            <p className="mt-0.5 text-eyebrow font-semibold uppercase opacity-85">
              UM Tagum College
            </p>
          </div>
        </div>

        <div className="animate-in fade-in slide-in-from-bottom-2 duration-500 motion-reduce:animate-none">
          <p className="max-w-md text-4xl leading-[1.08] font-extrabold tracking-tight lg:text-5xl">
            Evidence-based internship placement for UMTC.
          </p>
          <p className="mt-6 max-w-md text-base leading-relaxed opacity-90">
            Sign in to manage competency profiles, host establishments, deployment assignments,
            certified hours, supervisor evaluations, and placement analytics.
          </p>
          <ul className="mt-8 space-y-4">
            {HIGHLIGHTS.map((h) => (
              <li key={h} className="flex items-center gap-3 text-sm font-medium">
                <span aria-hidden="true" className="size-1.5 shrink-0 rounded-full bg-warn" />
                {h}
              </li>
            ))}
          </ul>
        </div>

        <p className="text-xs opacity-75">University of Mindanao Tagum College</p>
      </aside>

      <main
        id="auth-form"
        tabIndex={-1}
        className="flex items-center justify-center bg-card px-6 py-12 focus:outline-none sm:px-8 lg:py-16"
      >
        <div className="w-full max-w-md animate-in fade-in slide-in-from-bottom-2 duration-500 motion-reduce:animate-none">
          <h1 className="text-3xl font-extrabold tracking-tight text-foreground">{title}</h1>
          <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{subtitle}</p>
          {children}
        </div>
      </main>
    </div>
  );
}
