import { useEffect, useRef, useState } from "react";
import { api, mutate, ApiError } from "@/lib/api";
import { useSessionUser } from "./SessionGuard";
import { Button } from "./ui";

type Conversation = {
  id: number;
  type: string;
  student: string;
  program: string;
  host: string | null;
  writable: boolean;
  coordinator_available: boolean;
  unread_count?: number;
  participants: { id: number; name: string; role: string }[];
};
type Message = { id: number; sender_id: number; sender: string; body: string; created_at: string };
type Detail = { conversation: Conversation; messages: Message[]; has_older: boolean };
type Inbox = { data: Conversation[]; last_page: number };

export function ChatWorkspace({ compact = false }: { compact?: boolean }) {
  const user = useSessionUser();
  const [inbox, setInbox] = useState<Inbox | null>(null);
  const [selected, setSelected] = useState<number | null>(() => {
    const id = Number(new URLSearchParams(window.location.search).get("conversation"));
    return Number.isSafeInteger(id) && id > 0 ? id : null;
  });
  const [detail, setDetail] = useState<Detail | null>(null);
  const [page, setPage] = useState(1);
  const [revision, setRevision] = useState(0);
  const [draft, setDraft] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [older, setOlder] = useState<Message[]>([]);
  const [olderAvailable, setOlderAvailable] = useState(false);
  const [loadingOlder, setLoadingOlder] = useState(false);
  const sendId = useRef<{ body: string; id: string } | null>(null);
  const bottom = useRef<HTMLDivElement>(null);
  useEffect(() => {
    const timer = window.setInterval(() => {
      if (!document.hidden) setRevision((v) => v + 1);
    }, 10000);
    return () => window.clearInterval(timer);
  }, []);
  useEffect(() => {
    let active = true;
    api<Inbox>(`/chat/conversations?page=${page}`)
      .then((value) => {
        if (active) {
          setInbox(value);
          setError("");
        }
      })
      .catch((cause: Error) => {
        if (active) {
          setInbox(null);
          setDetail(null);
          setOlder([]);
          setError(cause.message);
        }
      });
    return () => {
      active = false;
    };
  }, [page, revision]);
  useEffect(() => {
    let active = true;
    if (!selected) {
      setDetail(null);
      return;
    }
    api<Detail>(`/chat/conversations/${selected}`)
      .then(async (value) => {
        if (!active) return;
        setDetail(value);
        const last = value.messages.at(-1);
        if (last) {
          await mutate(`/chat/conversations/${selected}/read`, { last_message_id: last.id });
          if (active)
            setInbox((old) =>
              old
                ? {
                    ...old,
                    data: old.data.map((c) => (c.id === selected ? { ...c, unread_count: 0 } : c)),
                  }
                : old,
            );
        }
      })
      .catch((cause: Error) => {
        if (active) {
          setDetail(null);
          setOlder([]);
          setError(cause.message);
        }
      });
    return () => {
      active = false;
    };
  }, [selected, revision]);
  const latestMessageId = detail?.messages.at(-1)?.id;
  useEffect(() => {
    bottom.current?.scrollIntoView({ block: "nearest" });
  }, [latestMessageId]);
  function choose(id: number | null) {
    setSelected(id);
    setDetail(null);
    setOlder([]);
    setOlderAvailable(false);
    setDraft("");
    setError("");
    sendId.current = null;
  }
  async function send(event: React.FormEvent) {
    event.preventDefault();
    if (!selected || !draft.trim() || busy) return;
    const body = draft.trim();
    if (sendId.current?.body !== body) sendId.current = { body, id: crypto.randomUUID() };
    setBusy(true);
    setError("");
    try {
      const value = await mutate<Detail>(`/chat/conversations/${selected}/messages`, {
        body,
        client_id: sendId.current.id,
      });
      setDetail(value);
      setDraft("");
      sendId.current = null;
      setRevision((v) => v + 1);
    } catch (cause) {
      if (cause instanceof ApiError && [403, 404, 423].includes(cause.status)) {
        setDetail(null);
        setOlder([]);
      }
      setError(
        cause instanceof Error
          ? cause.message
          : "Message could not be sent. Your draft is retained.",
      );
    } finally {
      setBusy(false);
    }
  }
  const messages = [...older, ...(detail?.messages ?? [])].filter(
    (m, i, all) => all.findIndex((other) => other.id === m.id) === i,
  );
  return (
    <section aria-label="InternMatch messages" className="flex min-h-0 flex-col gap-3">
      {!compact && (
        <div className="mb-3">
          <p className="text-sm text-brand">Dashboard / Inbox</p>
          <h1 className="mt-2 text-2xl font-bold">Inbox</h1>
          <p className="mt-2 text-muted-foreground">
            Messages with your assigned internship participants.
          </p>
        </div>
      )}
      {error && (
        <div role="alert" className="rounded-lg bg-brand-soft p-3 text-sm">
          {error}
          <button className="ml-3 underline" onClick={() => setRevision((v) => v + 1)}>
            Retry
          </button>
        </div>
      )}
      {!selected ? (
        <div className="overflow-hidden rounded-2xl border bg-card">
          <div className="flex items-center justify-between border-b bg-slate-50 p-4">
            <h2 className="font-semibold">All conversations</h2>
            <span className="text-xs text-muted-foreground">Assigned participants only</span>
          </div>
          {!inbox && !error && (
            <p role="status" className="p-5">
              Loading your inbox…
            </p>
          )}
          {inbox?.data.length === 0 && (
            <p className="p-5 text-sm text-muted-foreground">
              No conversations are available. Students need an enrollment; supervisors need an
              active assigned deployment.
            </p>
          )}
          <div className={compact ? "max-h-80 overflow-y-auto" : ""}>
            {inbox?.data.map((c) => (
              <button
                key={c.id}
                onClick={() => choose(c.id)}
                className="block w-full border-b p-4 text-left last:border-0 hover:bg-blue-50"
              >
                <span className="flex items-center justify-between gap-2">
                  <strong>
                    {c.type === "placement"
                      ? (c.host ?? "Placement conversation")
                      : "Program coordinator"}
                  </strong>
                  {!!c.unread_count && (
                    <span className="rounded-full bg-brand px-2 py-1 text-xs text-white">
                      {c.unread_count} unread
                    </span>
                  )}
                </span>
                <span className="mt-1 block text-sm">
                  {c.student} · {c.program}
                </span>
                <span className="mt-1 block text-xs text-muted-foreground">
                  {c.participants
                    .filter((p) => p.id !== user.id)
                    .map((p) => p.name)
                    .join(", ") || "Coordinator assignment pending"}
                  {!c.writable && " · Read only"}
                </span>
              </button>
            ))}
          </div>
          {inbox && inbox.last_page > 1 && (
            <div className="flex items-center justify-between p-3">
              <Button disabled={page === 1} onClick={() => setPage(page - 1)}>
                Previous
              </Button>
              <span>
                {page} / {inbox.last_page}
              </span>
              <Button disabled={page >= inbox.last_page} onClick={() => setPage(page + 1)}>
                Next
              </Button>
            </div>
          )}
        </div>
      ) : (
        <div className="flex min-h-0 flex-col overflow-hidden rounded-2xl border bg-card">
          <div className="border-b p-3">
            <button
              className="mb-2 text-sm font-semibold text-brand"
              disabled={busy || loadingOlder}
              onClick={() => choose(null)}
            >
              ← All conversations
            </button>
            <h2 className="font-semibold">
              {detail?.conversation.type === "placement"
                ? "Placement conversation"
                : "Program coordinator"}
            </h2>
            <p className="text-xs text-muted-foreground">
              {detail?.conversation.participants.map((p) => p.name).join(" · ")}
            </p>
          </div>
          {!detail && !error && (
            <p role="status" className="p-4">
              Loading conversation…
            </p>
          )}
          <div
            className={`${compact ? "h-60" : "h-[min(52vh,480px)]"} overflow-y-auto bg-slate-50 p-4`}
            aria-label="Messages"
          >
            {(older.length ? olderAvailable : detail?.has_older) && (
              <button
                className="mb-3 text-sm underline"
                disabled={loadingOlder}
                onClick={async () => {
                  setLoadingOlder(true);
                  try {
                    const value = await api<Detail>(
                      `/chat/conversations/${selected}?before=${messages[0]?.id}`,
                    );
                    setOlder((old) => [...value.messages, ...old]);
                    setOlderAvailable(value.has_older);
                  } catch (cause) {
                    setError(
                      cause instanceof Error ? cause.message : "Unable to load earlier messages.",
                    );
                  } finally {
                    setLoadingOlder(false);
                  }
                }}
              >
                Load earlier messages
              </button>
            )}
            {detail && !messages.length && (
              <p className="rounded-xl bg-blue-50 p-4 text-sm text-slate-600">
                Start a conversation with your assigned participants. Messages do not replace
                placement approval or official requirements.
              </p>
            )}
            {messages.map((m) => (
              <div
                key={m.id}
                className={`mb-3 flex ${m.sender_id === user.id ? "justify-end" : "justify-start"}`}
              >
                <div
                  className={`max-w-[90%] rounded-2xl p-3 ${m.sender_id === user.id ? "bg-blue-100" : "border bg-white"}`}
                >
                  <p className="text-xs font-bold">{m.sender}</p>
                  <p className="mt-1 whitespace-pre-wrap break-words text-sm">{m.body}</p>
                  <time className="mt-2 block text-[11px] text-slate-500">
                    {new Date(m.created_at).toLocaleString()}
                  </time>
                </div>
              </div>
            ))}
            <div ref={bottom} />
          </div>
          {detail?.conversation.writable ? (
            <form onSubmit={(e) => void send(e)} className="flex items-end gap-2 border-t p-3">
              <label className="flex-1">
                <span className="sr-only">Write a message</span>
                <textarea
                  value={draft}
                  onChange={(e) => setDraft(e.target.value)}
                  disabled={busy}
                  maxLength={2000}
                  rows={2}
                  placeholder="Write a message"
                  className="w-full resize-none rounded-xl border p-3 text-sm"
                />
              </label>
              <Button type="submit" disabled={busy || !draft.trim()}>
                {busy ? "Sending…" : "Send"}
              </Button>
            </form>
          ) : (
            detail && (
              <p className="border-t p-3 text-sm text-muted-foreground">
                {detail.conversation.coordinator_available
                  ? "This conversation is read-only because the enrollment or deployment has ended."
                  : "Messaging is unavailable until exactly one active coordinator is assigned to this program."}
              </p>
            )
          )}
        </div>
      )}
    </section>
  );
}

export function ChatSettings() {
  const [enabled, setEnabled] = useState<boolean | null>(null);
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  useEffect(() => {
    api<{ enabled: boolean }>("/chat/settings")
      .then((v) => setEnabled(v.enabled))
      .catch((e: Error) => setError(e.message));
  }, []);
  return (
    <section className="rounded-xl border bg-card p-6">
      <h1 className="text-2xl font-bold">Chat availability</h1>
      <p className="my-4 text-muted-foreground">
        Enable or disable messaging for students, coordinators and assigned host supervisors.
        Administrators cannot read conversations. Changes are audit logged.
      </p>
      {error && <p role="alert">{error}</p>}
      {enabled === null ? (
        <p>Loading settings…</p>
      ) : (
        <Button
          disabled={busy}
          onClick={async () => {
            setBusy(true);
            setError("");
            try {
              const v = await mutate<{ enabled: boolean }>(
                "/chat/settings",
                { enabled: !enabled },
                "PUT",
              );
              setEnabled(v.enabled);
            } catch (e) {
              setError(e instanceof Error ? e.message : "Unable to save.");
            } finally {
              setBusy(false);
            }
          }}
        >
          {enabled ? "Disable chat" : "Enable chat"}
        </Button>
      )}
    </section>
  );
}
