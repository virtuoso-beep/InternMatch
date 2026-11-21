import { Eye, EyeOff, type LucideIcon } from "lucide-react";
import { forwardRef, useState, type InputHTMLAttributes, type ReactNode } from "react";
import { cx } from "@/components/im/ui";

type InputProps = Omit<InputHTMLAttributes<HTMLInputElement>, "className" | "id">;

const inputClass =
  "h-12 w-full rounded-lg border bg-background pl-11 text-sm text-foreground shadow-xs outline-none transition-[border-color,box-shadow] duration-fast ease-out placeholder:text-muted-foreground/70 focus:border-brand focus:ring-4 focus:ring-brand/15 disabled:opacity-60";

/** Labelled input with a leading icon, an optional trailing control and an inline error. */
export const AuthField = forwardRef<
  HTMLInputElement,
  InputProps & {
    id: string;
    label: string;
    icon: LucideIcon;
    error?: string | undefined;
    labelAction?: ReactNode;
    trailing?: ReactNode;
  }
>(function AuthField(
  {
    id,
    label,
    icon: Icon,
    error,
    labelAction,
    trailing,
    "aria-describedby": describedBy,
    ...input
  },
  ref,
) {
  const errorId = `${id}-error`;
  const describedByIds =
    [describedBy, error ? errorId : undefined].filter(Boolean).join(" ") || undefined;
  return (
    <div>
      <div className="mb-2 flex items-center justify-between gap-4">
        <label htmlFor={id} className="text-sm font-semibold text-foreground">
          {label}
        </label>
        {labelAction}
      </div>
      <div className="relative">
        <Icon
          aria-hidden="true"
          size={16}
          className="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-muted-foreground"
        />
        <input
          ref={ref}
          id={id}
          aria-invalid={error ? true : undefined}
          aria-describedby={describedByIds}
          className={cx(
            inputClass,
            trailing ? "pr-12" : "pr-4",
            error
              ? "border-destructive focus:border-destructive focus:ring-destructive/15"
              : "border-input",
          )}
          {...input}
        />
        {trailing}
      </div>
      {error && (
        <p id={errorId} className="mt-2 text-sm font-medium text-destructive">
          {error}
        </p>
      )}
    </div>
  );
});

/** Password input with a show/hide toggle that keeps focus in the field. */
export const PasswordField = forwardRef<
  HTMLInputElement,
  Omit<Parameters<typeof AuthField>[0], "type" | "trailing">
>(function PasswordField(props, ref) {
  const [visible, setVisible] = useState(false);
  return (
    <AuthField
      ref={ref}
      {...props}
      type={visible ? "text" : "password"}
      trailing={
        <button
          type="button"
          aria-label={visible ? "Hide password" : "Show password"}
          aria-pressed={visible}
          aria-controls={props.id}
          onClick={() => setVisible((value) => !value)}
          className="absolute top-1/2 right-2 inline-flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition-colors duration-fast hover:bg-muted hover:text-foreground focus-visible:outline-2 focus-visible:outline-brand"
        >
          {visible ? <EyeOff aria-hidden="true" size={16} /> : <Eye aria-hidden="true" size={16} />}
        </button>
      }
    />
  );
});
