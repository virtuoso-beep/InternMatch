import { useEffect, useState } from "react";
import { ShieldCheck } from "lucide-react";
import { api, logout, mutate, type SessionUser } from "@/lib/api";
import { Button } from "./ui";

type Notice = { version: string; title: string; sections: { heading: string; body: string }[] };

/** Spec §17B: the current privacy notice must be acknowledged once per version before using the portal. */
export function PrivacyConsent({
  onAcknowledged,
}: {
  onAcknowledged: (user: SessionUser) => void;
}) {
  const [notice, setNotice] = useState<Notice | null>(null);
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  useEffect(() => {
    api<{ data: Notice }>("/privacy-notice")
      .then((result) => setNotice(result.data))
      .catch((cause: Error) => setError(cause.message));
  }, []);
  return (
    <main className="mx-auto my-12 max-w-2xl rounded-xl border bg-card p-8 shadow-sm">
      <div className="flex items-center gap-3">
        <span className="rounded-lg bg-brand-soft p-2 text-brand">
          <ShieldCheck size={22} aria-hidden="true" />
        </span>
        <h1 className="text-2xl font-semibold">{notice?.title ?? "Privacy notice"}</h1>
      </div>
      <p className="mt-3 text-sm text-muted-foreground">
        Please read how InternMatch uses your personal data before continuing.
      </p>
      {error && (
        <p role="alert" className="mt-4 text-sm text-destructive">
          {error}
        </p>
      )}
      {!notice && !error && (
        <p role="status" className="mt-6 text-sm">
          Loading the privacy notice…
        </p>
      )}
      {notice && (
        <>
          <div className="mt-6 space-y-5">
            {notice.sections.map((section) => (
              <section key={section.heading}>
                <h2 className="font-semibold">{section.heading}</h2>
                <p className="mt-1 text-sm leading-relaxed text-muted-foreground">{section.body}</p>
              </section>
            ))}
          </div>
          <p className="mt-6 text-xs text-muted-foreground">Notice version {notice.version}</p>
          <div className="mt-6 flex flex-wrap gap-3">
            <Button
              disabled={busy}
              onClick={async () => {
                setBusy(true);
                setError("");
                try {
                  const result = await mutate<{ data: SessionUser }>("/privacy-consent", {});
                  onAcknowledged(result.data);
                } catch (cause) {
                  setError(
                    cause instanceof Error ? cause.message : "Unable to record acknowledgement.",
                  );
                } finally {
                  setBusy(false);
                }
              }}
            >
              I have read and acknowledge this notice
            </Button>
            <Button variant="outline" disabled={busy} onClick={() => void logout()}>
              Sign out
            </Button>
          </div>
        </>
      )}
    </main>
  );
}
