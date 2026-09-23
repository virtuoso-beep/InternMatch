import { Link, Outlet, useNavigate, useRouterState } from "@tanstack/react-router";
import { useEffect, useRef, useState } from "react";
import { ROLES, type RoleKey } from "@/lib/internmatch";
import { api, logout } from "@/lib/api";
import { cx } from "./ui";
import sealUrl from "@/assets/umtc-seal.png";
import { SessionGuard, useSessionUser } from "./SessionGuard";
import type { NotificationPage } from "./NotificationList";

export function AppShell({ role }: { role: RoleKey }) {
  return (
    <SessionGuard role={role}>
      <AuthenticatedShell role={role} />
    </SessionGuard>
  );
}

function AuthenticatedShell({ role }: { role: RoleKey }) {
  const user = useSessionUser();
  const cfg = ROLES[role];
  const navigate = useNavigate();
  const pathname = useRouterState({ select: (state) => state.location.pathname });
  const [open, setOpen] = useState(false);
  const [collapsed, setCollapsed] = useState(false);
  const [menu, setMenu] = useState<"none" | "bell" | "user">("none");
  const [notifications, setNotifications] = useState<NotificationPage | null>(null);
  const [notificationError, setNotificationError] = useState("");
  const [notificationRevision, setNotificationRevision] = useState(0);
  const [logoutError, setLogoutError] = useState("");
  const [signingOut, setSigningOut] = useState(false);
  const barRef = useRef<HTMLDivElement>(null);
  const initials = user.name
    .split(" ")
    .map((part) => part[0])
    .slice(0, 2)
    .join("");

  useEffect(() => {
    let active = true;
    setNotificationError("");
    api<NotificationPage>("/notifications")
      .then((value) => {
        if (active) setNotifications(value);
      })
      .catch((cause: Error) => {
        if (active) {
          setNotifications(null);
          setNotificationError(cause.message);
        }
      });
    return () => {
      active = false;
    };
  }, [pathname, menu, notificationRevision]);

  useEffect(() => {
    const refresh = () => setNotificationRevision((value) => value + 1);
    window.addEventListener("internmatch:notifications-changed", refresh);
    return () => window.removeEventListener("internmatch:notifications-changed", refresh);
  }, []);

  useEffect(() => {
    const onClick = (event: MouseEvent) => {
      if (barRef.current && !barRef.current.contains(event.target as Node)) setMenu("none");
    };
    const onKey = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setMenu("none");
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", onClick);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onClick);
      document.removeEventListener("keydown", onKey);
    };
  }, []);

  return (
    <div className="min-h-screen bg-background">
      <header className="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 bg-brand px-4 text-brand-foreground">
        <div className="flex min-w-0 items-center gap-3">
          <button
            type="button"
            aria-label="Toggle navigation"
            onClick={() =>
              window.matchMedia("(min-width: 1024px)").matches
                ? setCollapsed((value) => !value)
                : setOpen((value) => !value)
            }
            className="rounded p-2 hover:bg-white/15"
          >
            ☰
          </button>
          <Link to="/" className="flex items-center gap-2 text-xl font-bold">
            <img src={sealUrl} alt="UM Tagum College seal" className="size-8" />
            InternMatch
          </Link>
        </div>
        <div ref={barRef} className="relative flex items-center gap-2">
          <button
            type="button"
            aria-expanded={menu === "bell"}
            onClick={() => setMenu((value) => (value === "bell" ? "none" : "bell"))}
            className="rounded px-3 py-2 text-sm hover:bg-white/15"
          >
            Notifications
            {notifications && notifications.unread_count > 0
              ? ` (${notifications.unread_count})`
              : ""}
          </button>
          <button
            type="button"
            aria-label="Account menu"
            aria-expanded={menu === "user"}
            onClick={() => setMenu((value) => (value === "user" ? "none" : "user"))}
            className="flex items-center gap-2 rounded-full p-1 hover:bg-white/15"
          >
            <span className="flex size-9 items-center justify-center rounded-full bg-white/15 font-semibold">
              {initials}
            </span>
            <span className="hidden max-w-48 truncate text-sm sm:block">{user.name}</span>
          </button>
          {menu === "bell" && (
            <div className="absolute top-12 right-0 z-40 w-80 max-w-[90vw] rounded-lg border border-border bg-card text-foreground shadow-lg">
              <p className="border-b p-4 font-semibold">Notifications</p>
              {notificationError && (
                <p role="alert" className="p-4 text-sm">
                  {notificationError}
                </p>
              )}
              {!notifications && !notificationError && (
                <p role="status" className="p-4">
                  Loading…
                </p>
              )}
              {notifications?.data.length === 0 && (
                <p className="p-4 text-sm">No notifications yet.</p>
              )}
              <ul className="max-h-80 overflow-y-auto">
                {notifications?.data.slice(0, 5).map((item) => (
                  <li
                    key={item.id}
                    className={cx("border-b p-4 text-sm", !item.read_at && "bg-brand-soft")}
                  >
                    <p className="font-semibold">{item.data.title}</p>
                    <p className="mt-1">{item.data.body}</p>
                    <p className="mt-1 text-xs text-muted-foreground">
                      {new Date(item.created_at).toLocaleString()}
                    </p>
                  </li>
                ))}
              </ul>
              <Link
                to={`${cfg.base}/$section` as "/student/$section"}
                params={{ section: "notifications" }}
                onClick={() => setMenu("none")}
                className="block p-4 text-center text-sm font-semibold text-brand"
              >
                View all notifications
              </Link>
            </div>
          )}
          {menu === "user" && (
            <div className="absolute top-12 right-0 z-40 w-64 rounded-lg border border-border bg-card text-foreground shadow-lg">
              <p className="border-b p-4 text-sm font-semibold">{cfg.title}</p>
              <Link
                to={`${cfg.base}/$section` as "/student/$section"}
                params={{ section: "profile" }}
                onClick={() => setMenu("none")}
                className="block px-4 py-3 text-sm hover:bg-muted"
              >
                My profile
              </Link>
              <button
                type="button"
                disabled={signingOut}
                onClick={async () => {
                  setSigningOut(true);
                  setLogoutError("");
                  try {
                    await logout();
                    await navigate({ to: "/auth", replace: true });
                  } catch {
                    setLogoutError("Sign-out failed. Please try again.");
                  } finally {
                    setSigningOut(false);
                  }
                }}
                className="block w-full border-t px-4 py-3 text-left text-sm font-semibold text-brand hover:bg-muted"
              >
                {signingOut ? "Signing out…" : "Sign out"}
              </button>
              {logoutError && (
                <p role="alert" className="px-4 pb-3 text-sm">
                  {logoutError}
                </p>
              )}
            </div>
          )}
        </div>
      </header>
      <div className="flex">
        {open && (
          <button
            aria-label="Close navigation"
            className="fixed inset-0 top-16 z-10 bg-black/25 lg:hidden"
            onClick={() => setOpen(false)}
          />
        )}
        <aside
          className={cx(
            "fixed top-16 bottom-0 left-0 z-20 w-64 shrink-0 overflow-y-auto border-r bg-card p-4 lg:sticky lg:h-[calc(100vh-4rem)] lg:translate-x-0",
            open ? "translate-x-0" : "-translate-x-full",
            collapsed && "lg:hidden",
          )}
        >
          <p className="mb-5 px-3 text-sm font-semibold text-brand">{cfg.title}</p>
          {cfg.nav.map((group) => (
            <div key={group.group} className="mb-5">
              <p className="mb-2 px-3 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                {group.group}
              </p>
              <nav aria-label={group.group} className="space-y-1">
                {group.items.map((item) => {
                  const target = item.section ? `${cfg.base}/${item.section}` : cfg.base;
                  const active = pathname === target || pathname === `${target}/`;
                  return (
                    <Link
                      key={item.section}
                      to={target}
                      aria-current={active ? "page" : undefined}
                      onClick={() => setOpen(false)}
                      className={cx(
                        "block rounded-lg px-3 py-2 text-sm",
                        active ? "bg-brand-soft font-semibold text-brand" : "hover:bg-muted",
                      )}
                    >
                      {item.label}
                    </Link>
                  );
                })}
              </nav>
            </div>
          ))}
        </aside>
        <main className="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">
          <p className="mb-5 rounded border border-warn/40 bg-warn/10 p-3 text-sm">
            Development preview: the implementation audit is ongoing. Some dashboard, placement,
            monitoring, and reporting screens still contain demonstration content.
          </p>
          <Outlet />
        </main>
      </div>
    </div>
  );
}
