import { useState, type ReactNode } from "react";
import { toast } from "sonner";
import { Button, ConfirmDialog, FormField, FormModal } from "./ui";
import { inputClass, toFormError, type FormError } from "./formUtils";

type ButtonLook = {
  /** Opener button look. */
  variant?: "primary" | "outline" | "ghost" | "danger";
  size?: "sm" | "md";
  disabled?: boolean;
  className?: string;
  "aria-label"?: string;
};

/**
 * Opener button + confirmation modal for an action that changes data with no extra input.
 * The modal names the item, Cancel has focus, the action button says what it does (red only when
 * `tone="danger"`). On success it closes, shows a toast and calls `onDone` so the page refreshes.
 * Errors from the server stay inside the modal.
 */
export function ConfirmButton({
  children,
  title,
  description,
  confirmLabel,
  tone = "primary",
  success,
  onConfirm,
  onDone,
  ...look
}: ButtonLook & {
  children: ReactNode;
  title: string;
  description: string;
  confirmLabel: string;
  tone?: "primary" | "danger";
  success: string;
  onConfirm: () => Promise<unknown>;
  onDone?: () => void;
}) {
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  return (
    <>
      <Button
        variant={look.variant ?? "outline"}
        size={look.size ?? "md"}
        disabled={look.disabled ?? false}
        {...(look.className ? { className: look.className } : {})}
        {...(look["aria-label"] ? { "aria-label": look["aria-label"] } : {})}
        onClick={() => {
          setError("");
          setOpen(true);
        }}
      >
        {children}
      </Button>
      <ConfirmDialog
        open={open}
        title={title}
        description={description}
        confirmLabel={confirmLabel}
        tone={tone}
        busy={busy}
        error={error || undefined}
        onCancel={() => setOpen(false)}
        onConfirm={async () => {
          if (busy) return;
          setBusy(true);
          setError("");
          try {
            await onConfirm();
            setOpen(false);
            toast.success(success);
            onDone?.();
          } catch (cause) {
            setError(toFormError(cause, "The action could not be completed.").message);
          } finally {
            setBusy(false);
          }
        }}
      />
    </>
  );
}

/**
 * Opener button + form modal that collects a required reason (reject, return, withdraw).
 * `field` is the API field name, so a Laravel 422 message for it appears under the textarea.
 */
export function ReasonButton({
  children,
  title,
  description,
  submitLabel,
  tone = "danger",
  label = "Reason",
  field = "reason",
  minLength = 1,
  success,
  onSubmit,
  onDone,
  ...look
}: ButtonLook & {
  children: ReactNode;
  title: string;
  description?: string;
  submitLabel: string;
  tone?: "primary" | "danger";
  label?: string;
  field?: string;
  minLength?: number;
  success: string;
  onSubmit: (reason: string) => Promise<unknown>;
  onDone?: () => void;
}) {
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [reason, setReason] = useState("");
  const [error, setError] = useState<FormError | null>(null);
  return (
    <>
      <Button
        variant={look.variant ?? "outline"}
        size={look.size ?? "md"}
        disabled={look.disabled ?? false}
        {...(look.className ? { className: look.className } : {})}
        {...(look["aria-label"] ? { "aria-label": look["aria-label"] } : {})}
        onClick={() => {
          setReason("");
          setError(null);
          setOpen(true);
        }}
      >
        {children}
      </Button>
      <FormModal
        open={open}
        onClose={() => setOpen(false)}
        title={title}
        description={description}
        size="sm"
        submitLabel={submitLabel}
        busyLabel="Saving…"
        tone={tone}
        busy={busy}
        error={error}
        onSubmit={async () => {
          setBusy(true);
          setError(null);
          try {
            await onSubmit(reason.trim());
            setOpen(false);
            toast.success(success);
            onDone?.();
          } catch (cause) {
            setError(toFormError(cause, "The action could not be completed."));
          } finally {
            setBusy(false);
          }
        }}
      >
        <FormField
          label={label}
          required
          {...(minLength > 1 ? { helper: `At least ${minLength} characters.` } : {})}
        >
          <textarea
            name={field}
            required
            minLength={minLength}
            maxLength={5000}
            rows={4}
            disabled={busy}
            className={inputClass}
            value={reason}
            onChange={(event) => setReason(event.target.value)}
          />
        </FormField>
      </FormModal>
    </>
  );
}
