/** Light, dark or follow the operating system. Stored per browser; the default is "system". */
export type ThemePreference = "light" | "dark" | "system";

export const THEME_STORAGE_KEY = "internmatch:theme";

export function readThemePreference(): ThemePreference {
  try {
    const value = window.localStorage.getItem(THEME_STORAGE_KEY);
    return value === "light" || value === "dark" ? value : "system";
  } catch {
    return "system";
  }
}

export function resolveTheme(preference: ThemePreference): "light" | "dark" {
  if (preference !== "system") return preference;
  return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

export function applyTheme(preference: ThemePreference): void {
  const dark = resolveTheme(preference) === "dark";
  document.documentElement.classList.toggle("dark", dark);
  try {
    if (preference === "system") window.localStorage.removeItem(THEME_STORAGE_KEY);
    else window.localStorage.setItem(THEME_STORAGE_KEY, preference);
  } catch {
    // Storage can be blocked; the theme still applies for this page view.
  }
  window.dispatchEvent(new CustomEvent("internmatch:theme-changed"));
}

/** Runs in <head> before first paint so the page never flashes the wrong theme. */
export const THEME_BOOT_SCRIPT = `(function(){try{var p=localStorage.getItem("${THEME_STORAGE_KEY}");var d=p==="dark"||(p!=="light"&&window.matchMedia("(prefers-color-scheme: dark)").matches);if(d)document.documentElement.classList.add("dark");}catch(e){}})();`;
