export function managementPath(section = ""): string {
  if (!section || section === "dashboard") return "/manage";
  return `/manage/administration?section=${encodeURIComponent(section)}`;
}

/** Administrators use the canonical Laravel interface. */
export function managementUrl(section = ""): string {
  const configured = import.meta.env["VITE_MANAGEMENT_URL"];
  const origin = new URL(configured || window.location.origin);
  if (!configured && ["localhost", "127.0.0.1", "[::1]"].includes(origin.hostname))
    origin.port = "8000";
  const target = new URL(managementPath(section), origin);
  origin.pathname = target.pathname;
  origin.search = target.search;
  origin.hash = "";
  return origin.toString();
}
