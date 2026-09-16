import { useState } from "react";
import { HOSTS } from "@/lib/internmatch";
import { Card, CardTitle, Pill, cx, statusTone } from "./ui";

export function AccessibilityMap({ note }: { note?: string }) {
  const [selected, setSelected] = useState(HOSTS[0]!.name);
  const active = HOSTS.find((h) => h.name === selected)!;

  return (
    <div className="grid gap-5 lg:grid-cols-[1.6fr_1fr]">
      <Card className="p-0">
        <div className="border-b border-border px-5 py-4">
          <h2 className="text-base font-semibold">Geographic distribution of host establishments</h2>
          <p className="mt-1 text-sm text-muted-foreground">
            {note ?? "Marker size reflects available slots; colour reflects travel accessibility from campus."}
          </p>
        </div>
        <div className="relative h-[420px] overflow-hidden rounded-b-lg bg-[oklch(0.94_0.02_200)]">
          <div
            className="absolute inset-0 opacity-60"
            style={{
              backgroundImage:
                "linear-gradient(to right, oklch(0.9 0.01 200) 1px, transparent 1px), linear-gradient(to bottom, oklch(0.9 0.01 200) 1px, transparent 1px)",
              backgroundSize: "44px 44px",
            }}
          />
          <div className="absolute top-[18%] left-[-6%] h-40 w-[70%] -rotate-6 rounded-[40%] bg-[oklch(0.92_0.05_150)]" />
          <div className="absolute bottom-[6%] left-[28%] h-52 w-[80%] rotate-3 rounded-[45%] bg-[oklch(0.93_0.045_140)]" />

          <MapPin x={22} y={34} label="Campus" campus />
          {HOSTS.map((h) => (
            <button
              key={h.name}
              type="button"
              onClick={() => setSelected(h.name)}
              className="absolute -translate-x-1/2 -translate-y-1/2"
              style={{ left: `${h.x}%`, top: `${h.y}%` }}
              aria-label={h.name}
            >
              <span
                className={cx(
                  "block rounded-full ring-2 ring-card transition-transform",
                  h.accessibility === "High" ? "bg-success" : h.accessibility === "Moderate" ? "bg-warn" : "bg-brand",
                  selected === h.name ? "scale-125 shadow-lg" : "hover:scale-110",
                )}
                style={{ width: 14 + h.slotsOpen * 4, height: 14 + h.slotsOpen * 4 }}
              />
            </button>
          ))}

          <div className="absolute right-4 bottom-4 rounded-md border border-border bg-card/95 px-3 py-2 text-xs">
            <p className="mb-1 font-semibold">Accessibility</p>
            <Legend color="bg-success" label="High (&lt; 5 km)" />
            <Legend color="bg-warn" label="Moderate (5–10 km)" />
            <Legend color="bg-brand" label="Low (&gt; 10 km)" />
          </div>
        </div>
      </Card>

      <div className="space-y-4">
        <Card>
          <CardTitle>{active.name}</CardTitle>
          <p className="text-sm text-muted-foreground">{active.field}</p>
          <div className="mt-4 space-y-2 text-sm">
            <Line label="Location" value={active.city} />
            <Line label="Distance from campus" value={`${active.km} km`} />
            <Line label="Est. travel time" value={active.travel} />
            <Line label="Available slots" value={`${active.slotsOpen} of ${active.slotsTotal}`} />
            <Line label="MOA" value={active.moa} />
          </div>
          <div className="mt-4 flex gap-2">
            <Pill tone={statusTone(active.accessibility)}>{active.accessibility} accessibility</Pill>
            <Pill tone="info">{active.match}% match</Pill>
          </div>
        </Card>
        <Card>
          <CardTitle>All establishments</CardTitle>
          <div className="space-y-1">
            {HOSTS.map((h) => (
              <button
                key={h.name}
                type="button"
                onClick={() => setSelected(h.name)}
                className={cx(
                  "flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm",
                  selected === h.name ? "bg-brand-soft font-semibold text-brand" : "hover:bg-muted",
                )}
              >
                <span>{h.name}</span>
                <span className="text-xs text-muted-foreground">{h.km} km</span>
              </button>
            ))}
          </div>
        </Card>
      </div>
    </div>
  );
}

function Legend({ color, label }: { color: string; label: string }) {
  return (
    <p className="flex items-center gap-2 text-muted-foreground">
      <span className={cx("size-2.5 rounded-full", color)} />
      <span dangerouslySetInnerHTML={{ __html: label }} />
    </p>
  );
}

function Line({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-3 border-b border-border/60 pb-2 last:border-0">
      <span className="text-muted-foreground">{label}</span>
      <span className="font-medium text-foreground">{value}</span>
    </div>
  );
}

function MapPin({ x, y, label, campus }: { x: number; y: number; label: string; campus?: boolean }) {
  return (
    <div className="absolute -translate-x-1/2 -translate-y-1/2" style={{ left: `${x}%`, top: `${y}%` }}>
      <div className={cx("size-4 rotate-45 rounded-sm ring-2 ring-card", campus ? "bg-foreground" : "bg-brand")} />
      <span className="mt-2 block -translate-x-1/3 rounded bg-card/90 px-1.5 py-0.5 text-[11px] font-semibold">
        {label}
      </span>
    </div>
  );
}
