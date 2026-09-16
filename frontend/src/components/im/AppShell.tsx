import { Link, Outlet, useRouterState } from "@tanstack/react-router";
import { useEffect, useRef, useState } from "react";
import { ROLES, type RoleKey } from "@/lib/internmatch";
import { cx } from "./ui";
import sealAsset from "@/assets/umtc-seal.png.asset.json";

type Note = { title: string; body: string; time: string; unread: boolean };

const NOTIFICATIONS: Record<RoleKey, Note[]> = {
  student: [
    { title: "Placement approved", body: "DataCore Solutions Inc. approved by the coordinator.", time: "2h ago", unread: true },
    { title: "Week 6 journal due", body: "Submit your weekly journal before Aug 30, 2026.", time: "Yesterday", unread: true },
    { title: "New recommendation", body: "Davao Region IT Hub now matches your profile at 90%.", time: "3d ago", unread: false },
  ],
  coordinator: [
    { title: "12 placements awaiting review", body: "Batch B recommendations are ready for approval.", time: "1h ago", unread: true },
    { title: "MOA expiring", body: "Tagum Digital Services MOA expires in 21 days.", time: "Yesterday", unread: true },
    { title: "Requirement backlog", body: "8 students have missing insurance certificates.", time: "2d ago", unread: false },
  ],
  supervisor: [
    { title: "Attendance to verify", body: "3 intern time logs need your certification.", time: "30m ago", unread: true },
    { title: "Evaluation window open", body: "Midterm evaluations close Sep 12, 2026.", time: "2d ago", unread: false },
  ],
  dean: [
    { title: "Equity report ready", body: "AY 2026–2027 placement equity summary published.", time: "4h ago", unread: true },
    { title: "Accreditation packet", body: "Program compliance documents updated.", time: "1w ago", unread: false },
  ],
  admin: [
    { title: "Backup completed", body: "Nightly database backup finished successfully.", time: "6h ago", unread: true },
    { title: "New account request", body: "2 host supervisor accounts pending provisioning.", time: "Yesterday", unread: true },
  ],
};

export function AppShell({ role }: { role: RoleKey }) {
  const cfg = ROLES[role];
  const pathname = useRouterState({ select: (s) => s.location.pathname });
  const [open, setOpen] = useState(false);
  const [menu, setMenu] = useState<"none" | "bell" | "user">("none");
  const barRef = useRef<HTMLDivElement>(null);
  const notes = NOTIFICATIONS[role];
  const unread = notes.filter((n) => n.unread).length;

  useEffect(() => {
    const onClick = (e: MouseEvent) => {
      if (barRef.current && !barRef.current.contains(e.target as Node)) setMenu("none");
    };
    const onKey = (e: KeyboardEvent) => e.key === "Escape" && setMenu("none");
    document.addEventListener("mousedown", onClick);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onClick);
      document.removeEventListener("keydown", onKey);
    };
  }, []);

  const isActive = (section: string) => {
    const target = section ? `${cfg.base}/${section}` : cfg.base;
    return pathname === target || pathname === `${target}/`;
  };

  return (
    <div className="min-h-screen bg-background">
      <header className="sticky top-0 z-30 grid h-16 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 bg-brand px-4 text-brand-foreground sm:px-5">
        <div className="flex min-w-0 items-center gap-3">
          <button
            type="button"
            aria-label="Toggle navigation"
            onClick={() => setOpen((v) => !v)}
            className="shrink-0 rounded-md px-2 py-1 text-lg lg:hidden"
          >
            ☰
          </button>
          <Link to="/" className="flex min-w-0 items-center gap-2 text-xl font-bold tracking-tight">
            <img src={sealAsset.url} alt="UM Tagum College seal" className="size-8 shrink-0" />
            <span className="truncate">InternMatch</span>
          </Link>
        </div>

        <div ref={barRef} className="relative flex shrink-0 items-center gap-2">
          {/* Notifications */}
          <button
            type="button"
            aria-label={`Notifications${unread ? `, ${unread} unread` : ""}`}
            aria-expanded={menu === "bell"}
            onClick={() => setMenu((m) => (m === "bell" ? "none" : "bell"))}
            className="relative flex size-9 items-center justify-center rounded-full transition-colors hover:bg-brand-foreground/15"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="size-5">
              <path d="M18 8a6 6 0 1 0-12 0c0 7-3 8-3 8h18s-3-1-3-8" strokeLinecap="round" strokeLinejoin="round" />
              <path d="M13.7 21a2 2 0 0 1-3.4 0" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            {unread > 0 && (
              <span className="absolute top-1 right-1 flex size-4 items-center justify-center rounded-full bg-warn text-[10px] font-bold text-foreground">
                {unread}
              </span>
            )}
          </button>

          {menu === "bell" && (
            <div className="absolute top-12 right-0 z-40 w-[19rem] overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-lg sm:w-80">
              <div className="flex items-center justify-between border-b border-border px-4 py-3">
                <p className="text-sm font-semibold">Notifications</p>
                <span className="text-xs text-muted-foreground">{unread} unread</span>
              </div>
              <ul className="max-h-80 overflow-y-auto">
                {notes.map((n) => (
                  <li
                    key={n.title}
                    className={cx("border-b border-border px-4 py-3 last:border-0", n.unread && "bg-brand-soft/60")}
                  >
                    <div className="flex items-start justify-between gap-3">
                      <p className="text-sm font-semibold">{n.title}</p>
                      <span className="shrink-0 text-[11px] text-muted-foreground">{n.time}</span>
                    </div>
                    <p className="mt-1 text-xs text-muted-foreground">{n.body}</p>
                  </li>
                ))}
              </ul>
              {role === "student" && (
                <Link
                  to="/student/$section"
                  params={{ section: "notifications" }}
                  onClick={() => setMenu("none")}
                  className="block border-t border-border px-4 py-3 text-center text-sm font-semibold text-brand hover:bg-muted"
                >
                  View all notifications
                </Link>
              )}
            </div>
          )}

          {/* Account / role switcher */}
          <button
            type="button"
            aria-label="Account menu"
            aria-expanded={menu === "user"}
            onClick={() => setMenu((m) => (m === "user" ? "none" : "user"))}
            className="flex items-center gap-2 rounded-full py-1 pr-2 pl-1 transition-colors hover:bg-brand-foreground/15"
          >
            <span className="flex size-9 items-center justify-center rounded-full bg-brand-foreground/15 text-sm font-semibold">
              {cfg.initials}
            </span>
            <span className="hidden max-w-[14rem] truncate text-sm opacity-95 sm:inline">{cfg.userLabel}</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="size-4 opacity-80">
              <path d="m6 9 6 6 6-6" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </button>

          {menu === "user" && (
            <div className="absolute top-12 right-0 z-40 w-64 overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-lg">
              <div className="border-b border-border px-4 py-3">
                <p className="text-sm font-semibold">{cfg.title}</p>
                <p className="truncate text-xs text-muted-foreground">{cfg.userLabel}</p>
              </div>
              <Link
                to={`${cfg.base}/$section` as "/student/$section"}
                params={{ section: "profile" }}
                onClick={() => setMenu("none")}
                className="flex items-center gap-2 px-4 py-2.5 text-sm font-medium hover:bg-muted"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="size-4">
                  <circle cx="12" cy="8" r="3.5" />
                  <path d="M4.5 20a7.5 7.5 0 0 1 15 0" strokeLinecap="round" />
                </svg>
                My profile
              </Link>
              <Link
                to="/"
                onClick={() => setMenu("none")}
                className="block border-t border-border px-4 py-3 text-sm font-semibold text-brand hover:bg-muted"
              >
                Sign out
              </Link>
            </div>
          )}
        </div>
      </header>

      <div className="flex">
        <aside
          className={cx(
            "fixed top-16 bottom-0 left-0 z-20 w-60 shrink-0 overflow-y-auto border-r border-border bg-card px-4 py-6 transition-transform lg:sticky lg:top-16 lg:h-[calc(100vh-4rem)] lg:translate-x-0",
            open ? "translate-x-0" : "-translate-x-full",
          )}
        >
          {cfg.nav.map((group) => (
            <div key={group.group} className="mb-5">
              <p className="mb-2 px-2 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase">
                {group.group}
              </p>
              <nav className="space-y-0.5">
                {group.items.map((item) => (
                  <Link
                    key={item.label}
                    to={item.section ? `${cfg.base}/${item.section}` : cfg.base}
                    onClick={() => setOpen(false)}
                    className={cx(
                      "block rounded-md px-3 py-2 text-sm transition-colors",
                      isActive(item.section)
                        ? "bg-brand-soft font-semibold text-brand"
                        : "text-foreground hover:bg-muted",
                    )}
                  >
                    {item.label}
                  </Link>
                ))}
              </nav>
            </div>
          ))}
        </aside>

        <main className="min-w-0 flex-1 px-5 py-7 lg:px-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
