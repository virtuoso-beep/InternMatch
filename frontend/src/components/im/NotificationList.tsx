import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, cx, FilterChips, PageHeader } from "./ui";
import { useSessionUser } from "./SessionGuard";

export type NotificationPage = {
  data: {
    id: string;
    data: { title: string; body: string; conversation_id?: number };
    created_at: string;
    read_at: string | null;
  }[];
  unread_count: number;
  last_page: number;
};

function timeAgo(dateString: string) {
  const seconds = Math.floor((Date.now() - new Date(dateString).getTime()) / 1000);
  if (seconds < 60) return "just now";
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `${days}d ago`;
  return new Date(dateString).toLocaleDateString("en-PH", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

export function NotificationList() {
  const user = useSessionUser();
  const [result, setResult] = useState<NotificationPage | null>(null);
  const [page, setPage] = useState(1);
  const [revision, setRevision] = useState(0);
  const [error, setError] = useState("");
  const [filter, setFilter] = useState("All");
  const [markingAll, setMarkingAll] = useState(false);
  const [marking, setMarking] = useState<string | null>(null);

  useEffect(() => {
    let active = true;
    setError("");
    setResult(null);
    api<NotificationPage>(`/notifications?page=${page}&status=${filter.toLowerCase()}`)
      .then((value) => {
        if (active) setResult(value);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [page, revision, filter]);

  const visible =
    result?.data.filter((item) =>
      filter === "All" ? true : filter === "Unread" ? !item.read_at : !!item.read_at,
    ) ?? [];

  async function markAllRead() {
    if (!result || markingAll) return;
    if (result.unread_count === 0) return;
    setMarkingAll(true);
    try {
      await mutate("/notifications/read-all", undefined, "PATCH");
      setPage(1);
      window.dispatchEvent(new Event("internmatch:notifications-changed"));
      setRevision((value) => value + 1);
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Unable to mark notifications.");
    } finally {
      setMarkingAll(false);
    }
  }

  return (
    <>
      <PageHeader
        title="Notifications"
        subtitle="Updates from recorded system events."
        action={
          result && result.unread_count > 0 ? (
            <Button variant="outline" disabled={markingAll || !!marking} onClick={markAllRead}>
              {markingAll ? "Marking…" : `Mark all as read (${result.unread_count})`}
            </Button>
          ) : undefined
        }
      />
      <FilterChips
        options={["All", "Unread", "Read"]}
        value={filter}
        onChange={(value) => {
          setFilter(value);
          setPage(1);
          setResult(null);
        }}
      />
      {error && (
        <p role="alert" className="mb-4 rounded-lg bg-brand-soft p-4 text-sm text-brand">
          {error}
          <button className="ml-3 underline" onClick={() => setRevision((value) => value + 1)}>
            Try again
          </button>
        </p>
      )}
      {!result && !error && <p role="status">Loading notifications…</p>}
      {result?.data.length === 0 && (
        <Card className="py-16 text-center">
          <div className="mb-4 text-5xl opacity-40">🔔</div>
          <p className="text-lg font-semibold">
            {filter === "All" ? "No notifications yet" : `No ${filter.toLowerCase()} notifications`}
          </p>
          <p className="mt-2 text-sm text-muted-foreground">
            System events and updates will appear here.
          </p>
        </Card>
      )}
      {result && result.data.length > 0 && visible.length === 0 && (
        <Card className="py-10 text-center">
          <p className="text-sm text-muted-foreground">
            No {filter.toLowerCase()} notifications on this page.
          </p>
          <Button variant="outline" className="mt-3" onClick={() => setFilter("All")}>
            Show all
          </Button>
        </Card>
      )}
      <div className="space-y-3">
        {visible.map((item) => (
          <Card
            key={item.id}
            className={cx(
              "relative transition-colors",
              !item.read_at && "border-l-4 border-l-brand bg-brand-soft/30",
            )}
          >
            <div className="flex items-start gap-4">
              <div
                className={cx(
                  "mt-1 flex size-10 shrink-0 items-center justify-center rounded-full text-lg",
                  !item.read_at ? "bg-brand/10 text-brand" : "bg-muted text-muted-foreground",
                )}
              >
                {item.data.conversation_id ? "💬" : "📋"}
              </div>
              <div className="min-w-0 flex-1">
                <div className="flex items-start justify-between gap-3">
                  <p className={cx("text-sm", !item.read_at ? "font-semibold" : "font-medium")}>
                    {item.data.title}
                  </p>
                  <span
                    className="shrink-0 text-xs text-muted-foreground"
                    title={new Date(item.created_at).toLocaleString()}
                  >
                    {timeAgo(item.created_at)}
                  </span>
                </div>
                <p className="mt-1 text-sm text-muted-foreground">{item.data.body}</p>
                <div className="mt-3 flex flex-wrap items-center gap-3">
                  {item.data.conversation_id &&
                    ["student", "coordinator", "supervisor"].includes(user.role) && (
                      <a
                        className="text-sm font-semibold text-brand hover:underline"
                        href={`/${user.role}/inbox?conversation=${item.data.conversation_id}`}
                      >
                        Open conversation →
                      </a>
                    )}
                  {!item.read_at && (
                    <Button
                      variant="ghost"
                      disabled={!!marking || markingAll}
                      onClick={async () => {
                        setMarking(item.id);
                        try {
                          await mutate(`/notifications/${item.id}/read`, undefined, "PATCH");
                          setPage(1);
                          window.dispatchEvent(new Event("internmatch:notifications-changed"));
                          setRevision((value) => value + 1);
                        } catch (cause) {
                          setError(
                            cause instanceof Error
                              ? cause.message
                              : "Unable to mark notification read.",
                          );
                        } finally {
                          setMarking(null);
                        }
                      }}
                    >
                      Mark as read
                    </Button>
                  )}
                </div>
              </div>
            </div>
          </Card>
        ))}
      </div>
      {result && result.last_page > 1 && (
        <div className="mt-6 flex items-center justify-center gap-3">
          <Button variant="outline" disabled={page === 1} onClick={() => setPage(page - 1)}>
            ← Previous
          </Button>
          <span className="text-sm text-muted-foreground">
            Page {page} of {result.last_page}
          </span>
          <Button
            variant="outline"
            disabled={page >= result.last_page}
            onClick={() => setPage(page + 1)}
          >
            Next →
          </Button>
        </div>
      )}
    </>
  );
}
