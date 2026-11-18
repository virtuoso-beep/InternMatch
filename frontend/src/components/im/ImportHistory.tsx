import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Button, Card } from "./ui";

import { downloadImportCredentials } from "@/lib/importCredentials";
import { activationLabel } from "@/lib/activation";

type Batch = {
  id: string;
  filename: string;
  created_at: string;
  credentials_available: boolean;
  result: null | {
    successful: number;
    updated: number;
    skipped: number;
    failed: number;
    rows: { source: string; status: string; message: string; activation?: string }[];
  };
};
export function ImportHistory() {
  const [open, setOpen] = useState(false),
    [page, setPage] = useState(1),
    [revision, setRevision] = useState(0);
  const [data, setData] = useState<{ data: Batch[]; last_page: number } | null>(null),
    [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  useEffect(() => {
    if (!open) return;
    let active = true;
    setData(null);
    setError("");
    api<{ data: Batch[]; last_page: number }>(`/coordinator-imports/history?page=${page}`)
      .then((r) => {
        if (active) setData(r);
      })
      .catch((e) => {
        if (active) setError(e.message);
      });
    return () => {
      active = false;
    };
  }, [open, page, revision]);
  return (
    <div className="mb-4">
      <Button
        variant="outline"
        onClick={() => {
          setOpen(!open);
          setRevision((v) => v + 1);
        }}
      >
        Import History
      </Button>
      {open && (
        <Card className="mt-3">
          <h2 className="font-semibold">Your student imports</h2>
          {error && <p role="alert">{error}</p>}
          {!data && !error && <p>Loading history…</p>}
          {data?.data.length === 0 && <p>No imports yet.</p>}
          {data?.data.map((batch) => (
            <details key={batch.id} className="my-3 rounded border p-3">
              <summary>
                {batch.filename} · {batch.created_at} ·{" "}
                {batch.result ? "Imported" : "Not confirmed"}
              </summary>
              {batch.result && (
                <>
                  <p className="my-2">
                    Successful: {batch.result.successful} · Updated: {batch.result.updated} ·
                    Skipped: {batch.result.skipped} · Failed: {batch.result.failed}
                  </p>
                  <ul>
                    {batch.result.rows.map((row, i) => (
                      <li key={i}>
                        {row.source} — {row.status}: {row.message}
                        {row.activation && ` · Activation: ${activationLabel(row.activation)}`}
                      </li>
                    ))}
                  </ul>
                </>
              )}
              {batch.credentials_available && (
                <Button
                  disabled={busy}
                  onClick={async () => {
                    setBusy(true);
                    try {
                      await downloadImportCredentials(batch.id);
                      setRevision((v) => v + 1);
                    } catch (e) {
                      setError(e instanceof Error ? e.message : "Download failed.");
                    } finally {
                      setBusy(false);
                    }
                  }}
                >
                  Download credentials once
                </Button>
              )}
            </details>
          ))}
          {data && (
            <div className="flex items-center gap-3">
              <Button disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
                Previous
              </Button>
              <span>
                Page {page} of {data.last_page}
              </span>
              <Button disabled={page >= data.last_page} onClick={() => setPage((p) => p + 1)}>
                Next
              </Button>
            </div>
          )}
        </Card>
      )}
    </div>
  );
}
