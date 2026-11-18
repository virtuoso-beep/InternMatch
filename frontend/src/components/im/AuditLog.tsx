import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button, Card, PageHeader, downloadText, formatAction } from "./ui";
import { useSessionUser } from "./SessionGuard";

type AuditPage = {
  data: {
    id: number;
    actor_name: string | null;
    action: string;
    subject_id: number;
    created_at: string;
  }[];
  current_page: number;
  last_page: number;
};
export function AuditLog() {
  const user = useSessionUser();
  const [page, setPage] = useState(1);
  const [data, setData] = useState<AuditPage | null>(null);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [revision, setRevision] = useState(0);
  const [action, setAction] = useState("");
  const visible =
    data?.data.filter(
      (item) =>
        (!action || item.action === action) &&
        `${item.actor_name ?? "System"} ${item.action} ${item.subject_id}`
          .toLowerCase()
          .includes(search.trim().toLowerCase()),
    ) ?? [];
  useEffect(() => {
    let active = true;
    setData(null);
    setError("");
    api<AuditPage>(`/audit-logs?page=${page}`)
      .then((result) => {
        if (active) setData(result);
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
      <PageHeader
        title="Audit trail"
        subtitle="Read-only actions within your authorized scope. Times are shown in Asia/Manila."
        action={
          <Button
            variant="outline"
            disabled={!data && !error}
            onClick={() => setRevision((v) => v + 1)}
          >
            Refresh audit trail
          </Button>
        }
      />
      {error && <p role="alert">{error}</p>}
      {!data && !error && <p role="status">Loading audit records…</p>}
      <div className="mb-5 flex flex-wrap gap-3">
        <label className="text-sm">
          Search this page
          <input
            type="search"
            className="ml-2 rounded border p-2"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </label>
        <Button variant="outline" onClick={() => setSearch("")}>
          Clear search
        </Button>
        {user.role === "admin" && (
          <>
            <select
              aria-label="Filter audit action"
              className="rounded border p-2"
              value={action}
              onChange={(event) => setAction(event.target.value)}
            >
              <option value="">All actions on this page</option>
              {[...new Set(data?.data.map((item) => item.action) ?? [])].sort().map((value) => (
                <option key={value} value={value}>
                  {formatAction(value)}
                </option>
              ))}
            </select>
            <Button
              variant="outline"
              onClick={() => {
                setAction("");
                setSearch("");
              }}
            >
              Clear filters
            </Button>
            <Button
              disabled={!data || visible.length === 0}
              onClick={() => {
                const cell = (value: unknown) => {
                  const raw = String(value ?? "");
                  return `"${(/^[\s]*[=+@-]/.test(raw) ? `'${raw}` : raw).replace(/"/g, '""')}"`;
                };
                const rows = visible.map((item) => [
                  item.id,
                  item.created_at,
                  item.actor_name ?? "System / unavailable actor",
                  item.action,
                  item.subject_id,
                ]);
                downloadText(
                  `internmatch-audit-page-${page}.csv`,
                  "\uFEFF" +
                    [["Audit ID", "Timestamp (ISO)", "Actor", "Action", "Record ID"], ...rows]
                      .map((row) => row.map(cell).join(","))
                      .join("\r\n"),
                  "text/csv;charset=utf-8",
                );
              }}
            >
              Export visible records
            </Button>
          </>
        )}
      </div>
      {data && (
        <Card>
          <p className="mb-3 text-sm text-muted-foreground">
            Showing {visible.length} of {data.data.length} records on this page. Search, action
            filtering and export apply only to this page.
          </p>
          {visible.length === 0 ? (
            <p>No audit records match this page and search.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead>
                  <tr>
                    <th className="p-2">Time</th>
                    <th className="p-2">Actor</th>
                    <th className="p-2">Action</th>
                    <th className="p-2">Record</th>
                  </tr>
                </thead>
                <tbody>
                  {visible.map((item) => (
                    <tr key={item.id} className="border-t">
                      <td className="p-2">
                        {new Date(item.created_at).toLocaleString("en-PH", {
                          timeZone: "Asia/Manila",
                        })}
                      </td>
                      <td className="p-2">{item.actor_name ?? "System / unavailable actor"}</td>
                      <td className="p-2" title={item.action}>
                        {formatAction(item.action)}
                      </td>
                      <td className="p-2">{item.subject_id}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
          <div className="mt-4 flex gap-3">
            <Button disabled={page === 1} onClick={() => setPage(page - 1)}>
              Previous
            </Button>
            <span>
              Page {page} of {data.last_page}
            </span>
            <Button disabled={page >= data.last_page} onClick={() => setPage(page + 1)}>
              Next
            </Button>
          </div>
        </Card>
      )}
    </>
  );
}
