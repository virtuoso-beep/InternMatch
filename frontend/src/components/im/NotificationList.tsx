import { useEffect, useState } from "react";
import { api, mutate } from "@/lib/api";
import { Button, Card, PageHeader } from "./ui";

export type NotificationPage = {
  data: {
    id: string;
    data: { title: string; body: string };
    created_at: string;
    read_at: string | null;
  }[];
  unread_count: number;
  last_page: number;
};
export function NotificationList() {
  const [result, setResult] = useState<NotificationPage | null>(null);
  const [page, setPage] = useState(1);
  const [revision, setRevision] = useState(0);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true;
    setError("");
    api<NotificationPage>(`/notifications?page=${page}`)
      .then((value) => {
        if (active) setResult(value);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [page, revision]);
  return (
    <>
      <PageHeader title="Notifications" subtitle="Updates from recorded system events." />
      {error && <p role="alert">{error}</p>}
      {!result && !error && <p role="status">Loading notifications…</p>}
      {result?.data.length === 0 && <Card>No notifications yet.</Card>}
      <div className="space-y-3">
        {result?.data.map((item) => (
          <Card key={item.id}>
            <p className="font-semibold">{item.data.title}</p>
            <p>{item.data.body}</p>
            <p className="text-sm text-muted-foreground">
              {new Date(item.created_at).toLocaleString()}
            </p>
            {!item.read_at && (
              <Button
                variant="outline"
                onClick={async () => {
                  try {
                    await mutate(`/notifications/${item.id}/read`, undefined, "PATCH");
                    window.dispatchEvent(new Event("internmatch:notifications-changed"));
                    setRevision((value) => value + 1);
                  } catch (cause) {
                    setError(
                      cause instanceof Error ? cause.message : "Unable to mark notification read.",
                    );
                  }
                }}
              >
                Mark as read
              </Button>
            )}
          </Card>
        ))}
      </div>
      {result && result.last_page > 1 && (
        <div className="mt-4 flex gap-3">
          <Button disabled={page === 1} onClick={() => setPage(page - 1)}>
            Previous
          </Button>
          <span>
            {page} / {result.last_page}
          </span>
          <Button disabled={page >= result.last_page} onClick={() => setPage(page + 1)}>
            Next
          </Button>
        </div>
      )}
    </>
  );
}
