import { useState } from "react";
import { logout, mutate, type SessionUser } from "@/lib/api";
import { Button } from "./ui";

export function FirstPassword({ onChanged }: { onChanged: (user: SessionUser) => void }) {
  const [current, setCurrent] = useState("");
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  return (
    <main className="mx-auto my-16 max-w-lg rounded-xl border bg-card p-8 shadow-sm">
      <h1 className="text-2xl font-semibold">Create your password</h1>
      <p className="my-4 text-muted-foreground">
        Before entering the Student Portal, replace your temporary password. Use at least 12
        characters, including uppercase and lowercase letters, a number, and a symbol.
      </p>
      <form
        className="space-y-4"
        onSubmit={async (e) => {
          e.preventDefault();
          setBusy(true);
          setError("");
          try {
            const result = await mutate<{ data: SessionUser }>("/first-password", {
              current_password: current,
              password,
              password_confirmation: confirmation,
            });
            setCurrent("");
            setPassword("");
            setConfirmation("");
            onChanged(result.data);
          } catch (cause) {
            setError(cause instanceof Error ? cause.message : "Unable to change password.");
          } finally {
            setBusy(false);
          }
        }}
      >
        <label className="block">
          Temporary password
          <input
            className="mt-1 block w-full rounded border p-2"
            type="password"
            autoComplete="current-password"
            required
            value={current}
            onChange={(e) => setCurrent(e.target.value)}
          />
        </label>
        <label className="block">
          New password
          <input
            className="mt-1 block w-full rounded border p-2"
            type="password"
            autoComplete="new-password"
            minLength={12}
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
        </label>
        <label className="block">
          Confirm new password
          <input
            className="mt-1 block w-full rounded border p-2"
            type="password"
            autoComplete="new-password"
            minLength={12}
            required
            value={confirmation}
            onChange={(e) => setConfirmation(e.target.value)}
          />
        </label>
        {error && <p role="alert">{error}</p>}
        <Button type="submit" disabled={busy || password !== confirmation}>
          Save password and continue
        </Button>
        <Button
          type="button"
          variant="outline"
          disabled={busy}
          onClick={async () => {
            try {
              await logout();
              window.location.assign("/auth");
            } catch {
              setError("Unable to sign out. Try again.");
            }
          }}
        >
          Sign out
        </Button>
      </form>
    </main>
  );
}
