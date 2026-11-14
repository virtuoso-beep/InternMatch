import { useEffect, useRef, type ReactNode } from "react";
import { useSessionUser } from "./SessionGuard";

/** Keep existing forms and validation inside the administrator reference dialog. */
export function AdminFormDialog({
  title,
  onClose,
  children,
}: {
  title: string;
  onClose: () => void;
  children: ReactNode;
}) {
  const user = useSessionUser();
  const ref = useRef<HTMLDialogElement>(null);
  useEffect(() => {
    if (user.role === "admin") ref.current?.showModal();
  }, [user.role]);
  if (user.role !== "admin") return <>{children}</>;
  return (
    <dialog
      ref={ref}
      aria-label={title}
      onCancel={onClose}
      onClick={(e) => {
        if (e.target === e.currentTarget) onClose();
      }}
      className="m-auto max-h-[90vh] w-[min(680px,calc(100vw-32px))] overflow-auto rounded-2xl border-0 bg-white p-6 text-foreground shadow-xl backdrop:bg-black/60"
    >
      <div className="mb-4 flex items-center justify-between gap-4">
        <h2 className="text-xl font-bold">{title}</h2>
        <button
          type="button"
          className="rounded border px-3 py-2"
          onClick={onClose}
          aria-label={`Close ${title}`}
        >
          ×
        </button>
      </div>
      {children}
    </dialog>
  );
}
