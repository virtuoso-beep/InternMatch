import { useEffect, useState } from "react";
import { createRoot } from "react-dom/client";
import { currentUser, ApiError, type SessionUser } from "@/lib/api";
import { SessionUserProvider } from "@/components/im/SessionGuard";
import { AdminSection } from "@/pages/admin";
import { managementPath } from "@/lib/management";
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
          <a
            className="mb-4 inline-block text-sm font-semibold underline"
            href={`/manage/${section}`}
            target="_top"
          >
            Manage existing {section} records
          </a>
        )}
        <AdminSection section={section === "dashboard" ? "" : section} />
      </main>
    </SessionUserProvider>
  );
}

if (mount) createRoot(mount).render(<AdminWorkspace />);
