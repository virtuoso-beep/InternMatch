import { Link, Outlet, useNavigate, useRouterState } from "@tanstack/react-router";
import {
  Activity,
  Award,
  Bell,
  BookOpen,
  Briefcase,
  Building,
  Building2,
  CalendarCheck,
  CalendarRange,
  ChartColumn,
  ChevronDown,
  ChevronsLeft,
  ChevronsRight,
  Circle,
  ClipboardCheck,
  ClipboardList,
  Clock,
  DatabaseBackup,
  Download,
  FileCheck2,
  FileSignature,
  FileText,
  GraduationCap,
  History,
  LayoutDashboard,
  ListOrdered,
  KeyRound,
  LogOut,
  MapPinned,
  Menu,
  MessageSquare,
  Monitor,
  Moon,
  Scale,
  Settings,
  ShieldCheck,
  SlidersHorizontal,
  Sun,
  TrendingUp,
  Upload,
  UserCog,
  UserRound,
  Users,
  X,
  type LucideIcon,
} from "lucide-react";
import { useCallback, useEffect, useRef, useState } from "react";
import { ROLES, type RoleKey } from "@/lib/internmatch";
import { api, logout } from "@/lib/api";
import { applyTheme, readThemePreference, type ThemePreference } from "@/lib/theme";
import { ConfirmDialog, cx } from "./ui";
import { ChangePasswordModal } from "./ChangePassword";
// Small optimized copy of the seal (the original asset is 868 KB).
const sealUrl = "/images/brand/umtc-seal-128.webp";
import { SessionGuard, useSessionUser } from "./SessionGuard";
import type { NotificationPage } from "./NotificationList";
import { ChatWorkspace } from "./ChatWorkspace";

/** Icon per navigation section (same section key means the same kind of page in every role). */
const SECTION_ICONS: Record<string, LucideIcon> = {
  "": LayoutDashboard,
  competencies: Award,
  opportunities: Briefcase,
  recommendations: ListOrdered,
  internship: CalendarCheck,
  requirements: FileCheck2,
  map: MapPinned,
  approvals: ClipboardCheck,
  students: GraduationCap,
  hosts: Building2,
  moa: FileSignature,
  progress: Activity,
  "monitoring-settings": SlidersHorizontal,
  evaluations: ClipboardList,
  analytics: ChartColumn,
  interns: Users,
  attendance: Clock,
  company: Building,
  performance: TrendingUp,
  equity: Scale,
  accreditation: FileText,
  export: Download,
  audit: History,
  notifications: Bell,
  users: UserCog,
  roles: ShieldCheck,
  "student-import": Upload,
  programs: BookOpen,
  terms: CalendarRange,
  "chat-settings": MessageSquare,
  backup: DatabaseBackup,
  settings: Settings,
  profile: UserRound,
};

const SIDEBAR_KEY = "internmatch:sidebar-collapsed";

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
  const programLabel = user.program_label ?? "No program assigned";
  const navigate = useNavigate();
  const pathname = useRouterState({ select: (state) => state.location.pathname });
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [collapsed, setCollapsed] = useState(false);
  const [branding, setBranding] = useState({
    system_name: "InternMatch",
    institution: "University of Mindanao Tagum College",
    display_timezone: "Asia/Manila",
  });
  const [chatOpen, setChatOpen] = useState(false);
  const canChat = ["student", "coordinator", "supervisor"].includes(role);
  const [menu, setMenu] = useState<"none" | "bell" | "user" | "theme">("none");
  const [notifications, setNotifications] = useState<NotificationPage | null>(null);
  const [notificationError, setNotificationError] = useState("");
  const [notificationRevision, setNotificationRevision] = useState(0);
  const [logoutError, setLogoutError] = useState("");
  const [confirmSignOut, setConfirmSignOut] = useState(false);
  const [passwordOpen, setPasswordOpen] = useState(false);
  const [signingOut, setSigningOut] = useState(false);
  const [theme, setTheme] = useState<ThemePreference>("system");
  const barRef = useRef<HTMLDivElement>(null);
  const initials = user.name
    .split(" ")
    .map((part) => part[0])
    .slice(0, 2)
    .join("");
  const ThemeIcon = theme === "dark" ? Moon : theme === "light" ? Sun : Monitor;

  // Sidebar collapse preference (desktop) and theme preference are per browser.
  useEffect(() => {
    try {
      setCollapsed(window.localStorage.getItem(SIDEBAR_KEY) === "1");
    } catch {
      // Storage blocked: keep the expanded default.
    }
    setTheme(readThemePreference());
  }, []);
  const chooseTheme = (value: ThemePreference) => {
    setTheme(value);
    applyTheme(value);
    setMenu("none");
  };
  useEffect(() => {
    if (theme !== "system") return;
    const query = window.matchMedia("(prefers-color-scheme: dark)");
    const update = () => applyTheme("system");
    query.addEventListener("change", update);
    return () => query.removeEventListener("change", update);
  }, [theme]);
  const toggleCollapsed = () =>
    setCollapsed((value) => {
      try {
        window.localStorage.setItem(SIDEBAR_KEY, value ? "0" : "1");
      } catch {
        // Not persisted when storage is blocked.
      }
      return !value;
    });

  useEffect(() => {
    let active = true;
    const refresh = () =>
      api<{ data: { system_name: string; institution: string; display_timezone: string } }>(
        "/branding",
      )
        .then((r) => {
          if (active) setBranding(r.data);
        })
        .catch(() => {});
    void refresh();
    window.addEventListener("internmatch:branding-updated", refresh);
    return () => {
      active = false;
      window.removeEventListener("internmatch:branding-updated", refresh);
    };
  }, []);

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
    setDrawerOpen(false);
    setMenu("none");
  }, [pathname]);

  useEffect(() => {
    const onClick = (event: MouseEvent) => {
      if (barRef.current && !barRef.current.contains(event.target as Node)) setMenu("none");
    };
    const onKey = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setMenu("none");
        setDrawerOpen(false);
        setChatOpen(false);
      }
    };
    document.addEventListener("mousedown", onClick);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onClick);
      document.removeEventListener("keydown", onKey);
    };
  }, []);

  const signOut = useCallback(async () => {
    setSigningOut(true);
    setLogoutError("");
    try {
      await logout();
      await navigate({ to: "/auth", replace: true });
    } catch {
      setLogoutError("Sign-out failed. Try again.");
    } finally {
      setSigningOut(false);
    }
  }, [navigate]);

  const target = (section: string) => (section ? `${cfg.base}/${section}` : cfg.base);
  const isActive = (section: string) =>
    pathname === target(section) || pathname === `${target(section)}/`;
  const current = cfg.nav.flatMap((g) => g.items).find((item) => isActive(item.section));
  const currentGroup = cfg.nav.find((g) => g.items.some((item) => isActive(item.section)));
  // Pages reached from the account menu or notification bell are not in the sidebar.
  const segment = pathname.split("/").filter(Boolean)[1] ?? "";
  const pageFallback =
    segment === "profile"
      ? "My profile"
      : segment === "notifications"
        ? "Notifications"
        : segment
          ? segment.replaceAll("-", " ").replace(/^./, (c) => c.toUpperCase())
          : "Dashboard";
  const unread = notifications?.unread_count ?? 0;

  const navigation = (rail: boolean) => (
    <nav
      aria-label={`${cfg.title} navigation`}
      className="flex-1 space-y-5 overflow-y-auto px-3 py-4 im-hide-scrollbar"
    >
      {cfg.nav.map((group) => (
        <div key={group.group}>
          {rail ? (
            <div
              aria-hidden="true"
              className="mx-2 mb-2 border-t border-[#d2e3fc] first:hidden dark:border-border"
            />
          ) : (
            <p className="mb-1.5 px-3 text-xs font-semibold text-muted-foreground">{group.group}</p>
          )}
          <ul className="space-y-0.5">
            {group.items.map((item) => {
              const Icon = SECTION_ICONS[item.section] ?? Circle;
              const active = isActive(item.section);
              return (
                <li key={item.section} className="group/nav relative">
                  <Link
                    to={target(item.section)}
                    aria-current={active ? "page" : undefined}
                    aria-label={rail ? item.label : undefined}
                    className={cx(
                      "flex items-center gap-3 rounded-lg text-sm transition-colors duration-150",
                      rail ? "justify-center px-0 py-2.5" : "px-3 py-2",
                      active
                        ? rail
                          ? "bg-[#d2e3fc] font-semibold text-[#17385e] dark:bg-brand-soft dark:text-brand"
                          : "bg-brand-soft font-semibold text-brand"
                        : rail
                          ? "text-[#17385e] hover:bg-white dark:text-foreground dark:hover:bg-muted"
                          : "text-foreground hover:bg-muted",
                    )}
                  >
                    <Icon size={18} strokeWidth={1.8} aria-hidden="true" className="shrink-0" />
                    {!rail && <span className="truncate">{item.label}</span>}
                  </Link>
                  {rail && (
                    <span
                      role="tooltip"
                      className="pointer-events-none absolute top-1/2 left-full z-50 ml-3 -translate-y-1/2 rounded-md bg-foreground px-2 py-1 text-xs font-medium whitespace-nowrap text-background opacity-0 shadow-md transition-opacity group-focus-within/nav:opacity-100 group-hover/nav:opacity-100"
                    >
                      {item.label}
                    </span>
                  )}
                </li>
              );
            })}
          </ul>
        </div>
      ))}
    </nav>
  );

  const brandBlock = (rail: boolean) => (
    <Link
      to={cfg.base}
      className={cx("flex items-center gap-3 rounded-lg", rail && "justify-center")}
      title={branding.institution}
    >
      <img src={sealUrl} alt="UM Tagum College seal" className="size-9 shrink-0" />
      {!rail && (
        <span className="min-w-0">
          <span className="block truncate text-base font-semibold text-brand">
            {branding.system_name}
          </span>
          <span className="block truncate text-xs text-muted-foreground">{cfg.title}</span>
        </span>
      )}
    </Link>
  );

  return (
    <div className="min-h-screen bg-background text-foreground">
      <a
        href="#main-content"
        className="sr-only z-[60] rounded-md bg-card px-3 py-2 text-sm font-semibold focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
      >
        Skip to content
      </a>

      {/* Desktop sidebar */}
      <aside
        className={cx(
          "fixed inset-y-0 left-0 z-30 hidden flex-col border-r border-border transition-[width] duration-200 lg:flex",
          collapsed ? "w-[4.5rem] bg-[#e8eff8] dark:bg-card" : "w-64 bg-card",
        )}
      >
        <div
          className={cx(
            "flex h-16 items-center border-b border-border",
            collapsed ? "justify-center px-2" : "px-4",
          )}
        >
          {brandBlock(collapsed)}
        </div>
        {navigation(collapsed)}
        <div className="border-t border-border p-3">
          <button
            type="button"
            onClick={toggleCollapsed}
            aria-label={collapsed ? "Expand navigation" : "Collapse navigation"}
            title={collapsed ? "Expand navigation" : "Collapse navigation"}
            className={cx(
              "flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground",
              collapsed && "justify-center px-0",
            )}
          >
            {collapsed ? (
              <ChevronsRight size={18} aria-hidden="true" />
            ) : (
              <>
                <ChevronsLeft size={18} aria-hidden="true" />
                Collapse
              </>
            )}
          </button>
        </div>
      </aside>

      {/* Mobile drawer */}
      {drawerOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <button
            type="button"
            aria-label="Close navigation"
            className="absolute inset-0 bg-foreground/30 animate-in fade-in-0"
            onClick={() => setDrawerOpen(false)}
          />
          <aside
            role="dialog"
            aria-modal="true"
            aria-label="Navigation"
            className="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col bg-card shadow-lg animate-in slide-in-from-left duration-200"
          >
            <div className="flex h-16 items-center justify-between border-b border-border px-4">
              {brandBlock(false)}
              <button
                type="button"
                onClick={() => setDrawerOpen(false)}
                aria-label="Close navigation"
                className="rounded-md p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground"
              >
                <X size={20} aria-hidden="true" />
              </button>
            </div>
            {navigation(false)}
          </aside>
        </div>
      )}

      <div
        className={cx(
          "flex min-h-screen flex-col transition-[padding] duration-200",
          collapsed ? "lg:pl-[4.5rem]" : "lg:pl-64",
        )}
      >
        <header className="sticky top-0 z-20 flex h-16 items-center gap-3 bg-header px-4 text-header-foreground sm:px-6">
          <button
            type="button"
            onClick={() => setDrawerOpen(true)}
            aria-label="Open navigation"
            className="-ml-1 rounded-lg p-2 text-header-foreground hover:bg-white/15 lg:hidden"
          >
            <Menu size={20} aria-hidden="true" />
          </button>
          <nav aria-label="Breadcrumb" className="min-w-0 flex-1">
            <ol className="flex min-w-0 items-center gap-1.5 text-sm">
              <li className="hidden shrink-0 text-header-foreground/80 sm:block">{cfg.title}</li>
              {currentGroup && current?.section !== "" && (
                <li className="hidden shrink-0 text-header-foreground/80 md:block">
                  <span aria-hidden="true" className="mr-1.5">
                    /
                  </span>
                  {currentGroup.group}
                </li>
              )}
              <li
                className="min-w-0 truncate font-semibold text-header-foreground"
                aria-current="page"
              >
                <span
                  aria-hidden="true"
                  className="mr-1.5 hidden font-normal text-header-foreground/80 sm:inline"
                >
                  /
                </span>
                {current?.label ?? pageFallback}
              </li>
            </ol>
          </nav>

          <div ref={barRef} className="relative flex items-center gap-1">
            <div className="relative">
              <button
                type="button"
                aria-label="Colour theme"
                title="Colour theme"
                aria-expanded={menu === "theme"}
                aria-haspopup="menu"
                onClick={() => setMenu((v) => (v === "theme" ? "none" : "theme"))}
                className="inline-flex size-9 items-center justify-center rounded-lg text-header-foreground hover:bg-white/15"
              >
                <ThemeIcon size={18} strokeWidth={1.8} aria-hidden="true" />
              </button>
              {menu === "theme" && (
                <div
                  role="menu"
                  className="absolute top-11 right-0 z-40 w-40 rounded-lg border border-border bg-popover p-1 text-popover-foreground shadow-md"
                >
                  {(
                    [
                      ["light", "Light", Sun],
                      ["dark", "Dark", Moon],
                      ["system", "Match device", Monitor],
                    ] as const
                  ).map(([value, label, Icon]) => (
                    <button
                      key={value}
                      type="button"
                      role="menuitemradio"
                      aria-checked={theme === value}
                      onClick={() => chooseTheme(value)}
                      className={cx(
                        "flex w-full items-center gap-2.5 rounded-md px-2.5 py-2 text-sm hover:bg-muted",
                        theme === value && "font-semibold text-brand",
                      )}
                    >
                      <Icon size={16} aria-hidden="true" />
                      {label}
                    </button>
                  ))}
                </div>
              )}
            </div>

            {canChat && (
              <button
                type="button"
                aria-label={chatOpen ? "Close chat" : "Open chat"}
                title="Chat"
                aria-expanded={chatOpen}
                onClick={() => setChatOpen((v) => !v)}
                className={cx(
                  "inline-flex size-9 items-center justify-center rounded-lg text-header-foreground hover:bg-white/15",
                  chatOpen && "bg-white/20",
                )}
              >
                <MessageSquare size={18} strokeWidth={1.8} aria-hidden="true" />
              </button>
            )}

            <div className="relative">
              <button
                type="button"
                aria-label={unread ? `Notifications, ${unread} unread` : "Notifications"}
                title="Notifications"
                aria-expanded={menu === "bell"}
                aria-haspopup="dialog"
                onClick={() => setMenu((v) => (v === "bell" ? "none" : "bell"))}
                className="relative inline-flex size-9 items-center justify-center rounded-lg text-header-foreground hover:bg-white/15"
              >
                <Bell size={18} strokeWidth={1.8} aria-hidden="true" />
                {unread > 0 && (
                  <span className="absolute top-1 right-1 min-w-4 rounded-full bg-white px-1 text-center text-[10px] leading-4 font-semibold text-header tabular-nums">
                    {unread > 99 ? "99+" : unread}
                  </span>
                )}
              </button>
              {menu === "bell" && (
                <div
                  role="dialog"
                  aria-label="Recent notifications"
                  className="absolute top-11 right-0 z-40 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-border bg-popover text-popover-foreground shadow-md"
                >
                  <div className="flex items-center justify-between border-b border-border px-4 py-3">
                    <p className="text-sm font-semibold">Notifications</p>
                    {unread > 0 && <p className="text-xs text-muted-foreground">{unread} unread</p>}
                  </div>
                  {notificationError && (
                    <p role="alert" className="px-4 py-3 text-sm text-brand">
                      Could not load notifications. {notificationError}
                    </p>
                  )}
                  {!notifications && !notificationError && (
                    <p role="status" className="px-4 py-3 text-sm text-muted-foreground">
                      Loading notifications…
                    </p>
                  )}
                  {notifications?.data.length === 0 && (
                    <p className="px-4 py-6 text-center text-sm text-muted-foreground">
                      No notifications yet.
                    </p>
                  )}
                  <ul className="max-h-80 divide-y divide-border overflow-y-auto">
                    {notifications?.data.slice(0, 5).map((item) => (
                      <li
                        key={item.id}
                        className={cx("px-4 py-3 text-sm", !item.read_at && "bg-brand-soft/50")}
                      >
                        <div className="flex items-start gap-2">
                          {!item.read_at && (
                            <span
                              aria-label="Unread"
                              className="mt-1.5 size-2 shrink-0 rounded-full bg-brand"
                            />
                          )}
                          <div className="min-w-0">
                            <p className="font-medium">{item.data.title}</p>
                            <p className="mt-0.5 text-muted-foreground">{item.data.body}</p>
                            <p className="mt-1 text-xs text-muted-foreground">
                              {new Date(item.created_at).toLocaleString("en-PH", {
                                timeZone: branding.display_timezone,
                                dateStyle: "medium",
                                timeStyle: "short",
                              })}
                            </p>
                          </div>
                        </div>
                      </li>
                    ))}
                  </ul>
                  <Link
                    to={target("notifications")}
                    className="block border-t border-border px-4 py-2.5 text-center text-sm font-semibold text-brand hover:bg-muted"
                  >
                    View all notifications
                  </Link>
                </div>
              )}
            </div>

            <div className="relative ml-1">
              <button
                type="button"
                id="account-menu-button"
                aria-label="Account menu"
                aria-expanded={menu === "user"}
                aria-haspopup="menu"
                onClick={() => setMenu((v) => (v === "user" ? "none" : "user"))}
                className="flex items-center gap-2 rounded-lg p-1 pr-2 hover:bg-white/15"
              >
                <span className="flex size-8 items-center justify-center rounded-full bg-white/15 text-xs font-semibold text-header-foreground">
                  {initials}
                </span>
                <span className="hidden max-w-44 truncate text-sm font-medium md:block">
                  {user.name}
                </span>
                <ChevronDown
                  size={16}
                  aria-hidden="true"
                  className="hidden text-header-foreground/80 md:block"
                />
              </button>
              {menu === "user" && (
                <div
                  role="menu"
                  className="absolute top-11 right-0 z-40 w-64 overflow-hidden rounded-xl border border-border bg-popover text-popover-foreground shadow-md"
                >
                  <div className="border-b border-border px-4 py-3">
                    <p className="truncate text-sm font-semibold">{user.name}</p>
                    {user.email && (
                      <p className="truncate text-xs text-muted-foreground">{user.email}</p>
                    )}
                    <p className="mt-1.5 text-xs text-muted-foreground">
                      {cfg.title}
                      {role !== "admin" && ` · ${programLabel}`}
                    </p>
                  </div>
                  <Link
                    to={target("profile")}
                    role="menuitem"
                    className="flex items-center gap-2.5 px-4 py-2.5 text-sm hover:bg-muted"
                  >
                    <UserRound size={16} aria-hidden="true" className="text-muted-foreground" />
                    My profile
                  </Link>
                  <button
                    type="button"
                    role="menuitem"
                    onClick={() => {
                      setMenu("none");
                      // The menu item disappears; focus returns to the menu button after the modal.
                      document.getElementById("account-menu-button")?.focus();
                      setPasswordOpen(true);
                    }}
                    className="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm hover:bg-muted"
                  >
                    <KeyRound size={16} aria-hidden="true" className="text-muted-foreground" />
                    Change password
                  </button>
                  <button
                    type="button"
                    role="menuitem"
                    disabled={signingOut}
                    onClick={() => {
                      setMenu("none");
                      // The menu item disappears; focus returns to the menu button after the modal.
                      document.getElementById("account-menu-button")?.focus();
                      setConfirmSignOut(true);
                    }}
                    className="flex w-full items-center gap-2.5 border-t border-border px-4 py-2.5 text-left text-sm hover:bg-muted disabled:opacity-60"
                  >
                    <LogOut size={16} aria-hidden="true" className="text-muted-foreground" />
                    {signingOut ? "Signing out…" : "Sign out"}
                  </button>
                  {logoutError && (
                    <p role="alert" className="px-4 pb-3 text-sm text-brand">
                      {logoutError}
                    </p>
                  )}
                </div>
              )}
            </div>
          </div>
        </header>

        <main
          id="main-content"
          tabIndex={-1}
          className="mx-auto w-full max-w-[1400px] min-w-0 flex-1 px-4 py-6 outline-none sm:px-6 lg:px-8 lg:py-8"
        >
          <p className="mb-5 rounded border border-warn/40 bg-warn/10 p-3 text-sm">
            Development preview: records labelled Synthetic are fictional test data. Production
            deployment and final user acceptance are pending.
          </p>
          <Outlet />
        </main>
      </div>

      <ConfirmDialog
        open={confirmSignOut}
        title="Sign out?"
        description="You will need to sign in again to continue."
        confirmLabel="Sign out"
        busy={signingOut}
        error={logoutError || undefined}
        onCancel={() => setConfirmSignOut(false)}
        onConfirm={() => void signOut()}
      />
      <ChangePasswordModal open={passwordOpen} onClose={() => setPasswordOpen(false)} />
      {canChat && chatOpen && (
        <aside
          role="dialog"
          aria-modal="false"
          aria-label="Chat"
          className="fixed right-3 bottom-3 z-50 flex max-h-[calc(100dvh-5rem)] w-[min(400px,calc(100vw-24px))] flex-col overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-2xl animate-in fade-in-0 slide-in-from-bottom-2 duration-150 dark:border-border dark:bg-popover dark:text-popover-foreground"
        >
          <header className="flex items-center justify-between border-b border-border px-4 py-3">
            <div>
              <h2 className="font-semibold text-[#17385e] dark:text-foreground">Chat</h2>
              <p className="text-xs text-slate-500 dark:text-muted-foreground">
                InternMatch messages
              </p>
            </div>
            <button
              type="button"
              onClick={() => setChatOpen(false)}
              aria-label="Close chat"
              title="Close chat"
              className="rounded-md p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground"
            >
              <X size={18} aria-hidden="true" />
            </button>
          </header>
          <div className="min-h-0 flex-1 overflow-y-auto p-3">
            <ChatWorkspace compact />
          </div>
        </aside>
      )}
    </div>
  );
}
