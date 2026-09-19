import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button, Card, PageHeader } from "./ui";

type AuditPage = { data: { id: number; actor_name: string | null; action: string; subject_id: number; created_at: string }[]; current_page: number; last_page: number };
export function AuditLog() {
  const [page, setPage] = useState(1);
  const [data, setData] = useState<AuditPage | null>(null);
  const [error, setError] = useState("");
  useEffect(() => {
    let active = true; setData(null); setError("");
    api<AuditPage>(`/audit-logs?page=${page}`).then(result => { if (active) setData(result); })
      .catch((cause: Error) => { if (active) setError(cause.message); });
    return () => { active = false; };
  }, [page]);
  return <>
    <PageHeader title="Audit trail" subtitle="Recorded account, profile, competency, and program changes." />
    {error && <p role="alert">{error}</p>}
    {!data && !error && <p role="status">Loading audit records…</p>}
    {data && <Card>
      {data.data.length === 0 ? <p>No audit records in your access scope.</p> : <div className="overflow-x-auto"><table className="w-full text-left text-sm">
        <thead><tr><th className="p-2">Time</th><th className="p-2">Actor</th><th className="p-2">Action</th><th className="p-2">Record</th></tr></thead>
        <tbody>{data.data.map(item => <tr key={item.id} className="border-t"><td className="p-2">{item.created_at}</td><td className="p-2">{item.actor_name ?? "Deleted account"}</td><td className="p-2">{item.action}</td><td className="p-2">{item.subject_id}</td></tr>)}</tbody>
      </table></div>}
      <div className="mt-4 flex gap-3"><Button disabled={page === 1} onClick={() => setPage(page - 1)}>Previous</Button><span>Page {page} of {data.last_page}</span><Button disabled={page >= data.last_page} onClick={() => setPage(page + 1)}>Next</Button></div>
    </Card>}
  </>;
}
