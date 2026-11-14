import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button, Card, CardTitle } from "./ui";

type Backup = {
  id: string;
  status: string;
  created_at: string | null;
  verified_at: string | null;
  bytes: number;
  verification_scope: string | null;
};
type BackupResponse = {
  data: Backup[];
  total: number;
  schedule: {
    schedule: string;
    timezone: string;
    requirements: string;
    configured_at: string;
  } | null;
};
export function BackupHistory() {
  const [data, setData] = useState<BackupResponse | null>(null);
  const [error, setError] = useState("");
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    let active = true;
    setData(null);
    setError("");
    api<BackupResponse>("/backup-status")
      .then((value) => {
        if (active) setData(value);
      })
      .catch((cause: Error) => {
        if (active) setError(cause.message);
      });
    return () => {
      active = false;
    };
  }, [revision]);
  return (
    <Card>
      <CardTitle>Recorded backups</CardTitle>
      {data && (
        <p className="my-3 text-sm">
          {data.schedule
            ? `${data.schedule.schedule} (${data.schedule.timezone}). ${data.schedule.requirements} Configuration recorded ${data.schedule.configured_at}; live task state is available in Windows Task Scheduler.`
            : "No local schedule registered."}
        </p>
      )}
      <p className="my-3 text-sm text-muted-foreground">
        Database plus private and public documents. Recovery verification covers checksums, SQL
        import and document extraction into an isolated environment; it does not certify full
        application recovery.
      </p>
      <Button
        variant="outline"
        disabled={!data && !error}
        onClick={() => setRevision((value) => value + 1)}
      >
        Refresh backup history
      </Button>
      {error && <p role="alert">{error}</p>}
      {!data && !error && <p role="status">Loading backup history…</p>}
      {data && (
        <>
          <p className="my-3 text-sm">
            Showing {data.data.length} of {data.total} recorded backups.
          </p>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead>
                <tr>
                  {["Created (UTC)", "Status", "Archive size", "Recovery drill"].map((label) => (
                    <th className="p-2" key={label}>
                      {label}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {data.data.map((item) => (
                  <tr className="border-t" key={item.id}>
                    <td className="p-2">{item.created_at ?? "Unknown"}</td>
                    <td className="p-2">{item.status}</td>
                    <td className="p-2">{(item.bytes / 1048576).toFixed(2)} MB</td>
                    <td className="p-2">
                      {item.verified_at ? `Verified ${item.verified_at}` : "Not verified"}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </>
      )}
    </Card>
  );
}
