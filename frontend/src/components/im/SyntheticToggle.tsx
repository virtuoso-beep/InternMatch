import { SYNTHETIC_LABEL, SYNTHETIC_ROLES } from "@/lib/synthetic";
import { useSessionUser } from "./SessionGuard";
import { Pill } from "./ui";

/** "Include synthetic data" switch; hidden for roles that never see aggregate data. Off by default. */
export function SyntheticToggle({
  checked,
  onChange,
  disabled,
}: {
  checked: boolean;
  onChange: (value: boolean) => void;
  disabled?: boolean;
}) {
  const user = useSessionUser();
  if (!SYNTHETIC_ROLES.includes(user.role)) return null;
  return (
    <span className="inline-flex flex-wrap items-center gap-2 text-sm">
      <label className="inline-flex items-center gap-2 font-medium">
        <input
          type="checkbox"
          checked={checked}
          disabled={disabled}
          onChange={(e) => onChange(e.target.checked)}
        />
        Include synthetic data
      </label>
      {checked && <Pill tone="warn">{SYNTHETIC_LABEL}</Pill>}
    </span>
  );
}
