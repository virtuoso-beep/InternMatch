import { useEffect, useRef, useState } from "react";
import type { Map as LeafletMap, Marker } from "leaflet";
import "leaflet/dist/leaflet.css";
import { api } from "@/lib/api";
import { Button, Card, CardTitle, cx, Pill } from "./ui";
import { useSessionUser } from "./SessionGuard";

type Point = { name: string; latitude: number; longitude: number };
type Host = {
  id: number;
  name: string;
  city: string;
  latitude: number | null;
  longitude: number | null;
  distance_km: number | null;
  current_placement?: boolean;
  eligible_opportunities?: {
    id: number;
    title: string;
    available_slots: number;
    moa_expires_on: string;
  }[];
};
type Data = { student: Point | null; hosts: Host[] };
function band(distance: number | null) {
  return distance === null
    ? "Unknown"
    : distance < 5
      ? "Under 5 km"
      : distance <= 10
        ? "5–10 km"
        : "Over 10 km";
}
function color(distance: number | null) {
  return distance === null
    ? "#64748b"
    : distance < 5
      ? "#008260"
      : distance <= 10
        ? "#b7790b"
        : "#701724";
}

export function AccessibilityMap({ note }: { note?: string }) {
  const user = useSessionUser();
  const [data, setData] = useState<Data | null>(null);
  const [selected, setSelected] = useState<number | null>(null);
  const [error, setError] = useState("");
  const [tileError, setTileError] = useState(false);
  const [revision, setRevision] = useState(0);
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState("All distances");
  const [sort, setSort] = useState("Nearest");
  const [ready, setReady] = useState(false);
  const container = useRef<HTMLDivElement>(null);
  const map = useRef<LeafletMap | null>(null);
  const markers = useRef<Map<number, Marker>>(new Map());
  const visible = (data?.hosts ?? [])
    .filter(
      (host) =>
        (host.name + " " + host.city).toLowerCase().includes(search.trim().toLowerCase()) &&
        (filter === "All distances" || band(host.distance_km) === filter),
    )
    .sort(
      (a, b) =>
        (sort === "Nearest" ? (a.distance_km ?? Infinity) - (b.distance_km ?? Infinity) : 0) ||
        a.name.localeCompare(b.name),
    );
  const active = visible.find((host) => host.id === selected) ?? visible[0];
  useEffect(() => {
    let mounted = true;
    setError("");
    api<Data>("/map")
      .then((value) => {
        if (mounted) {
          setData(value);
          setSelected(value.hosts[0]?.id ?? null);
        }
      })
      .catch((cause: Error) => {
        if (mounted) setError(cause.message);
      });
    return () => {
      mounted = false;
    };
  }, [revision]);
  useEffect(() => {
    if (!data || !container.current) return;
    let mounted = true;
    const hostMarkers = markers.current;
    setReady(false);
    setTileError(false);
    void import("leaflet")
      .then((L) => {
        if (!mounted || !container.current) return;
        const instance = L.map(container.current).setView([7.45, 125.8], 9);
        map.current = instance;
        L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
          maxZoom: 19,
          attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        })
          .on("tileerror", () => {
            if (mounted) setTileError(true);
          })
          .addTo(instance);
        const points: [number, number][] = [];
        function add(point: Point, host?: Host) {
          const coords: [number, number] = [point.latitude, point.longitude];
          points.push(coords);
          const label = document.createElement("span");
          label.textContent = host ? host.name : "Your saved location";
          const marker = L.marker(coords, {
            title: label.textContent,
            icon: L.divIcon({
              className: "",
              html:
                '<span style="display:block;width:20px;height:20px;border:3px solid white;border-radius:50%;background:' +
                (host ? color(host.distance_km) : "#172554") +
                ';box-shadow:0 1px 4px #555"></span>',
              iconSize: [20, 20],
              iconAnchor: [10, 10],
            }),
          })
            .addTo(instance)
            .bindPopup(label);
          if (host) {
            markers.current.set(host.id, marker);
            marker.on("click", () => setSelected(host.id));
          }
        }
        if (data.student) add(data.student);
        data.hosts.forEach((host) => {
          if (host.latitude !== null && host.longitude !== null)
            add({ ...host, latitude: host.latitude, longitude: host.longitude }, host);
        });
        if (points.length)
          instance.fitBounds(L.latLngBounds(points), { padding: [35, 35], maxZoom: 14 });
        setReady(true);
      })
      .catch((cause: Error) => {
        if (mounted) setError(cause.message);
      });
    return () => {
      mounted = false;
      map.current?.remove();
      map.current = null;
      hostMarkers.clear();
    };
  }, [data]);
  const visibleIds = visible.map((host) => host.id).join(",");
  useEffect(() => {
    if (!map.current || !ready) return;
    const ids = new Set(visibleIds.split(",").map(Number));
    markers.current.forEach((marker, id) => {
      if (ids.has(id)) marker.addTo(map.current!);
      else marker.remove();
    });
  }, [visibleIds, ready]);
  function choose(host: Host) {
    setSelected(host.id);
    if (host.latitude !== null && host.longitude !== null) {
      map.current?.setView([host.latitude, host.longitude], 14);
      markers.current.get(host.id)?.openPopup();
    }
  }
  function fit() {
    const points: [number, number][] = visible
      .filter((host) => host.latitude !== null && host.longitude !== null)
      .map((host) => [host.latitude!, host.longitude!]);
    if (data?.student) points.push([data.student.latitude, data.student.longitude]);
    if (points.length) map.current?.fitBounds(points, { padding: [35, 35], maxZoom: 14 });
  }
  return (
    <div className="space-y-5">
      {error && (
        <p role="alert" className="rounded-lg bg-brand-soft p-4 text-sm">
          {error}
          <button className="ml-3 underline" onClick={() => setRevision((value) => value + 1)}>
            Retry
          </button>
        </p>
      )}
      {!data && !error && <p role="status">Loading saved locations…</p>}
      {data && !data.student && user.role === "student" && (
        <p className="rounded-lg border bg-card p-4 text-sm text-muted-foreground">
          Save your location to calculate student-to-host distances.{" "}
          <a href="/student/profile" className="font-semibold text-brand underline">
            Update My profile
          </a>
        </p>
      )}
      <div className="flex flex-wrap items-end gap-3">
        <label className="min-w-0 flex-1 basis-60 text-sm font-medium">
          Search establishments
          <input
            type="search"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Host name or city"
            className="mt-2 block w-full rounded-lg border bg-card px-3 py-2.5"
          />
        </label>
        <label className="text-sm font-medium">
          Distance
          <select
            value={filter}
            onChange={(event) => setFilter(event.target.value)}
            className="mt-2 block rounded-lg border bg-card px-3 py-2.5"
          >
            {["All distances", "Under 5 km", "5–10 km", "Over 10 km", "Unknown"].map((value) => (
              <option key={value}>{value}</option>
            ))}
          </select>
        </label>
        <label className="text-sm font-medium">
          Sort by
          <select
            value={sort}
            onChange={(event) => setSort(event.target.value)}
            className="mt-2 block rounded-lg border bg-card px-3 py-2.5"
          >
            <option>Nearest</option>
            <option>Name</option>
          </select>
        </label>
      </div>
      <div className="grid items-start gap-6 xl:grid-cols-[1.6fr_1fr]">
        <Card className="overflow-hidden p-0">
          <div className="space-y-3 px-6 py-5">
            <h2 className="text-lg font-semibold">
              Geographic distribution of host establishments
            </h2>
            <p className="text-sm text-muted-foreground">
              {note ??
                "Marker colors show straight-line distance from your saved location. Unknown distances are gray."}
            </p>
            <div className="flex flex-wrap gap-2">
              <Button
                variant="outline"
                disabled={
                  !ready || (!data?.student && !visible.some((host) => host.latitude !== null))
                }
                onClick={fit}
              >
                Show all visible locations
              </Button>
              {user.role === "student" && (
                <Button
                  variant="outline"
                  disabled={!ready || !data?.student}
                  onClick={() => {
                    if (data?.student)
                      map.current?.setView([data.student.latitude, data.student.longitude], 14);
                  }}
                >
                  My saved location
                </Button>
              )}
            </div>
            {tileError && (
              <p role="status" className="text-xs text-muted-foreground">
                Base-map tiles are unavailable. Saved markers and host details still work.
              </p>
            )}
          </div>
          <div
            ref={container}
            aria-label="InternMatch location map"
            className="relative z-0 h-[400px] bg-slate-100 sm:h-[520px]"
          />
          <div className="flex flex-wrap gap-x-5 gap-y-2 border-t p-4 text-xs text-muted-foreground">
            {["Under 5 km", "5–10 km", "Over 10 km", "Unknown"].map((label, index) => (
              <span key={label} className="flex items-center gap-2">
                <span
                  className="h-3 w-3 rounded-full"
                  style={{ background: color([2, 7, 12, null][index] ?? null) }}
                />
                {label}
              </span>
            ))}
            <span className="flex items-center gap-2">
              <span className="h-3 w-3 rounded-full bg-blue-950" />
              Saved student location
            </span>
          </div>
        </Card>
        <div className="space-y-5">
          <Card className="p-6">
            <CardTitle>{active?.name ?? "Host details"}</CardTitle>
            {active ? (
              <div className="space-y-4 text-sm">
                {active.current_placement && <Pill tone="info">Your placement host</Pill>}
                <Line label="Location" value={active.city || "Not recorded"} />
                <Line
                  label="Student-to-host distance"
                  value={
                    active.distance_km === null
                      ? "Coordinates unavailable"
                      : active.distance_km.toFixed(2) + " km straight-line"
                  }
                />
                <Line label="Distance band" value={band(active.distance_km)} />
                {(active.eligible_opportunities ?? []).map((opportunity) => (
                  <section key={opportunity.id} className="rounded-lg border p-3">
                    <h3 className="font-semibold">{opportunity.title}</h3>
                    <p className="mt-2 text-muted-foreground">
                      {opportunity.available_slots} program slots available
                    </p>
                    <p className="mt-1 text-muted-foreground">
                      MOA valid through {opportunity.moa_expires_on}
                    </p>
                  </section>
                ))}
                {user.role === "student" && !active.eligible_opportunities?.length && (
                  <p className="text-muted-foreground">
                    No currently eligible opportunities at this host. A current placement host
                    remains visible for reference.
                  </p>
                )}
                {user.role === "student" && (
                  <a
                    href="/student/opportunities"
                    className="inline-block font-semibold text-brand underline"
                  >
                    Browse opportunities
                  </a>
                )}
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">
                {data?.hosts.length
                  ? "No establishments match your filters."
                  : "No host records are available within your access scope."}
              </p>
            )}
          </Card>
          <Card>
            <CardTitle>All establishments</CardTitle>
            <p role="status" className="mb-3 text-xs text-muted-foreground">
              {visible.length} of {data?.hosts.length ?? 0} hosts shown
            </p>
            <div className="max-h-80 space-y-1 overflow-y-auto">
              {visible.map((host) => (
                <button
                  key={host.id}
                  type="button"
                  aria-pressed={active?.id === host.id}
                  onClick={() => choose(host)}
                  className={cx(
                    "flex w-full items-center justify-between gap-3 rounded-lg px-3 py-3 text-left text-sm",
                    active?.id === host.id
                      ? "bg-brand-soft font-semibold text-brand"
                      : "hover:bg-muted",
                  )}
                >
                  <span>{host.name}</span>
                  <span className="shrink-0 text-xs text-muted-foreground">
                    {host.distance_km === null ? "Unknown" : host.distance_km.toFixed(2) + " km"}
                  </span>
                </button>
              ))}
            </div>
            {(search || filter !== "All distances") && (
              <Button
                className="mt-4"
                variant="outline"
                onClick={() => {
                  setSearch("");
                  setFilter("All distances");
                }}
              >
                Clear filters
              </Button>
            )}
          </Card>
        </div>
      </div>
    </div>
  );
}
function Line({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4 border-b pb-3">
      <span className="text-muted-foreground">{label}</span>
      <span className="text-right font-medium">{value}</span>
    </div>
  );
}
