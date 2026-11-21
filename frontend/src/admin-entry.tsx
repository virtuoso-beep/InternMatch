import { useEffect, useState } from "react";
import { ExternalLink } from "lucide-react";
import { createRoot } from "react-dom/client";
import { currentUser, ApiError, type SessionUser } from "@/lib/api";
import { SessionUserProvider } from "@/components/im/SessionGuard";
import { AdminSection } from "@/pages/admin";
import { Toaster } from "@/components/ui/sonner";
import { managementPath } from "@/lib/management";
import { readThemePreference, resolveTheme } from "@/lib/theme";
import "./styles.css";

const mount = document.getElementById("admin-workspace");
const section = mount?.dataset["section"] ?? "dashboard";
const destination = (value: string) => managementPath(value);

function AdminWorkspace() {
  const [user, setUser] = useState<SessionUser | null>(null);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    currentUser()
      .then((value) => {
        if (!active) return;
        if (value.role !== "admin") {
          setError("Your account cannot access this management workspace.");
          return;
        }
        setUser(value);
      })
      .catch((cause: unknown) => {
        if (!active) return;
        if (cause instanceof ApiError && (cause.status === 401 || cause.status === 403)) {
          window.top?.location.assign("/manage/login");
        } else setError("Unable to load your session. Refresh this page to retry.");
      });
    return () => {
      active = false;
    };
  }, []);
  // Follow the light/dark choice made in the administration shell (same origin, shared storage).
  useEffect(() => {
    const sync = () =>
      document.documentElement.classList.toggle(
        "dark",
        resolveTheme(readThemePreference()) === "dark",
      );
    sync();
    const query = window.matchMedia("(prefers-color-scheme: dark)");
    window.addEventListener("storage", sync);
    query.addEventListener("change", sync);
    return () => {
      window.removeEventListener("storage", sync);
      query.removeEventListener("change", sync);
    };
  }, []);
  useEffect(() => {
    if (window.top === window) {
      window.location.replace(destination(section));
      return;
    }
    const report = () =>
      window.parent.postMessage(
        {
          type: "internmatch:admin-height",
          height: Math.ceil(mount?.getBoundingClientRect().height ?? 800) + 24,
        },
        window.location.origin,
      );
    const observer = new ResizeObserver(report);
    if (mount) observer.observe(mount);
    report();
    return () => observer.disconnect();
  }, []);
  if (error)
    return (
      <p role="alert" className="p-5 text-brand">
        {error}
      </p>
    );
  if (!user)
    return (
      <p role="status" className="p-5">
        Loading administration…
      </p>
    );
  return (
    <SessionUserProvider user={user} updateUser={setUser}>
      <main
        className="p-1 sm:p-2"
        onClick={(event) => {
          const link = (event.target as Element).closest<HTMLAnchorElement>("a[href]");
          if (!link) return;
          const url = new URL(link.href, window.location.origin);
          if (url.origin === window.location.origin && /^\/admin(?:\/|$)/.test(url.pathname)) {
            event.preventDefault();
            window.top?.location.assign(destination(url.pathname.split("/")[2] ?? "dashboard"));
          }
        }}
      >
        {["hosts", "opportunities", "programs"].includes(section) && (
          <div className="mb-2 flex justify-end">
            <a
              className="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-muted-foreground hover:bg-brand-soft hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
              href={`/manage/${section}`}
              target="_top"
            >
              Open full {section} manager
              <ExternalLink size={12} aria-hidden="true" />
            </a>
          </div>
        )}
        <AdminSection section={section === "dashboard" ? "" : section} />
      </main>
      <Toaster />
    </SessionUserProvider>
  );
}

if (mount) createRoot(mount).render(<AdminWorkspace />);
