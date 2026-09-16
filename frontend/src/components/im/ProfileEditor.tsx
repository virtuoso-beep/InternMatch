import { useRef, useState } from "react";
import { Button, Card, CardTitle, Meter, PageHeader } from "@/components/im/ui";
import { ROLES, type RoleKey } from "@/lib/internmatch";

type Profile = {
  name: string;
  email: string;
  contact: string;
  unit: string;
  location: string;
  bio: string;
  notifyEmail: boolean;
  notifyDigest: boolean;
  avatar: string;
};

const DEFAULTS: Record<RoleKey, Omit<Profile, "avatar">> = {
  student: {
    name: "Trisha Mae Talamillo",
    email: "tmtalamillo@umindanao.edu.ph",
    contact: "+63 917 555 0142",
    unit: "BS Information Technology · 4th Year",
    location: "Magugpo East, Tagum City",
    bio: "Aspiring web developer focused on Laravel and React projects.",
    notifyEmail: true,
    notifyDigest: false,
  },
  coordinator: {
    name: "Angeli Sophia Pancho",
    email: "aspancho@umindanao.edu.ph",
    contact: "+63 917 555 0188",
    unit: "Department of Computing Education",
    location: "UM Tagum College",
    bio: "Practicum coordinator overseeing placement approvals for 160 students.",
    notifyEmail: true,
    notifyDigest: true,
  },
  supervisor: {
    name: "Rico Fernandez",
    email: "rfernandez@datacore.ph",
    contact: "+63 918 220 4471",
    unit: "DataCore Solutions Inc.",
    location: "Tagum City",
    bio: "Supervises web development interns and certifies weekly hours.",
    notifyEmail: true,
    notifyDigest: false,
  },
  dean: {
    name: "Dr. R. Villanueva",
    email: "rvillanueva@umindanao.edu.ph",
    contact: "+63 917 555 0100",
    unit: "College of Computing Education",
    location: "UM Tagum College",
    bio: "Program chair monitoring placement equity and accreditation readiness.",
    notifyEmail: false,
    notifyDigest: true,
  },
  admin: {
    name: "System Administrator",
    email: "itadmin@umindanao.edu.ph",
    contact: "+63 917 555 0000",
    unit: "MIS Office",
    location: "UM Tagum College",
    bio: "Maintains accounts, permissions, backups, and audit records.",
    notifyEmail: true,
    notifyDigest: true,
  },
};

function Input({
  label,
  value,
  onChange,
  type = "text",
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  type?: string;
}) {
  return (
    <label className="block">
      <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted-foreground uppercase">{label}</span>
      <input
        type={type}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground outline-none focus:border-brand focus:ring-2 focus:ring-brand/25"
      />
    </label>
  );
}

export function ProfileEditor({ role }: { role: RoleKey }) {
  const cfg = ROLES[role];
  const initial: Profile = { ...DEFAULTS[role], avatar: "" };
  const fileRef = useRef<HTMLInputElement>(null);
  const [form, setForm] = useState<Profile>(initial);
  const [saved, setSaved] = useState<Profile>(initial);
  const [status, setStatus] = useState<"idle" | "saved">("idle");
  const [imgError, setImgError] = useState<string | null>(null);

  const pickImage = (file?: File | null) => {
    if (!file) return;
    if (!file.type.startsWith("image/")) {
      setImgError("Please choose an image file (JPG, PNG).");
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      setImgError("Image must be 2 MB or smaller.");
      return;
    }
    const reader = new FileReader();
    reader.onload = () => {
      setImgError(null);
      set("avatar", String(reader.result));
    };
    reader.readAsDataURL(file);
  };

  const dirty = JSON.stringify(form) !== JSON.stringify(saved);
  const set = <K extends keyof Profile>(key: K, value: Profile[K]) => {
    setForm((f) => ({ ...f, [key]: value }));
    setStatus("idle");
  };

  const filled = [form.name, form.email, form.contact, form.unit, form.location, form.bio].filter(
    (v) => v.trim().length > 0,
  ).length;
  const completeness = Math.round((filled / 6) * 100);

  return (
    <>
      <PageHeader
        title="My profile"
        subtitle="Update your account details — these are used across InternMatch."
        action={
          <div className="flex items-center gap-2">
            {dirty && (
              <Button
                variant="outline"
                onClick={() => {
                  setForm(saved);
                  setStatus("idle");
                }}
              >
                Cancel
              </Button>
            )}
            <Button
              onClick={() => {
                setSaved(form);
                setStatus("saved");
              }}
            >
              {dirty ? "Save changes" : "Saved"}
            </Button>
          </div>
        }
      />

      {status === "saved" && (
        <div className="mb-5 rounded-md border border-border bg-brand-soft px-4 py-3 text-sm font-medium text-brand">
          Profile updated successfully.
        </div>
      )}

      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardTitle>Account details</CardTitle>
          <div className="grid gap-4 sm:grid-cols-2">
            <Input label="Full name" value={form.name} onChange={(v) => set("name", v)} />
            <Input label="Institutional email" type="email" value={form.email} onChange={(v) => set("email", v)} />
            <Input label="Contact number" value={form.contact} onChange={(v) => set("contact", v)} />
            <Input label={role === "student" ? "Program & year" : "Unit / organization"} value={form.unit} onChange={(v) => set("unit", v)} />
            <Input label="Location" value={form.location} onChange={(v) => set("location", v)} />
            <label className="block sm:col-span-2">
              <span className="mb-1.5 block text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                About
              </span>
              <textarea
                rows={3}
                value={form.bio}
                onChange={(e) => set("bio", e.target.value)}
                className="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground outline-none focus:border-brand focus:ring-2 focus:ring-brand/25"
              />
            </label>
          </div>
        </Card>

        <div className="space-y-5">
          <Card>
            <CardTitle>Profile photo</CardTitle>
            <div
              onDragOver={(e) => e.preventDefault()}
              onDrop={(e) => {
                e.preventDefault();
                pickImage(e.dataTransfer.files?.[0]);
              }}
              className="flex items-center gap-4"
            >
              {form.avatar ? (
                <img
                  src={form.avatar}
                  alt={`${form.name} profile photo`}
                  className="size-20 rounded-full border border-border object-cover"
                />
              ) : (
                <span className="flex size-20 items-center justify-center rounded-full bg-brand text-lg font-semibold text-brand-foreground">
                  {cfg.initials}
                </span>
              )}
              <div className="min-w-0 space-y-2">
                <div className="flex flex-wrap gap-2">
                  <Button variant="outline" onClick={() => fileRef.current?.click()}>
                    {form.avatar ? "Change photo" : "Upload photo"}
                  </Button>
                  {form.avatar && (
                    <Button
                      variant="outline"
                      onClick={() => {
                        set("avatar", "");
                        setImgError(null);
                      }}
                    >
                      Remove
                    </Button>
                  )}
                </div>
                <p className="text-xs text-muted-foreground">JPG or PNG, up to 2 MB. Drag and drop works too.</p>
              </div>
              <input
                ref={fileRef}
                type="file"
                accept="image/*"
                className="hidden"
                onChange={(e) => {
                  pickImage(e.target.files?.[0]);
                  e.target.value = "";
                }}
              />
            </div>
            {imgError && <p className="mt-3 text-xs font-medium text-destructive">{imgError}</p>}
          </Card>

          <Card>
            <CardTitle>Role</CardTitle>
            <div className="flex items-center gap-3">
              {form.avatar ? (
                <img src={form.avatar} alt="" className="size-11 rounded-full border border-border object-cover" />
              ) : (
                <span className="flex size-11 items-center justify-center rounded-full bg-brand text-sm font-semibold text-brand-foreground">
                  {cfg.initials}
                </span>
              )}
              <div className="min-w-0">
                <p className="truncate text-sm font-semibold">{cfg.title}</p>
                <p className="truncate text-xs text-muted-foreground">{form.email}</p>
              </div>
            </div>
            <div className="mt-5">
              <p className="mb-2 text-sm text-muted-foreground">Profile completeness</p>
              <Meter value={completeness} tone={completeness === 100 ? "success" : "warn"} />
            </div>
          </Card>

          <Card>
            <CardTitle>Notifications</CardTitle>
            <div className="space-y-3">
              <label className="flex items-center gap-3 text-sm">
                <input
                  type="checkbox"
                  checked={form.notifyEmail}
                  onChange={(e) => set("notifyEmail", e.target.checked)}
                  className="size-4 accent-[var(--color-brand)]"
                />
                Email me about placement updates
              </label>
              <label className="flex items-center gap-3 text-sm">
                <input
                  type="checkbox"
                  checked={form.notifyDigest}
                  onChange={(e) => set("notifyDigest", e.target.checked)}
                  className="size-4 accent-[var(--color-brand)]"
                />
                Send a weekly digest summary
              </label>
            </div>
          </Card>
        </div>
      </div>
    </>
  );
}
