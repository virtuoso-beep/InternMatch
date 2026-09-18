import { useEffect, useState } from "react";
import { Card, CardTitle, PageHeader } from "@/components/im/ui";
import { api, mutate } from "@/lib/api";
import { ROLES, type RoleKey } from "@/lib/internmatch";
import { useSessionUser, useUpdateSessionUser } from "./SessionGuard";

type Profile = {
  id: number; name: string; email: string; role: RoleKey;
  contact_number: string | null; address: string | null; bio: string | null;
  latitude: string | null; longitude: string | null;
  notify_email: boolean; notify_digest: boolean; student_number: string | null;
  enrollments: Array<{ id: number; program: string; term: string; year_level: number | null; status: string }>;
};
type Form = { name: string; contact_number: string; address: string; bio: string; latitude: string; longitude: string; notify_email: boolean; notify_digest: boolean };
const toForm = (profile: Profile): Form => ({
  name: profile.name, contact_number: profile.contact_number ?? "", address: profile.address ?? "",
  bio: profile.bio ?? "", latitude: profile.latitude ?? "", longitude: profile.longitude ?? "",
  notify_email: profile.notify_email, notify_digest: profile.notify_digest,
});
const inputClass = "mt-1 w-full rounded-md border border-border bg-background px-3 py-2 text-sm focus:border-brand focus:ring-2 focus:ring-brand/25";

export function ProfileEditor({ role }: { role: RoleKey }) {
  const session = useSessionUser();
  const updateSession = useUpdateSessionUser();
  const [profile, setProfile] = useState<Profile | null>(null);
  const [form, setForm] = useState<Form | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [retry, setRetry] = useState(0);

  useEffect(() => {
    let active = true;
    setError(null);
    api<{ data: Profile }>("/profile").then(({ data }) => {
      if (active) { setProfile(data); setForm(toForm(data)); }
    }).catch((cause: unknown) => {
      if (active) setError(cause instanceof Error ? cause.message : "Unable to load your profile.");
    });
    return () => { active = false; };
  }, [retry]);

  if (!profile || !form) return <section aria-live="polite">
    <PageHeader title="My profile" subtitle={ROLES[role].title} />
    <p>{error ?? "Loading your profile…"}</p>
    {error && <button className="mt-3 underline" onClick={() => setRetry((value) => value + 1)}>Try again</button>}
  </section>;

  const dirty = JSON.stringify(form) !== JSON.stringify(toForm(profile));
  const set = <K extends keyof Form>(key: K, value: Form[K]) => {
    setForm((previous) => previous ? { ...previous, [key]: value } : previous);
    setMessage(null);
  };
  const field = (key: "name" | "contact_number" | "address" | "latitude" | "longitude", label: string) => <label className="block text-sm font-medium">
    {label}<input className={inputClass} value={form[key]} required={key === "name"}
      type={key === "latitude" || key === "longitude" ? "number" : "text"} step="any"
      min={key === "latitude" ? -90 : key === "longitude" ? -180 : undefined}
      max={key === "latitude" ? 90 : key === "longitude" ? 180 : undefined}
      maxLength={key === "contact_number" ? 40 : 255}
      onChange={(event) => set(key, event.target.value)} />
  </label>;

  return <>
    <PageHeader title="My profile" subtitle="Your saved personal and locality information." />
    {error && <p role="alert" className="mb-4 rounded-md bg-brand-soft p-3 text-brand">{error}</p>}
    {message && <p role="status" className="mb-4 rounded-md bg-green-50 p-3 text-green-900">{message}</p>}
    <form className="grid gap-5 lg:grid-cols-3" onSubmit={async (event) => {
      event.preventDefault();
      if (saving || !dirty) return;
      if ((form.latitude === "") !== (form.longitude === "")) {
        setError("Provide both coordinates, or clear both together."); return;
      }
      setSaving(true); setError(null); setMessage(null);
      try {
        const { data } = await mutate<{ data: Profile }>("/profile", {
          ...form, contact_number: form.contact_number.trim() || null, address: form.address.trim() || null,
          bio: form.bio.trim() || null, latitude: form.latitude === "" ? null : Number(form.latitude),
          longitude: form.longitude === "" ? null : Number(form.longitude),
        }, "PATCH");
        setProfile(data); setForm(toForm(data)); updateSession({ ...session, name: data.name });
        setMessage("Profile saved. Your changes are stored in InternMatch.");
      } catch (cause) {
        setError(cause instanceof Error ? cause.message : "Unable to save. Please try again.");
      } finally { setSaving(false); }
    }}>
      <Card className="lg:col-span-2">
        <CardTitle>Personal details</CardTitle>
        <fieldset disabled={saving} className="grid gap-4 sm:grid-cols-2">
          {field("name", "Full name")}
          <label className="block text-sm font-medium">Account email<input className={inputClass} value={profile.email} readOnly /></label>
          {field("contact_number", "Contact number")}
          {field("address", "Locality / address")}
          {field("latitude", "Latitude (optional)")}
          {field("longitude", "Longitude (optional)")}
          <p className="text-xs text-muted-foreground sm:col-span-2">Enter your recorded location, or leave both coordinates blank. These support straight-line distance calculations; they are not continuous tracking.</p>
          <label className="block text-sm font-medium sm:col-span-2">About<textarea className={inputClass} rows={4} maxLength={2000} value={form.bio} onChange={(event) => set("bio", event.target.value)} /></label>
          <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.notify_email} onChange={(event) => set("notify_email", event.target.checked)} />Email updates preference</label>
          <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.notify_digest} onChange={(event) => set("notify_digest", event.target.checked)} />Weekly digest preference</label>
          <p className="text-xs text-muted-foreground sm:col-span-2">Preferences are saved. Email notification delivery is still under development.</p>
        </fieldset>
        <div className="mt-5 flex gap-3">
          <button type="submit" disabled={!dirty || saving} className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-brand-foreground disabled:opacity-50">{saving ? "Saving…" : "Save changes"}</button>
          <button type="button" disabled={!dirty || saving} className="rounded-md border px-4 py-2 text-sm disabled:opacity-50" onClick={() => { setForm(toForm(profile)); setError(null); setMessage(null); }}>Cancel</button>
        </div>
      </Card>
      <Card>
        <CardTitle>Account and academic information</CardTitle>
        <p className="font-medium">{ROLES[role].title}</p>
        {profile.student_number && <p className="mt-2 text-sm">Student number: {profile.student_number}</p>}
        {profile.enrollments.length > 0 ? <ul className="mt-4 space-y-3">
          {profile.enrollments.map((enrollment) => <li key={enrollment.id} className="rounded-md border p-3 text-sm">
            <p className="font-semibold">{enrollment.program}</p><p>{enrollment.term}</p>
            {enrollment.year_level !== null && <p>Year {enrollment.year_level}</p>}<p>{enrollment.status}</p>
          </li>)}
        </ul> : role === "student" && <p className="mt-4 text-sm text-muted-foreground">No enrollment is linked to your account yet.</p>}
        <p className="mt-4 text-xs text-muted-foreground">Account email, role and academic records are managed by authorized staff.</p>
      </Card>
    </form>
  </>;
}
