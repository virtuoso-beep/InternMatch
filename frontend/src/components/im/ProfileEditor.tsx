import { useEffect, useRef, useState } from "react";
import { Button, Card, CardTitle, PageHeader } from "@/components/im/ui";
import { api, mutate, upload } from "@/lib/api";
import { ROLES, type RoleKey } from "@/lib/internmatch";
import { useSessionUser, useUpdateSessionUser } from "./SessionGuard";

type Profile = {
  id: number;
  name: string;
  email: string;
  role: RoleKey;
  avatar_url?: string | null;
  contact_number: string | null;
  address: string | null;
  bio: string | null;
  latitude: string | null;
  longitude: string | null;
  preferred_internship_location: string | null;
  knowledge_areas: string | null;
  notify_email: boolean;
  notify_digest: boolean;
  student_number: string | null;
  enrollments: Array<{
    id: number;
    program: string;
    term: string;
    year_level: number | null;
    status: string;
  }>;
};
type Form = {
  name: string;
  contact_number: string;
  address: string;
  bio: string;
  preferred_internship_location: string;
  knowledge_areas: string;
  latitude: string;
  longitude: string;
  notify_email: boolean;
  notify_digest: boolean;
};
const toForm = (profile: Profile): Form => ({
  name: profile.name,
  contact_number: profile.contact_number ?? "",
  address: profile.address ?? "",
  bio: profile.bio ?? "",
  latitude: profile.latitude ?? "",
  longitude: profile.longitude ?? "",
  preferred_internship_location: profile.preferred_internship_location ?? "",
  knowledge_areas: profile.knowledge_areas ?? "",
  notify_email: profile.notify_email,
  notify_digest: profile.notify_digest,
});
const inputClass =
  "mt-1 w-full rounded-lg border border-border bg-background px-3 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand/25";
const labelClass = "block text-xs font-semibold uppercase tracking-wide text-brand";

/** Compute profile completeness (0–100) from filled fields. */
function profileCompleteness(profile: Profile, role: RoleKey): number {
  const checks: boolean[] = [
    !!profile.name,
    !!profile.contact_number,
    !!profile.address,
    !!profile.bio,
    !!profile.avatar_url,
  ];
  if (role === "student") {
    checks.push(!!profile.preferred_internship_location, !!profile.knowledge_areas);
    checks.push(profile.enrollments.length > 0);
  }
  checks.push(!!profile.latitude && !!profile.longitude);
  const filled = checks.filter(Boolean).length;
  return Math.round((filled / checks.length) * 100);
}

/** Initials from a name (up to 2 letters). */
function initials(name: string) {
  return name
    .split(" ")
    .map((p) => p[0])
    .slice(0, 2)
    .join("");
}

export function ProfileEditor({ role }: { role: RoleKey }) {
  const session = useSessionUser();
  const updateSession = useUpdateSessionUser();
  const [profile, setProfile] = useState<Profile | null>(null);
  const [form, setForm] = useState<Form | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [retry, setRetry] = useState(0);
  const [photoBusy, setPhotoBusy] = useState(false);
  const [locating, setLocating] = useState(false);
  const [dragOver, setDragOver] = useState(false);
  const photoInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    let active = true;
    setError(null);
    api<{ data: Profile }>("/profile")
      .then(({ data }) => {
        if (active) {
          setProfile(data);
          setForm(toForm(data));
        }
      })
      .catch((cause: unknown) => {
        if (active)
          setError(cause instanceof Error ? cause.message : "Unable to load your profile.");
      });
    return () => {
      active = false;
    };
  }, [retry]);

  if (!profile || !form)
    return (
      <section aria-live="polite">
        <PageHeader title="My profile" subtitle={ROLES[role].title} />
        <p>{error ?? "Loading your profile…"}</p>
        {error && (
          <button className="mt-3 underline" onClick={() => setRetry((value) => value + 1)}>
            Try again
          </button>
        )}
      </section>
    );

  const dirty = JSON.stringify(form) !== JSON.stringify(toForm(profile));
  const set = <K extends keyof Form>(key: K, value: Form[K]) => {
    setForm((previous) => (previous ? { ...previous, [key]: value } : previous));
    setMessage(null);
  };

  /** Upload a photo file with validation. */
  const handlePhotoFile = async (file: File) => {
    setError(null);
    if (
      !["image/jpeg", "image/png"].includes(file.type) ||
      file.size > 2 * 1024 * 1024 ||
      file.size === 0
    ) {
      setError("Choose a JPG or PNG photo up to 2 MB.");
      return;
    }
    setPhotoBusy(true);
    try {
      const payload = new FormData();
      payload.set("photo", file);
      const { data } = await upload<{ data: Profile }>("/profile/photo", payload);
      setProfile((current) => current && { ...current, avatar_url: data.avatar_url ?? null });
      setMessage("Profile photo saved.");
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Unable to upload photo.");
    } finally {
      setPhotoBusy(false);
    }
  };

  const field = (
    key:
      | "name"
      | "contact_number"
      | "address"
      | "preferred_internship_location"
      | "latitude"
      | "longitude",
    label: string,
  ) => (
    <label className="block">
      <span className={labelClass}>{label}</span>
      <input
        className={inputClass}
        value={form[key]}
        required={key === "name"}
        type={key === "latitude" || key === "longitude" ? "number" : "text"}
        step="any"
        min={key === "latitude" ? -90 : key === "longitude" ? -180 : undefined}
        max={key === "latitude" ? 90 : key === "longitude" ? 180 : undefined}
        maxLength={key === "contact_number" ? 40 : 255}
        onChange={(event) => set(key, event.target.value)}
      />
    </label>
  );

  /** Read-only field matching the reference look. */
  const readOnlyField = (label: string, value: string) => (
    <label className="block">
      <span className={labelClass}>{label}</span>
      <input
        className={inputClass + " cursor-default bg-muted/40"}
        value={value}
        readOnly
        tabIndex={-1}
      />
    </label>
  );

  const completeness = profileCompleteness(profile, role);

  /** Primary enrollment for student display. */
  const primaryEnrollment = profile.enrollments[0];
  const programAndYear = primaryEnrollment
    ? `${primaryEnrollment.program}${primaryEnrollment.year_level ? ` · ${primaryEnrollment.year_level}${["st", "nd", "rd"][primaryEnrollment.year_level - 1] ?? "th"} Year` : ""}`
    : null;

  return (
    <>
      <PageHeader
        title="My profile"
        subtitle="Update your account details — these are used across InternMatch."
        action={
          <span
            role="status"
            className={`rounded-lg px-4 py-2 text-sm font-semibold ${
              dirty ? "bg-amber-50 text-amber-700" : "bg-brand px-5 text-brand-foreground"
            }`}
          >
            {saving ? "Saving…" : dirty ? "Unsaved changes" : "Saved"}
          </span>
        }
      />
      {error && (
        <p role="alert" className="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-800">
          {error}
        </p>
      )}
      {message && (
        <p role="status" className="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800">
          {message}
        </p>
      )}
      <form
        className="grid gap-5 lg:grid-cols-3"
        onSubmit={async (event) => {
          event.preventDefault();
          if (saving || locating || photoBusy || !dirty) return;
          if ((form.latitude === "") !== (form.longitude === "")) {
            setError("Provide both coordinates, or clear both together.");
            return;
          }
          setSaving(true);
          setError(null);
          setMessage(null);
          try {
            const { data } = await mutate<{ data: Profile }>(
              "/profile",
              {
                ...form,
                contact_number: form.contact_number.trim() || null,
                address: form.address.trim() || null,
                bio: form.bio.trim() || null,
                latitude: form.latitude === "" ? null : Number(form.latitude),
                longitude: form.longitude === "" ? null : Number(form.longitude),
              },
              "PATCH",
            );
            setProfile(data);
            setForm(toForm(data));
            updateSession({ ...session, name: data.name });
            setMessage("Profile saved. Your changes are stored in InternMatch.");
          } catch (cause) {
            setError(cause instanceof Error ? cause.message : "Unable to save. Please try again.");
          } finally {
            setSaving(false);
          }
        }}
      >
        {/* ── Left column: Account details ── */}
        <Card className="lg:col-span-2">
          <CardTitle>Account details</CardTitle>
          <fieldset disabled={saving} className="mt-4 grid gap-5 sm:grid-cols-2">
            {field("name", "Full name")}
            {readOnlyField("Institutional email", profile.email)}
            {field("contact_number", "Contact number")}
            {role === "student" && programAndYear
              ? readOnlyField("Program & year", programAndYear)
              : null}
            {(role === "coordinator" || role === "supervisor") &&
              field("address", "Unit / organization")}
            {role === "student" && field("address", "Location")}

            {role === "student" && (
              <>{field("preferred_internship_location", "Preferred internship location")}</>
            )}

            <label className="block sm:col-span-2">
              <span className={labelClass}>About</span>
              <textarea
                aria-label="About"
                className={inputClass}
                rows={4}
                maxLength={2000}
                value={form.bio}
                onChange={(event) => set("bio", event.target.value)}
              />
            </label>

            {role === "student" && (
              <label className="block sm:col-span-2">
                <span className={labelClass}>Knowledge / areas of expertise</span>
                <textarea
                  aria-label="Knowledge / areas of expertise"
                  className={inputClass}
                  rows={3}
                  maxLength={2000}
                  value={form.knowledge_areas}
                  onChange={(event) => set("knowledge_areas", event.target.value)}
                />
              </label>
            )}

            {/* Coordinates – collapsed under a summary for cleaner look */}
            <details className="sm:col-span-2">
              <summary className="cursor-pointer text-sm font-medium text-brand hover:underline">
                Location coordinates (optional)
              </summary>
              <div className="mt-3 grid gap-4 sm:grid-cols-2">
                {field("latitude", "Latitude")}
                {field("longitude", "Longitude")}
              </div>
              <div className="mt-3 flex flex-wrap gap-3">
                <Button
                  variant="outline"
                  disabled={locating}
                  onClick={() => {
                    if (!navigator.geolocation) {
                      setError(
                        "This browser does not support location. Enter both coordinates manually.",
                      );
                      return;
                    }
                    setLocating(true);
                    setError(null);
                    navigator.geolocation.getCurrentPosition(
                      (position) => {
                        setForm(
                          (current) =>
                            current && {
                              ...current,
                              latitude: position.coords.latitude.toFixed(7),
                              longitude: position.coords.longitude.toFixed(7),
                            },
                        );
                        setMessage("Location filled in. Review it, then save your changes.");
                        setLocating(false);
                      },
                      () => {
                        setError(
                          "Location was unavailable or permission was denied. You can enter both coordinates manually.",
                        );
                        setLocating(false);
                      },
                      { timeout: 10000, maximumAge: 0 },
                    );
                  }}
                >
                  {locating ? "Locating…" : "Use my current location"}
                </Button>
                <Button
                  variant="outline"
                  disabled={!form.latitude && !form.longitude}
                  onClick={() => {
                    setForm((current) => current && { ...current, latitude: "", longitude: "" });
                    setMessage(null);
                  }}
                >
                  Clear coordinates
                </Button>
              </div>
              <p className="mt-2 text-xs text-muted-foreground">
                Coordinates support straight-line distance calculations, not continuous tracking.
              </p>
            </details>
          </fieldset>
          <div className="mt-5 flex gap-3 border-t border-border pt-5">
            <button
              type="submit"
              disabled={!dirty || saving || locating || photoBusy}
              className="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-brand-foreground shadow-sm transition-opacity disabled:opacity-50"
            >
              {saving ? "Saving…" : "Save changes"}
            </button>
            <button
              type="button"
              disabled={!dirty || saving || locating || photoBusy}
              className="rounded-lg border border-border px-5 py-2.5 text-sm font-medium transition-opacity disabled:opacity-50"
              onClick={() => {
                setForm(toForm(profile));
                setError(null);
                setMessage(null);
              }}
            >
              Cancel
            </button>
          </div>
        </Card>

        {/* ── Right column: Photo, Role, Notifications ── */}
        <div className="space-y-5">
          {/* Profile photo */}
          <Card>
            <CardTitle>Profile photo</CardTitle>
            <div
              className={`mt-3 flex flex-col items-center gap-4 rounded-lg border-2 border-dashed p-4 transition-colors ${
                dragOver ? "border-brand bg-brand-soft/30" : "border-border"
              }`}
              onDragOver={(e) => {
                e.preventDefault();
                setDragOver(true);
              }}
              onDragLeave={() => setDragOver(false)}
              onDrop={async (e) => {
                e.preventDefault();
                setDragOver(false);
                const file = e.dataTransfer.files[0];
                if (file) await handlePhotoFile(file);
              }}
            >
              {profile.avatar_url ? (
                <img
                  src={profile.avatar_url}
                  alt="Your profile"
                  className="h-20 w-20 rounded-full object-cover ring-4 ring-brand/10"
                />
              ) : (
                <span className="flex h-20 w-20 items-center justify-center rounded-full bg-brand text-xl font-bold text-white ring-4 ring-brand/10">
                  {initials(profile.name)}
                </span>
              )}
              <div className="text-center">
                <Button
                  variant="outline"
                  disabled={photoBusy || saving}
                  onClick={() => photoInputRef.current?.click()}
                >
                  {photoBusy ? "Uploading…" : "Upload photo"}
                </Button>
                <input
                  ref={photoInputRef}
                  type="file"
                  accept="image/jpeg,image/png"
                  className="hidden"
                  onChange={async (event) => {
                    const input = event.currentTarget;
                    const file = input.files?.[0];
                    if (file) await handlePhotoFile(file);
                    input.value = "";
                  }}
                />
                <p className="mt-2 text-xs text-muted-foreground">
                  JPG or <span className="text-brand">PNG</span>, up to 2 MB. Drag and drop works
                  too.
                </p>
              </div>
            </div>
          </Card>

          {/* Role card */}
          <Card>
            <CardTitle>Role</CardTitle>
            <div className="mt-3 flex items-center gap-3">
              {profile.avatar_url ? (
                <img
                  src={profile.avatar_url}
                  alt=""
                  className="h-11 w-11 rounded-full object-cover"
                />
              ) : (
                <span className="flex h-11 w-11 items-center justify-center rounded-full bg-brand text-sm font-bold text-white">
                  {initials(profile.name)}
                </span>
              )}
              <div className="min-w-0">
                <p className="truncate text-sm font-semibold">{ROLES[role].title}</p>
                <p className="truncate text-xs text-muted-foreground">{profile.email}</p>
              </div>
            </div>
            <div className="mt-4">
              <p className="text-xs text-muted-foreground">Profile completeness</p>
              <div className="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-muted">
                <div
                  className="h-full rounded-full bg-emerald-600 transition-all duration-500"
                  style={{ width: `${completeness}%` }}
                />
              </div>
              <p className="mt-1 text-right text-xs font-medium text-emerald-700">
                {completeness}%
              </p>
            </div>
            {profile.student_number && (
              <p className="mt-3 text-xs text-muted-foreground">
                Student number: {profile.student_number}
              </p>
            )}
            {profile.enrollments.length > 1 && (
              <ul className="mt-3 space-y-2">
                {profile.enrollments.slice(1).map((enrollment) => (
                  <li key={enrollment.id} className="rounded-md border p-2.5 text-xs">
                    <p className="font-semibold">{enrollment.program}</p>
                    <p>{enrollment.term}</p>
                    {enrollment.year_level !== null && <p>Year {enrollment.year_level}</p>}
                    <p className="text-muted-foreground">{enrollment.status}</p>
                  </li>
                ))}
              </ul>
            )}
            <p className="mt-3 text-xs text-muted-foreground">
              Account email, role and academic records are managed by authorized staff.
            </p>
          </Card>

          {/* Notifications preferences */}
          <Card>
            <CardTitle>Notifications</CardTitle>
            <fieldset disabled={saving} className="mt-3 space-y-3">
              <label className="flex items-start gap-3 text-sm">
                <input
                  type="checkbox"
                  className="mt-0.5 h-4 w-4 rounded border-border text-brand accent-brand"
                  checked={form.notify_email}
                  onChange={(event) => set("notify_email", event.target.checked)}
                />
                Email me about placement updates
              </label>
              <label className="flex items-start gap-3 text-sm">
                <input
                  type="checkbox"
                  className="mt-0.5 h-4 w-4 rounded border-border text-brand accent-brand"
                  checked={form.notify_digest}
                  onChange={(event) => set("notify_digest", event.target.checked)}
                />
                Send a weekly digest summary
              </label>
            </fieldset>
            <p className="mt-3 text-xs text-muted-foreground">
              Preferences are saved with your profile. Email delivery is still under development.
            </p>
          </Card>
        </div>
      </form>
    </>
  );
}
