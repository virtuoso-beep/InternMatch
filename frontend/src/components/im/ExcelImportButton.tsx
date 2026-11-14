import { useEffect, useRef, useState } from "react";
import { Button } from "./ui";
import { useSessionUser } from "./SessionGuard";
import { ExcelImportWorkspace, type ImportModule } from "./ExcelImportWorkspace";

export function ExcelImportButton({
  module,
  onImported,
}: {
  module: ImportModule;
  onImported: () => void;
}) {
  const user = useSessionUser();
  const [open, setOpen] = useState(false);
  const callback = useRef(onImported);
  callback.current = onImported;
  useEffect(() => {
    const refresh = () => callback.current();
    const storage = (event: StorageEvent) => {
      if (event.key === "internmatch:records-imported") refresh();
    };
    window.addEventListener("internmatch:records-imported", refresh);
    window.addEventListener("storage", storage);
    return () => {
      window.removeEventListener("internmatch:records-imported", refresh);
      window.removeEventListener("storage", storage);
    };
  }, []);
  if (user.role !== "coordinator" && !(user.role === "admin" && module === "student")) return null;
  return (
    <div className="my-4">
      <Button variant="outline" onClick={() => setOpen((value) => !value)}>
        {open ? "Close Excel import" : module === "student" ? "Import Students" : "Import Excel"}
      </Button>
      {open && (
        <section
          aria-label={`Import ${module} records`}
          className="mt-4 rounded-lg border bg-muted/20 p-4"
        >
          <ExcelImportWorkspace module={module} />
        </section>
      )}
    </div>
  );
}
