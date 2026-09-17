import { createContext, useContext, useEffect, useState, type ReactNode } from "react";
import { Link, useNavigate } from "@tanstack/react-router";
import { ApiError, currentUser, type SessionUser } from "@/lib/api";
import { ROLES, type RoleKey } from "@/lib/internmatch";

const SessionContext = createContext<SessionUser | null>(null);

export function useSessionUser(): SessionUser {
  const user = useContext(SessionContext);
  if (!user) throw new Error("Session user requested outside authenticated portal.");
  return user;
}

export function SessionGuard({ role, children }: { role: RoleKey; children: ReactNode }) {
  const navigate = useNavigate();
  const [user, setUser] = useState<SessionUser | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [retry, setRetry] = useState(0);

  useEffect(() => {
    let active = true;
    setUser(null);
    setError(null);
    currentUser().then((session) => {
      if (!active) return;
      if (session.role !== role) {
        void navigate({ to: ROLES[session.role].base, replace: true });
        return;
      }
      setUser(session);
    }).catch((cause: unknown) => {
      if (!active) return;
      if (cause instanceof ApiError && (cause.status === 401 || cause.status === 403)) {
        void navigate({ to: "/auth", replace: true });
      } else {
        setError("Unable to check your session. Check the connection and try again.");
      }
    });
    return () => { active = false; };
  }, [role, navigate, retry]);

  if (!user || user.role !== role) {
    return <main className="mx-auto max-w-lg p-8" aria-live="polite">
      <p>{error ?? "Checking your session…"}</p>
      {error && <button className="mt-4 mr-4 underline" onClick={() => setRetry((value) => value + 1)}>Try again</button>}
      <Link to="/auth" className="mt-4 inline-block underline">Sign in</Link>
    </main>;
  }
  return <SessionContext.Provider value={user}>{children}</SessionContext.Provider>;
}
