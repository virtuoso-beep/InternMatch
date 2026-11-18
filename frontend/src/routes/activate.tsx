import { createFileRoute, Link } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { KeyRound } from "lucide-react";
import sealUrl from "@/assets/umtc-seal.png";
import { api, mutate } from "@/lib/api";
import { Button } from "@/components/im/ui";

export const Route = createFileRoute("/activate")({
  head: () => ({ meta: [{ title: "Activate your account — InternMatch UMTC" }] }),
  component: Activate,
});

type State = "checking" | "ready" | "invalid" | "done";

/** Spec §17A-B: set a password from a one-time activation link (no sign-in required). */
function Activate() {
  const [token, setToken] = useState("");
  const [state, setState] = useState<State>("checking");
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  useEffect(() => {
    const value = new URLSearchParams(window.location.search).get("token") ?? "";
    setToken(value);
    if (value.length !== 64) {
      setState("invalid");
      return;
    }
    api(`/activation/${encodeURIComponent(value)}`)
      .then(() => setState("ready"))
      .catch(() => setState("invalid"));
  }, []);
  return (
    <main className="flex min-h-screen items-center justify-center bg-background px-4 py-10">
      <div className="w-full max-w-md rounded-xl border bg-card p-8 shadow-sm">
        <div className="mb-6 flex items-center gap-3">
          <img src={sealUrl} alt="UM Tagum College seal" className="size-10" />
          <p className="text-lg font-bold">
            Intern<span className="text-brand">Match</span>
          </p>
        </div>
        <div className="flex items-center gap-2">
          <KeyRound size={20} className="text-brand" aria-hidden="true" />
          <h1 className="text-xl font-semibold">Activate your account</h1>
        </div>
        {state === "checking" && (
          <p role="status" className="mt-4 text-sm text-muted-foreground">
            Checking your activation link…
          </p>
        )}
        {state === "invalid" && (
          <div role="alert" className="mt-4 space-y-3 text-sm">
            <p>
              This activation link is no longer valid. Links work once and expire after a set time.
            </p>
            <p className="text-muted-foreground">
              Ask your practicum coordinator to send a new one.
            </p>
            <Link to="/auth" className="inline-block font-semibold text-brand underline">
              Go to sign in
            </Link>
          </div>
        )}
        {state === "done" && (
          <div role="status" className="mt-4 space-y-3 text-sm">
            <p>Your password is set and your account is active.</p>
            <Link to="/auth" className="inline-block font-semibold text-brand underline">
              Sign in now
            </Link>
          </div>
        )}
        {state === "ready" && (
          <form
            className="mt-4 space-y-4"
            onSubmit={async (event) => {
              event.preventDefault();
              setBusy(true);
              setError("");
              try {
                await mutate("/activation", {
                  token,
                  password,
                  password_confirmation: confirmation,
                });
                setPassword("");
                setConfirmation("");
                setState("done");
              } catch (cause) {
                setError(
                  cause instanceof Error ? cause.message : "Unable to activate the account.",
                );
              } finally {
                setBusy(false);
              }
            }}
          >
            <p className="text-sm text-muted-foreground">
              Choose a password of at least 12 characters with uppercase and lowercase letters, a
              number and a symbol.
            </p>
            <label className="block text-sm font-medium">
              New password
              <input
                type="password"
                autoComplete="new-password"
                required
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                className="mt-1 block w-full rounded-md border bg-background p-2"
              />
            </label>
            <label className="block text-sm font-medium">
              Confirm password
              <input
                type="password"
                autoComplete="new-password"
                required
                value={confirmation}
                onChange={(event) => setConfirmation(event.target.value)}
                className="mt-1 block w-full rounded-md border bg-background p-2"
              />
            </label>
            {error && (
              <p role="alert" className="text-sm text-destructive">
                {error}
              </p>
            )}
            <Button type="submit" disabled={busy} className="w-full">
              {busy ? "Saving…" : "Set password and activate"}
            </Button>
          </form>
        )}
      </div>
    </main>
  );
}
