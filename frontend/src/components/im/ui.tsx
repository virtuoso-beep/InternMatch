import {
  useCallback,
  useEffect,
  useId,
  useLayoutEffect,
  useRef,
  useState,
  type ReactNode,
} from "react";
import { createPortal } from "react-dom";
import { X } from "lucide-react";
import type { FormError } from "./formUtils";
import { statusTone as sharedStatusTone, type Tone as SharedTone } from "@/lib/status";

export function cx(...parts: Array<string | false | null | undefined>) {
  return parts.filter(Boolean).join(" ");
}

export function downloadText(filename: string, content: string, type = "text/plain") {
  const blob = new Blob([content], { type });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}

export function PageHeader({
  title,
  subtitle,
  action,
  headingLevel = 1,
}: {
  title: string;
  subtitle?: string;
  action?: ReactNode;
  headingLevel?: 1 | 2;
}) {
  const Heading = headingLevel === 1 ? "h1" : "h2";
  return (
    <div className="mb-6 flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
      <div className="max-w-3xl min-w-0">
        <Heading
          className={cx(
            "font-semibold tracking-tight text-foreground",
            headingLevel === 1 ? "text-2xl" : "text-lg",
          )}
        >
          {title}
        </Heading>
        {subtitle && (
          <p className="mt-1.5 text-sm leading-relaxed text-muted-foreground">{subtitle}</p>
        )}
      </div>
      {action && <div className="flex flex-wrap items-center gap-2">{action}</div>}
    </div>
  );
}

export function Card({
  children,
  className,
}: {
  children: ReactNode;
  className?: string | undefined;
}) {
  return (
    <div
      className={cx(
        "min-w-0 rounded-xl border border-border bg-card p-5 text-card-foreground shadow-xs",
        className,
      )}
    >
      {children}
    </div>
  );
}

export function CardTitle({ children, right }: { children: ReactNode; right?: ReactNode }) {
  return (
    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
      <h2 className="text-base font-semibold text-foreground">{children}</h2>
      {right}
    </div>
  );
}

/** Badge colours by tone (the system's existing status colours). */
const toneMap = {
  brand: "bg-brand-soft text-brand",
  success: "bg-success-soft text-success",
  warn: "bg-warn-soft text-warn",
  danger: "bg-brand-soft text-brand",
  info: "bg-info-soft text-info",
  muted: "bg-muted text-muted-foreground",
} as const;

const dotMap = {
  brand: "bg-brand",
  success: "bg-success",
  warn: "bg-warn",
  danger: "bg-brand",
  info: "bg-info",
  muted: "bg-muted-foreground/40",
} as const;

export type Tone = SharedTone;

/** "under_review" -> "Under review". For machine status codes shown to people. */
export function formatStatus(value: string): string {
  const text = value.replaceAll("_", " ").trim();
  return text ? text[0]!.toUpperCase() + text.slice(1) : text;
}

const STATUS_CODE = /^[a-z]+(?:[_ ][a-z]+)*$/;

/** Status badge. The dot repeats the colour so status is never shown by colour alone without text. */
export function Pill({ children, tone = "muted" }: { children: ReactNode; tone?: Tone }) {
  // Plain lowercase status codes ("active", "under_review") read as labels; other content is untouched.
  const content =
    typeof children === "string" && STATUS_CODE.test(children) ? formatStatus(children) : children;
  return (
    <span
      className={cx(
        "inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap",
        toneMap[tone],
      )}
    >
      {tone !== "brand" && (
        <span aria-hidden="true" className={cx("size-1.5 rounded-full", dotMap[tone])} />
      )}
      {content}
    </span>
  );
}

/** Status -> tone. One mapping for badges and charts; see lib/status.ts. */
export function statusTone(status: string): Tone {
  return sharedStatusTone(status);
}

/** Raw cosine similarity to two decimals (spec §8): never a "% match", no strength labels. */
export function formatSimilarity(score: number | string | null | undefined): string {
  const value = Number(score);
  return score === null || score === undefined || Number.isNaN(value) ? "—" : value.toFixed(2);
}

export function StatCard({ label, value, tone }: { label: string; value: string; tone?: Tone }) {
  const color =
    tone === "success"
      ? "text-success"
      : tone === "warn"
        ? "text-warn"
        : tone === "danger"
          ? "text-brand"
          : tone === "brand"
            ? "text-brand"
            : "text-foreground";
  return (
    <Card className="p-4">
      <p className="text-sm text-muted-foreground">{label}</p>
      <p className={cx("mt-1 text-3xl font-semibold tracking-tight tabular-nums", color)}>
        {value}
      </p>
    </Card>
  );
}

export function StatGrid({ children }: { children: ReactNode }) {
  return <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{children}</div>;
}

export function Button({
  children,
  variant = "primary",
  size = "md",
  onClick,
  type = "button",
  form,
  disabled = false,
  className,
  title,
  autoFocus = false,
  "aria-label": ariaLabel,
  ...rest
}: {
  children: ReactNode;
  variant?: "primary" | "outline" | "ghost" | "danger";
  size?: "sm" | "md";
  onClick?: () => void;
  type?: "button" | "submit";
  /** Id of a form outside the button (e.g. a modal footer submitting the modal's form). */
  form?: string;
  disabled?: boolean;
  className?: string;
  title?: string;
  /** Marks the control the modal focuses first. */
  autoFocus?: boolean;
  "aria-label"?: string;
  "data-discard-keep"?: boolean;
}) {
  const styles = {
    primary: "bg-brand text-brand-foreground shadow-xs hover:opacity-90",
    outline: "border border-input bg-card text-foreground shadow-xs hover:bg-muted",
    ghost: "text-brand hover:bg-brand-soft",
    danger: "bg-destructive text-destructive-foreground shadow-xs hover:opacity-90",
  }[variant];
  return (
    <button
      type={type}
      form={form}
      onClick={onClick}
      disabled={disabled}
      title={title}
      aria-label={ariaLabel}
      {...(autoFocus ? { "data-autofocus": "" } : {})}
      {...(rest["data-discard-keep"] ? { "data-discard-keep": "" } : {})}
      className={cx(
        // relative: keeps any sr-only text positioned inside the button (no page-wide overflow in scrolled tables)
        "relative inline-flex items-center justify-center gap-2 rounded-lg font-semibold whitespace-nowrap transition-colors duration-150 disabled:cursor-not-allowed disabled:opacity-50 [&_svg]:shrink-0",
        size === "sm" ? "h-8 px-3 text-xs" : "min-h-10 px-4 py-2 text-sm",
        styles,
        className,
      )}
    >
      {children}
    </button>
  );
}

export type ModalSize = "sm" | "md" | "lg";
/** Small 400 px (confirmations), medium 560 px (normal forms), large 880 px (long forms, details). */
const MODAL_WIDTH: Record<ModalSize, string> = {
  sm: "sm:max-w-[400px]",
  md: "sm:max-w-[560px]",
  lg: "sm:max-w-[880px]",
};
const FOCUSABLE =
  'a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';

/**
 * The one modal for the whole app (docs/DESIGN_SYSTEM.md, "Modals").
 * - Header (title, optional description, close button) and footer stay fixed; only the body scrolls.
 * - Centred with a dimmed backdrop from 640 px up; below that it is a full-screen sheet.
 * - Focus moves in when it opens, stays inside while it is open, and returns to the opener on close.
 * - Esc and the close button ask "Discard changes?" first when `isDirty` reports edits.
 * - The backdrop closes only simple view and confirmation modals (`closeOnBackdrop`), never forms.
 */
export function Modal({
  open,
  onClose,
  title,
  description,
  children,
  footer,
  size = "md",
  dismissible = true,
  closeOnBackdrop = false,
  isDirty,
}: {
  open: boolean;
  onClose: () => void;
  title: string;
  description?: string | undefined;
  children: ReactNode;
  footer?: ReactNode;
  size?: ModalSize;
  /** False while an action is running: Esc, the backdrop and the close button are ignored. */
  dismissible?: boolean;
  closeOnBackdrop?: boolean;
  /** Returns true when the user has unsaved edits. */
  isDirty?: () => boolean;
}) {
  const titleId = useId();
  const descriptionId = useId();
  const discardId = useId();
  const panel = useRef<HTMLDivElement>(null);
  const [discarding, setDiscarding] = useState(false);
  const latest = useRef({ onClose, isDirty, dismissible });
  latest.current = { onClose, isDirty, dismissible };
  const requestClose = useCallback(() => {
    const { onClose: close, isDirty: dirty, dismissible: allowed } = latest.current;
    if (!allowed) return;
    if (dirty?.()) setDiscarding(true);
    else close();
  }, []);

  useEffect(() => {
    if (!open) {
      setDiscarding(false);
      return;
    }
    const opener = document.activeElement as HTMLElement | null;
    const overflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    const frame = requestAnimationFrame(() => {
      const root = panel.current;
      if (!root) return;
      const target =
        root.querySelector<HTMLElement>("[data-autofocus]") ??
        root.querySelector<HTMLElement>(
          '[data-modal-body] input:not([type="hidden"]):not([disabled]),[data-modal-body] select:not([disabled]),[data-modal-body] textarea:not([disabled])',
        ) ??
        root;
      target.focus();
    });
    return () => {
      cancelAnimationFrame(frame);
      document.body.style.overflow = overflow;
      opener?.focus?.();
    };
  }, [open]);

  useEffect(() => {
    if (!discarding) return;
    panel.current?.querySelector<HTMLElement>("[data-discard-keep]")?.focus();
  }, [discarding]);

  useEffect(() => {
    if (!open) return;
    const onKey = (event: KeyboardEvent) => {
      const root = panel.current;
      if (!root) return;
      if (event.key === "Escape") {
        event.preventDefault();
        if (discarding) setDiscarding(false);
        else requestClose();
        return;
      }
      if (event.key !== "Tab") return;
      const scope = discarding ? root.querySelector<HTMLElement>("[data-discard]") : root;
      const items = Array.from(scope?.querySelectorAll<HTMLElement>(FOCUSABLE) ?? []).filter(
        (el) => el.offsetParent !== null || el === document.activeElement,
      );
      if (items.length === 0) {
        event.preventDefault();
        return;
      }
      const first = items[0]!;
      const last = items[items.length - 1]!;
      const active = document.activeElement as HTMLElement | null;
      if (!active || !scope?.contains(active)) {
        event.preventDefault();
        first.focus();
      } else if (event.shiftKey && active === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus();
      }
    };
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [open, discarding, requestClose]);

  if (!open) return null;
  // Rendered into <body> so a modal never nests inside a page <form>, <details> or overflow container.
  const host = typeof document === "undefined" ? null : document.body;
  const content = (
    <div
      className="fixed inset-0 z-50 flex items-stretch justify-center bg-foreground/30 animate-in fade-in-0 duration-150 sm:items-center sm:p-4"
      role="presentation"
      onMouseDown={(event) => {
        if (event.target === event.currentTarget && closeOnBackdrop) requestClose();
      }}
    >
      <div
        ref={panel}
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        aria-describedby={description ? descriptionId : undefined}
        tabIndex={-1}
        className={cx(
          "relative flex h-dvh w-full flex-col overflow-hidden bg-popover text-popover-foreground shadow-lg outline-none animate-in fade-in-0 zoom-in-95 duration-150 motion-reduce:animate-none sm:h-auto sm:max-h-[calc(100dvh-2rem)] sm:rounded-xl sm:border sm:border-border",
          MODAL_WIDTH[size],
        )}
      >
        <div className="flex shrink-0 items-start justify-between gap-4 border-b border-border px-5 py-4">
          <div className="min-w-0">
            <h2 id={titleId} className="text-base font-semibold break-words">
              {title}
            </h2>
            {description && (
              <p id={descriptionId} className="mt-1 text-sm text-muted-foreground">
                {description}
              </p>
            )}
          </div>
          <button
            type="button"
            onClick={requestClose}
            disabled={!dismissible}
            aria-label="Close"
            title="Close"
            className="-mr-1 shrink-0 rounded-md p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-40"
          >
            <X size={18} aria-hidden="true" />
          </button>
        </div>
        <div data-modal-body className="min-h-0 flex-1 overflow-y-auto px-5 py-4">
          {children}
        </div>
        {footer && (
          <div className="flex shrink-0 flex-wrap justify-end gap-2 border-t border-border px-5 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] max-sm:[&>button]:flex-1">
            {footer}
          </div>
        )}
        {discarding && (
          <div
            data-discard
            role="alertdialog"
            aria-modal="true"
            aria-labelledby={discardId}
            className="absolute inset-0 z-10 flex items-center justify-center bg-popover/80 p-4"
          >
            <div className="w-full max-w-[340px] rounded-xl border border-border bg-popover p-5 shadow-lg">
              <h3 id={discardId} className="text-base font-semibold">
                Discard changes?
              </h3>
              <p className="mt-1.5 text-sm text-muted-foreground">
                You have unsaved changes. If you close now, they will be lost.
              </p>
              <div className="mt-4 flex flex-wrap justify-end gap-2">
                <Button variant="outline" onClick={() => setDiscarding(false)} data-discard-keep>
                  Keep editing
                </Button>
                <Button variant="danger" onClick={onClose}>
                  Discard changes
                </Button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
  return host ? createPortal(content, host) : content;
}

/** Read-only modal (details, history): a Close button in the footer; the backdrop also closes it. */
export function ViewModal({
  open,
  onClose,
  title,
  description,
  size = "md",
  actions,
  children,
}: {
  open: boolean;
  onClose: () => void;
  title: string;
  description?: string | undefined;
  size?: ModalSize;
  actions?: ReactNode;
  children: ReactNode;
}) {
  return (
    <Modal
      open={open}
      onClose={onClose}
      title={title}
      description={description}
      size={size}
      closeOnBackdrop
      footer={
        <>
          {actions}
          <Button variant="outline" onClick={onClose}>
            Close
          </Button>
        </>
      }
    >
      {children}
    </Modal>
  );
}

/**
 * Confirmation for destructive or irreversible actions. Name the item in `description`
 * (for example: "Remove Bank Reconciliation from your competencies? This cannot be undone.").
 * A server error keeps the dialog open and is shown inside it.
 */
export function ConfirmDialog({
  open,
  title,
  description,
  confirmLabel,
  tone = "danger",
  busy = false,
  error,
  onConfirm,
  onCancel,
}: {
  open: boolean;
  title: string;
  description: string;
  confirmLabel: string;
  tone?: "danger" | "primary";
  busy?: boolean;
  error?: string | undefined;
  onConfirm: () => void;
  onCancel: () => void;
}) {
  return (
    <Modal
      open={open}
      onClose={onCancel}
      title={title}
      size="sm"
      dismissible={!busy}
      closeOnBackdrop
      footer={
        <>
          <Button variant="outline" onClick={onCancel} disabled={busy} autoFocus>
            Cancel
          </Button>
          <Button
            variant={tone === "danger" ? "danger" : "primary"}
            onClick={onConfirm}
            disabled={busy}
          >
            {busy ? "Working…" : confirmLabel}
          </Button>
        </>
      }
    >
      <p className="text-sm leading-relaxed text-muted-foreground">{description}</p>
      {error && (
        <p
          role="alert"
          className="mt-3 rounded-lg border border-brand/20 bg-brand-soft px-3 py-2 text-sm text-brand"
        >
          {error}
        </p>
      )}
    </Modal>
  );
}

/** Label above the control, required marker, optional helper text. Errors are shown under the control. */
export function FormField({
  label,
  required = false,
  helper,
  className,
  children,
}: {
  label: string;
  required?: boolean;
  helper?: string;
  className?: string;
  children: ReactNode;
}) {
  return (
    <label className={cx("block", className)}>
      <span className="mb-1.5 block text-sm font-medium text-foreground">
        {label}
        {required && (
          <span aria-hidden="true" className="text-brand">
            {" "}
            *
          </span>
        )}
      </span>
      {children}
      {helper && <span className="mt-1 block text-xs text-muted-foreground">{helper}</span>}
    </label>
  );
}

/** Small section heading inside a form. */
export function FormSection({
  title,
  description,
  children,
}: {
  title: string;
  description?: string;
  children: ReactNode;
}) {
  return (
    <fieldset className="min-w-0 space-y-3 border-0 p-0">
      <legend className="mb-1 text-sm font-semibold text-foreground">{title}</legend>
      {description && <p className="-mt-1 text-xs text-muted-foreground">{description}</p>}
      {children}
    </fieldset>
  );
}

/** One column on phones; two columns from 640 px up. Use only for short fields. */
export function FormGrid({ children }: { children: ReactNode }) {
  return <div className="grid gap-4 sm:grid-cols-2">{children}</div>;
}

function snapshotForm(form: HTMLFormElement | null): string {
  if (!form) return "";
  return Array.from(form.elements)
    .map((el, i) => {
      const control = el as HTMLInputElement;
      if (!("value" in control) || control.type === "button" || control.type === "submit")
        return "";
      return `${i}:${control.type}:${control.type === "checkbox" || control.type === "radio" ? control.checked : control.value}`;
    })
    .join("|");
}

/**
 * Form in a modal. Pass the same `onSubmit` the form used before; keep browser validation.
 * - Submit shows `busyLabel` and is disabled while saving, so a double click cannot submit twice.
 * - `error` (from `toFormError`) puts each Laravel field error under its control (matched by the
 *   control's `name`, or `data-field` on a group) and focuses the first invalid one; messages that
 *   match no control appear in a short summary at the top. The modal stays open and keeps the input.
 * - Closing a form with edits asks "Discard changes?" first.
 */
export function FormModal({
  open,
  onClose,
  title,
  description,
  size = "md",
  submitLabel = "Save",
  busyLabel = "Saving…",
  busy = false,
  submitDisabled = false,
  tone = "primary",
  onSubmit,
  error,
  children,
}: {
  open: boolean;
  onClose: () => void;
  title: string;
  description?: string | undefined;
  size?: ModalSize;
  submitLabel?: string;
  busyLabel?: string;
  busy?: boolean;
  submitDisabled?: boolean;
  tone?: "primary" | "danger";
  onSubmit: () => void | Promise<void>;
  error?: FormError | null | undefined;
  children: ReactNode;
}) {
  const formId = useId();
  const form = useRef<HTMLFormElement>(null);
  const baseline = useRef("");
  const [unmatched, setUnmatched] = useState<string[]>([]);

  useEffect(() => {
    if (!open) return;
    const frame = requestAnimationFrame(() => {
      baseline.current = snapshotForm(form.current);
    });
    return () => cancelAnimationFrame(frame);
  }, [open]);

  useLayoutEffect(() => {
    const root = form.current;
    if (!root) return;
    const injected: HTMLElement[] = [];
    const flagged: Element[] = [];
    const rest: string[] = [];
    const fields = error?.fields ?? {};
    for (const [key, messages] of Object.entries(fields)) {
      const lookup = (k: string) =>
        root.querySelector<HTMLElement>(
          `[name="${CSS.escape(k)}"],[data-field="${CSS.escape(k)}"]`,
        );
      const target = lookup(key) ?? lookup(key.replace(/\.\d+(\.[^.]+)?$/, ""));
      if (!target || !messages[0]) {
        rest.push(...messages);
        continue;
      }
      const note = document.createElement("p");
      note.id = `${formId}-${key.replace(/\W/g, "-")}`;
      note.setAttribute("role", "alert");
      note.setAttribute("data-field-error", key);
      note.className = "mt-1 block text-sm text-brand";
      note.textContent = messages[0];
      target.insertAdjacentElement("afterend", note);
      target.setAttribute("aria-invalid", "true");
      target.setAttribute("aria-describedby", note.id);
      injected.push(note);
      flagged.push(target);
    }
    setUnmatched(rest);
    const first = flagged[0] as HTMLElement | undefined;
    first?.focus?.();
    const clear = (event: Event) => {
      const el = event.target as HTMLElement;
      if (el.getAttribute?.("aria-invalid") !== "true") return;
      el.removeAttribute("aria-invalid");
      el.removeAttribute("aria-describedby");
      injected
        .find((n) => n.id === `${formId}-${(el.getAttribute("name") ?? "").replace(/\W/g, "-")}`)
        ?.remove();
    };
    root.addEventListener("input", clear);
    root.addEventListener("change", clear);
    return () => {
      root.removeEventListener("input", clear);
      root.removeEventListener("change", clear);
      injected.forEach((n) => n.remove());
      flagged.forEach((el) => {
        el.removeAttribute("aria-invalid");
        el.removeAttribute("aria-describedby");
      });
    };
  }, [error, formId]);

  const summary = error && Object.keys(error.fields).length === 0 ? [error.message] : unmatched;
  return (
    <Modal
      open={open}
      onClose={onClose}
      title={title}
      description={description}
      size={size}
      dismissible={!busy}
      isDirty={() => !!form.current && snapshotForm(form.current) !== baseline.current}
      footer={
        <>
          <Button variant="outline" onClick={onClose} disabled={busy}>
            Cancel
          </Button>
          <Button
            type="submit"
            form={formId}
            variant={tone === "danger" ? "danger" : "primary"}
            disabled={busy || submitDisabled}
          >
            {busy ? busyLabel : submitLabel}
          </Button>
        </>
      }
    >
      {summary.length > 0 && (
        <div
          role="alert"
          className="mb-4 rounded-lg border border-brand/20 bg-brand-soft px-3 py-2 text-sm text-brand"
        >
          {summary.map((line) => (
            <p key={line}>{line}</p>
          ))}
        </div>
      )}
      <form
        id={formId}
        ref={form}
        className="space-y-5"
        onSubmit={(event) => {
          event.preventDefault();
          if (!busy) void onSubmit();
        }}
      >
        {children}
      </form>
    </Modal>
  );
}

export function FilterChips({
  options,
  value,
  onChange,
}: {
  options: string[];
  value: string;
  onChange: (v: string) => void;
}) {
  return (
    <div className="mb-5 flex flex-wrap gap-2" role="group">
      {options.map((o) => (
        <button
          key={o}
          type="button"
          aria-pressed={o === value}
          onClick={() => onChange(o)}
          className={cx(
            "rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors",
            o === value
              ? "border-brand bg-brand text-brand-foreground"
              : "border-input bg-card text-foreground hover:bg-muted",
          )}
        >
          {o}
        </button>
      ))}
    </div>
  );
}

export function Table({ head, children }: { head: string[]; children: ReactNode }) {
  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[520px] text-sm">
        <thead>
          <tr className="border-b border-border text-left">
            {head.map((h) => (
              <th
                key={h}
                scope="col"
                className="pr-4 pb-2.5 align-bottom text-xs font-semibold text-muted-foreground last:pr-0"
              >
                {h}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>{children}</tbody>
      </table>
    </div>
  );
}

export function Row({ children }: { children: ReactNode }) {
  return (
    <tr className="border-b border-border/70 transition-colors last:border-0 hover:bg-muted/50 [&>td]:py-3 [&>td]:pr-4 [&>td]:align-top [&>td:last-child]:pr-0">
      {children}
    </tr>
  );
}

export function Meter({
  value,
  tone = "brand",
}: {
  value: number;
  tone?: "brand" | "success" | "warn" | "danger" | "info";
}) {
  const bg = {
    brand: "bg-brand",
    success: "bg-success",
    warn: "bg-warn",
    danger: "bg-brand",
    info: "bg-info",
  }[tone];
  return (
    <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
      <div
        className={cx("h-full rounded-full transition-[width] duration-300", bg)}
        style={{ width: `${Math.max(0, Math.min(100, value))}%` }}
      />
    </div>
  );
}

export function Field({ label, value }: { label: string; value: ReactNode }) {
  return (
    <div className="min-w-0">
      <p className="text-xs font-medium text-muted-foreground">{label}</p>
      <p className="mt-1 text-sm font-medium break-words text-foreground">{value}</p>
    </div>
  );
}

export function Bars({ data }: { data: Array<{ label: string; value: number; max?: number }> }) {
  return (
    <div className="space-y-3">
      {data.map((d) => (
        <div key={d.label}>
          <div className="mb-1 flex justify-between gap-3 text-sm">
            <span className="text-foreground">{d.label}</span>
            <span className="font-semibold text-muted-foreground tabular-nums">{d.value}%</span>
          </div>
          <Meter value={(d.value / (d.max ?? 100)) * 100} />
        </div>
      ))}
    </div>
  );
}

/** Human-readable local date/time for API timestamps; falls back to the raw value. */
export function formatDateTime(value: string | null | undefined): string {
  if (!value) return "—";
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? value
    : date.toLocaleString(undefined, { dateStyle: "medium", timeStyle: "short" });
}

/** "chat.availability_updated" -> "Chat · availability updated". Codes stay raw in exports. */
export function formatAction(action: string): string {
  const [area = "", ...rest] = action.split(".");
  const words = (value: string) => value.replaceAll("_", " ");
  const head = words(area).replace(/^./, (c) => c.toUpperCase());
  return rest.length ? `${head} · ${rest.map(words).join(" · ")}` : head;
}

/** Display name for a student's account; archived (soft-deleted) accounts come back as null. */
export function studentName(user: { name?: string | null } | null | undefined): string {
  return user?.name ?? "Archived student account";
}

/**
 * Spec §13A: show the weighted result as a 1–5 rating with its descriptor
 * (4.50–5.00 Outstanding; 3.50–4.49 Very Satisfactory; 2.50–3.49 Satisfactory; 1.50–2.49 Fair; below 1.50 Poor).
 */
export function formatRating(percentage: number | string): string {
  const rating = (Number(percentage) / 100) * 5;
  const descriptor =
    rating >= 4.5
      ? "Outstanding"
      : rating >= 3.5
        ? "Very Satisfactory"
        : rating >= 2.5
          ? "Satisfactory"
          : rating >= 1.5
            ? "Fair"
            : "Poor";
  return `${rating.toFixed(2)} / 5 · ${descriptor} (${Number(percentage).toFixed(1)}%)`;
}
