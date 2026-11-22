/** Roles that may opt in to test data on aggregate screens (backend: App\Support\SyntheticData). */
export const SYNTHETIC_ROLES = ["coordinator", "dean", "admin"];

export const SYNTHETIC_LABEL = "Contains test data";

/** Appends include_synthetic=1 to an API path when the switch is on. */
export function withSynthetic(path: string, include: boolean): string {
  if (!include) return path;
  return `${path}${path.includes("?") ? "&" : "?"}include_synthetic=1`;
}
