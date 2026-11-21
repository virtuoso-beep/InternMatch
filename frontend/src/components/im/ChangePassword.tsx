import { useState } from "react";
import { toast } from "sonner";
import { mutate } from "@/lib/api";
import { FormField, FormModal } from "./ui";
import { inputClass, toFormError, type FormError } from "./formUtils";

const empty = { current_password: "", password: "", password_confirmation: "" };

/** Change password after the first sign-in (PUT /account/password). Available to every role. */
export function ChangePasswordModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const [value, setValue] = useState(empty);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<FormError | null>(null);
  const close = () => {
    setValue(empty);
    setError(null);
    onClose();
  };
  const field = (name: keyof typeof empty, label: string, helper?: string) => (
    <FormField label={label} required {...(helper ? { helper } : {})}>
      <input
        type="password"
        name={name}
        required
        autoComplete={name === "current_password" ? "current-password" : "new-password"}
        disabled={busy}
        className={inputClass}
        value={value[name]}
        onChange={(event) => setValue((current) => ({ ...current, [name]: event.target.value }))}
      />
    </FormField>
  );
  return (
    <FormModal
      open={open}
      onClose={close}
      size="sm"
      title="Change password"
      description="Enter your current password, then choose a new one."
      submitLabel="Change password"
      busy={busy}
      error={error}
      onSubmit={async () => {
        setBusy(true);
        setError(null);
        try {
          await mutate("/account/password", value, "PUT");
          close();
          toast.success("Password changed");
        } catch (cause) {
          setError(toFormError(cause, "Unable to change the password."));
        } finally {
          setBusy(false);
        }
      }}
    >
      {field("current_password", "Current password")}
      {field(
        "password",
        "New password",
        "At least 12 characters with upper and lower case letters, a number and a symbol.",
      )}
      {field("password_confirmation", "Confirm new password")}
    </FormModal>
  );
}
