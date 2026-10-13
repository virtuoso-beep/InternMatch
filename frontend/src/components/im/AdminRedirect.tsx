import { useEffect } from "react";
import { managementUrl } from "@/lib/management";

export function AdminRedirect() {
  useEffect(() => {
    const section = window.location.pathname.split("/")[2] ?? "";
    window.location.replace(managementUrl(section));
  }, []);
  return (
    <main className="p-8">
      <p role="status">Opening InternMatch management…</p>
    </main>
  );
}
