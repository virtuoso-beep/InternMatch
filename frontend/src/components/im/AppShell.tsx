import { Link, Outlet, useRouterState } from "@tanstack/react-router";
import { useEffect, useRef, useState } from "react";
import { ROLES, type RoleKey } from "@/lib/internmatch";
import { cx } from "./ui";
import sealAsset from "@/assets/umtc-seal.png.asset.json";

type Note = { title: string; body: string; time: string; unread: boolean };

const INBOX_MESSAGES = [
  { sender: "Practicum Office", subject: "Placement update for your internship", preview: "Your placement recommendation has been reviewed by the coordinator.", time: "9:42 AM", unread: true },
  { sender: "InternMatch support", subject: "Welcome to InternMatch", preview: "Complete your profile to receive better internship matches.", time: "Yesterday", unread: true },
  { sender: "DataCore Solutions Inc.", subject: "Weekly check-in reminder", preview: "Please submit your weekly journal before Friday.", time: "Aug 28", unread: false },
  { sender: "University of Mindanao", subject: "Academic term announcement", preview: "Important practicum dates for AY 2026-2027.", time: "Aug 25", unread: false },
];

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
  const [sidebarCollapsed, setSidebarCollapsed] = useState(true);
  const [mailHover, setMailHover] = useState(false);
  const [chatOpen, setChatOpen] = useState(false);
  const [chatDraft, setChatDraft] = useState("");
  const [inboxOpen, setInboxOpen] = useState(false);
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
            aria-label={sidebarCollapsed ? "Show sidebar" : "Hide sidebar"}
            aria-expanded={!sidebarCollapsed}
            onClick={() => {
              if (window.matchMedia("(min-width: 1024px)").matches) {
                setSidebarCollapsed((value) => !value);
              } else {
                setOpen((value) => !value);
              }
            }}
            className="flex size-9 shrink-0 items-center justify-center rounded-md transition-colors hover:bg-brand-foreground/15"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="size-5" aria-hidden="true">
              <path d="M4 6h16M4 12h16M4 18h16" strokeLinecap="round" />
            </svg>
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
            <div className="absolute top-12 right-0 z-40 w-76 overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-lg sm:w-80">
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
            <span className="hidden max-w-56 truncate text-sm opacity-95 sm:inline">{cfg.userLabel}</span>
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
          onMouseLeave={() => sidebarCollapsed && setMailHover(false)}
          className={cx(
            "fixed top-16 bottom-0 left-0 z-20 flex shrink-0 border-r border-border bg-card transition-[width,transform] lg:sticky lg:top-16 lg:h-[calc(100vh-4rem)] lg:translate-x-0",
            sidebarCollapsed ? "w-21" : "w-84",
            open ? "translate-x-0" : "-translate-x-full",
          )}
        >
          <nav aria-label="Applications" className="relative flex w-21 shrink-0 flex-col items-center gap-5 bg-[#e8eef7] px-2 pt-6 text-[#132238]">
            <Link
              to={cfg.base}
              onMouseEnter={() => sidebarCollapsed && setMailHover(true)}
              onClick={() => {
                setSidebarCollapsed(false);
                setMailHover(false);
                setOpen(false);
                setInboxOpen(false);
              }}
              className={cx(
                "flex w-full flex-col items-center gap-1 rounded-2xl px-2 py-2 text-xs transition-colors",
                isActive("") ? "bg-[#d2e3fc] font-semibold" : "hover:bg-white/60",
              )}
            >
              <span className="relative flex size-9 items-center justify-center rounded-full bg-[#17365f] text-white shadow-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="size-5" aria-hidden="true">
                  <rect x="4" y="4" width="6" height="6" rx="1" />
                  <rect x="14" y="4" width="6" height="6" rx="1" />
                  <rect x="4" y="14" width="6" height="6" rx="1" />
                  <rect x="14" y="14" width="6" height="6" rx="1" />
                </svg>
                {unread > 0 && <span className="absolute -top-1 -right-2 flex min-w-5 items-center justify-center rounded-full bg-[#c5221f] px-1 text-[10px] font-bold text-white">{unread}</span>}
              </span>
              Dashboard
            </Link>
            <button
              type="button"
              aria-expanded={chatOpen}
              onClick={() => setChatOpen((value) => !value)}
              className="flex w-full flex-col items-center gap-1 rounded-2xl px-2 py-2 text-xs transition-colors hover:bg-white/60"
            >
              <span className="flex size-9 items-center justify-center rounded-full text-[#132238]">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="size-5" aria-hidden="true">
                  <path d="M5 6.5A2.5 2.5 0 0 1 7.5 4h9A2.5 2.5 0 0 1 19 6.5v6a2.5 2.5 0 0 1-2.5 2.5H12l-4.5 4v-4H7.5A2.5 2.5 0 0 1 5 12.5v-6Z" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
              </span>
              Chat
            </button>
            {chatOpen && (
              <section className="absolute top-24 left-full z-50 w-64 overflow-hidden rounded-xl border border-[#d8e1ee] bg-white text-[#132238] shadow-[0_14px_35px_rgba(32,55,85,0.18)]" aria-label="Chat box">
                <div className="flex items-center justify-between border-b border-[#e4eaf2] px-3 py-2.5">
                  <div>
                    <p className="text-sm font-semibold">Chat</p>
                    <p className="text-[11px] text-[#6d7b8f]">InternMatch messages</p>
                  </div>
                  <button type="button" aria-label="Close chat" onClick={() => setChatOpen(false)} className="flex size-7 items-center justify-center rounded-full text-lg text-[#6d7b8f] hover:bg-[#eef3f9]">×</button>
                </div>
                <div className="px-3 py-3">
                  <div className="flex items-start gap-2 rounded-lg bg-[#f1f6fc] px-2.5 py-2">
                    <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-[#17365f] text-[10px] font-semibold text-white">IM</span>
                    <div>
                      <p className="text-xs font-semibold">InternMatch support</p>
                      <p className="mt-0.5 text-[11px] leading-4 text-[#6d7b8f]">How can we help with your internship?</p>
                    </div>
                  </div>
                  <form
                    className="mt-3 flex items-center gap-2 rounded-lg border border-[#d8e1ee] px-2 py-1.5"
                    onSubmit={(event) => {
                      event.preventDefault();
                      setChatDraft("");
                    }}
                  >
                    <input value={chatDraft} onChange={(event) => setChatDraft(event.target.value)} placeholder="Write a message" className="min-w-0 flex-1 bg-transparent text-xs outline-none placeholder:text-[#94a1b2]" />
                    <button type="submit" aria-label="Send message" className="flex size-7 shrink-0 items-center justify-center rounded-full bg-[#17365f] text-white disabled:opacity-40" disabled={!chatDraft.trim()}>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="size-3.5" aria-hidden="true"><path d="m4 4 16 8-16 8 3-8-3-8Z" strokeLinecap="round" strokeLinejoin="round" /><path d="M7 12h13" strokeLinecap="round" /></svg>
                    </button>
                  </form>
                </div>
              </section>
            )}
          </nav>

          <div
            className={cx(
              "min-w-0 overflow-y-auto px-4 py-6",
              sidebarCollapsed
                ? mailHover && "absolute top-0 left-full z-30 h-full w-80 overflow-y-auto rounded-xl border border-[#d8e1ee] bg-white px-4 py-6 text-[#132238] shadow-[0_14px_35px_rgba(32,55,85,0.18)]"
                : "flex-1",
              sidebarCollapsed && !mailHover && "hidden",
            )}
          >
            <button type="button" className="mb-5 flex w-full items-center gap-3 rounded-2xl bg-[#b9e0fa] px-5 py-4 text-left text-base font-semibold text-[#132238] shadow-sm transition-transform hover:-translate-y-0.5" onClick={() => window.dispatchEvent(new CustomEvent("internmatch:compose"))}>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" className="size-5" aria-hidden="true"><path d="m4 16 12.5-12.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" strokeLinecap="round" strokeLinejoin="round" /></svg>
              Compose
            </button>
            {cfg.nav.map((group) => (
              <div key={group.group} className="mb-5">
                <p className="mb-2 px-3 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase">{group.group}</p>
                <nav className="space-y-0.5">
                  {group.items.map((item) => (
                    <Link
                      key={item.label}
                      to={item.section ? `${cfg.base}/${item.section}` : cfg.base}
                      onClick={() => {
                        setOpen(false);
                        setInboxOpen(item.section === "");
                        if (item.section === "") setMailHover(false);
                      }}
                      className={cx(
                        "flex items-center gap-3 rounded-full px-4 py-2 text-sm transition-colors",
                        isActive(item.section) ? "bg-[#d2e3fc] font-semibold text-[#132238]" : "text-foreground hover:bg-muted",
                      )}
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="size-4 shrink-0" aria-hidden="true">
                        {item.section === "" ? <><path d="M4 6.5h16v11H4z" strokeLinecap="round" strokeLinejoin="round" /><path d="m4 7 8 6 8-6" strokeLinecap="round" strokeLinejoin="round" /></> : <><path d="M5 5h14v14H5z" strokeLinecap="round" strokeLinejoin="round" /><path d="M8 9h8M8 13h5" strokeLinecap="round" /></>}
                      </svg>
                      {item.section === "" ? "Inbox" : item.label}
                    </Link>
                  ))}
                </nav>
              </div>
            ))}
          </div>
        </aside>

        <main className="min-w-0 flex-1 px-5 py-7 lg:px-8">
          {inboxOpen ? <InboxView /> : <Outlet />}
        </main>
      </div>
    </div>
  );
}

function InboxView() {
  return (
    <section className="mx-auto max-w-5xl">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <p className="text-sm font-semibold text-brand">Dashboard / Inbox</p>
          <h1 className="mt-1 text-2xl font-bold text-foreground">Inbox</h1>
          <p className="mt-1 text-sm text-muted-foreground">Your latest InternMatch messages and updates.</p>
        </div>
      </div>
      <div className="overflow-hidden rounded-xl border border-border bg-card shadow-[0_1px_2px_rgba(16,24,40,0.04)]">
        <div className="flex items-center justify-between border-b border-border bg-muted/40 px-5 py-4">
          <p className="font-semibold">All messages</p>
          <span className="text-sm text-muted-foreground">{INBOX_MESSAGES.filter((message) => message.unread).length} unread</span>
        </div>
        <div>
          {INBOX_MESSAGES.map((message) => (
            <button key={message.subject} type="button" className="flex w-full items-start gap-4 border-b border-border px-5 py-4 text-left last:border-0 hover:bg-muted/60">
              <span className={cx("mt-1 size-2 shrink-0 rounded-full", message.unread ? "bg-brand" : "bg-transparent")} />
              <span className="min-w-0 flex-1">
                <span className={cx("block text-sm", message.unread ? "font-bold text-foreground" : "font-medium text-foreground")}>{message.sender}</span>
                <span className="mt-1 block truncate text-sm font-semibold text-foreground">{message.subject}</span>
                <span className="mt-1 block truncate text-sm text-muted-foreground">{message.preview}</span>
              </span>
              <span className="shrink-0 text-xs text-muted-foreground">{message.time}</span>
            </button>
          ))}
        </div>
      </div>
    </section>
  );
}
