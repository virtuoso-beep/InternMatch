import { useRef, useState } from "react";
import { toast } from "sonner";
import { AccessibilityMap } from "@/components/im/AccessibilityMap";
import {
  Bars,
  ActionDialog,
  Button,
  Card,
  CardTitle,
  Field,
  FilterChips,
  Meter,
  PageHeader,
  Pill,
  Row,
  StatCard,
  StatGrid,
  Table,
  matchTone,
  statusTone,
} from "@/components/im/ui";
import { ProfileEditor } from "@/components/im/ProfileEditor";
import { COMPETENCIES, getCompetencyMatch, HOSTS, REQUIREMENTS } from "@/lib/internmatch";

export function StudentSection({ section }: { section: string }) {
  switch (section) {
    case "":
      return <Dashboard />;
    case "profile":
      return <ProfileEditor role="student" />;
    case "competencies":
      return <Competencies />;
    case "recommendations":
      return <Recommendations />;
    case "internship":
      return <Internship />;
    case "requirements":
      return <Requirements />;
    case "map":
      return (
        <>
          <PageHeader
            title="Accessibility map"
            subtitle="Preview host locations and available slots. Live student-to-host distances are not connected yet."
          />
          <AccessibilityMap />
        </>
      );
    case "notifications":
      return <Notifications />;
    default:
      return <PageHeader title="Not found" subtitle="This student page does not exist." />;
  }
}

function Dashboard() {
  return (
    <>
      <PageHeader title="Welcome back, Trisha" subtitle="BS Information Technology · 4th Year · AY 2026–2027" />
      <StatGrid>
        <StatCard label="Profile completeness" value="86%" tone="success" />
        <StatCard label="Recommended matches" value="5" />
        <StatCard label="Requirements pending" value="3" tone="warn" />
        <StatCard label="Logged hours" value="214 / 486" />
      </StatGrid>
      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardTitle right={<Pill tone="info">Model v1.4</Pill>}>Top recommendations for you</CardTitle>
          <div className="space-y-3">
            {HOSTS.slice(0, 3).map((h) => (
              <div key={h.name} className="flex items-center justify-between gap-4 rounded-md border border-border p-4">
                <div>
                  <p className="font-semibold">{h.name}</p>
                  <p className="text-sm text-muted-foreground">
                    {h.field} · {h.city} · {h.km} km
                  </p>
                </div>
                <Pill tone={matchTone(h.match)}>{h.match}% match</Pill>
              </div>
            ))}
          </div>
        </Card>
        <Card>
          <CardTitle>Requirement checklist</CardTitle>
          <div className="space-y-3">
            {REQUIREMENTS.slice(0, 5).map((r) => (
              <div key={r.name} className="flex items-center justify-between gap-3 text-sm">
                <span>{r.name}</span>
                <Pill tone={statusTone(r.status)}>{r.status}</Pill>
              </div>
            ))}
          </div>
        </Card>
      </div>
      <Card className="mt-5">
        <CardTitle>Internship progress</CardTitle>
        <p className="mb-2 text-sm text-muted-foreground">214 of 486 required hours completed (44%)</p>
        <Meter value={44} tone="success" />
      </Card>
    </>
  );
}

function Competencies() {
  const [dialogOpen, setDialogOpen] = useState(false);
  return (
    <>
      <PageHeader
        title="Competencies"
        subtitle="Your skill vector feeds the recommendation engine's similarity search."
        action={<Button variant="outline" onClick={() => setDialogOpen(true)}>Add competency</Button>}
      />
      <div className="grid gap-5 lg:grid-cols-2">
        <Card>
          <CardTitle>Proficiency levels</CardTitle>
          <Bars data={COMPETENCIES.map((c) => ({ label: c.name, value: c.level }))} />
        </Card>
        <Card>
          <CardTitle>Evidence sources</CardTitle>
          <Table head={["Competency", "Level", "Source"]}>
            {COMPETENCIES.map((c) => (
              <Row key={c.name}>
                <td className="font-medium">{c.name}</td>
                <td>{c.level}%</td>
                <td className="text-muted-foreground">{c.source}</td>
              </Row>
            ))}
          </Table>
        </Card>
      </div>
      <ActionDialog
        open={dialogOpen}
        onOpenChange={setDialogOpen}
        title="Add competency"
        description="Add a skill to your competency profile."
        fields={[{ name: "name", label: "Competency", placeholder: "e.g. API Development" }, { name: "level", label: "Proficiency (%)", type: "number", placeholder: "75" }]}
        onSubmit={(values) => { setDialogOpen(false); toast.success(`${values['name']} added at ${values['level']}% proficiency.`); }}
      />
    </>
  );
}

function Recommendations() {
  const [filter, setFilter] = useState("Best match");
  const [interested, setInterested] = useState<string[]>([]);
  const [details, setDetails] = useState<(typeof HOSTS)[number] | null>(null);
  const sorted = [...HOSTS].sort((a, b) =>
    filter === "Nearest" ? a.km - b.km : filter === "Most slots" ? b.slotsOpen - a.slotsOpen : getCompetencyMatch(b).score - getCompetencyMatch(a).score,
  );
  return (
    <>
      <PageHeader
        title="Recommended internships"
        subtitle="Ranked by competency similarity, geographic accessibility, and slot availability."
      />
      <FilterChips options={["Best match", "Nearest", "Most slots"]} value={filter} onChange={setFilter} />
      <div className="grid gap-4 xl:grid-cols-2">
        {sorted.map((h) => (
          <Card key={h.name}>
            {(() => {
              const competencyMatch = getCompetencyMatch(h);
              return (
                <>
            <div className="flex items-start justify-between gap-4">
              <div>
                <h3 className="text-lg font-semibold">{h.name}</h3>
                <p className="text-sm text-muted-foreground">
                  {h.field} · {h.city}
                </p>
              </div>
              <Pill tone={matchTone(competencyMatch.score)}>{competencyMatch.score}% match</Pill>
            </div>
            <div className="mt-4 flex flex-wrap gap-2">
              {competencyMatch.matched.map((competency) => (
                <Pill key={competency.name} tone="success">
                  {competency.name} · {competency.level}%
                </Pill>
              ))}
              {competencyMatch.missing.map((tag) => <Pill key={tag} tone="warn">Missing: {tag}</Pill>)}
            </div>
            <p className="mt-3 text-xs text-muted-foreground">
              {competencyMatch.coverage}% competency coverage · {competencyMatch.proficiency}% average proficiency
            </p>
            <div className="mt-4 grid grid-cols-3 gap-3 text-sm">
              <Field label="Distance" value={`${h.km} km`} />
              <Field label="Travel" value={h.travel} />
              <Field label="Slots" value={`${h.slotsOpen}/${h.slotsTotal}`} />
            </div>
            <div className="mt-5 flex gap-2">
              <Button onClick={() => setInterested((current) => current.includes(h.name) ? current.filter((name) => name !== h.name) : [...current, h.name])}>
                {interested.includes(h.name) ? "Interest sent" : "Express interest"}
              </Button>
              <Button variant="outline" onClick={() => setDetails(h)}>View details</Button>
            </div>
                </>
              );
            })()}
          </Card>
        ))}
      </div>
      {details && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 px-4" role="presentation" onClick={() => setDetails(null)}>
          <div role="dialog" aria-modal="true" className="w-full max-w-md rounded-lg border border-border bg-card p-5 shadow-xl" onClick={(event) => event.stopPropagation()}>
            <h2 className="text-lg font-semibold">{details.name}</h2>
            <p className="mt-1 text-sm text-muted-foreground">{details.field} · {details.city}</p>
            <div className="mt-4 grid grid-cols-2 gap-3 text-sm"><Field label="Distance" value={`${details.km} km`} /><Field label="Travel" value={details.travel} /><Field label="Rating" value={`${details.rating} / 5`} /><Field label="Open slots" value={`${details.slotsOpen}/${details.slotsTotal}`} /></div>
            <div className="mt-5 flex justify-end"><Button onClick={() => setDetails(null)}>Close</Button></div>
          </div>
        </div>
      )}
    </>
  );
}

function Internship() {
  return (
    <>
      <PageHeader title="My internship" subtitle="DataCore Solutions Inc. · Web Development Intern" />
      <StatGrid>
        <StatCard label="Hours logged" value="214" />
        <StatCard label="Hours remaining" value="272" tone="warn" />
        <StatCard label="Weeks completed" value="6 of 14" />
        <StatCard label="Supervisor rating" value="4.6" tone="success" />
      </StatGrid>
      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardTitle>Weekly time log</CardTitle>
          <Table head={["Week", "Dates", "Hours", "Journal"]}>
            {[
              ["Week 6", "Aug 24 – Aug 28, 2026", "38", "Submitted"],
              ["Week 5", "Aug 17 – Aug 21, 2026", "40", "Submitted"],
              ["Week 4", "Aug 10 – Aug 14, 2026", "36", "Submitted"],
              ["Week 3", "Aug 3 – Aug 7, 2026", "40", "Submitted"],
            ].map((r) => (
              <Row key={r[0]}>
                <td className="font-medium">{r[0]}</td>
                <td className="text-muted-foreground">{r[1]}</td>
                <td>{r[2]}</td>
                <td>
                  <Pill tone="success">{r[3]}</Pill>
                </td>
              </Row>
            ))}
          </Table>
        </Card>
        <Card>
          <CardTitle>Placement details</CardTitle>
          <div className="space-y-4">
            <Field label="Supervisor" value="Rico Fernandez" />
            <Field label="Department" value="Application Development" />
            <Field label="Start date" value="Jul 20, 2026" />
            <Field label="Target completion" value="Nov 6, 2026" />
            <Field label="MOA status" value={<Pill tone="success">Active</Pill>} />
          </div>
        </Card>
      </div>
    </>
  );
}

function Requirements() {
  const fileRef = useRef<HTMLInputElement>(null);
  const [viewing, setViewing] = useState<(typeof REQUIREMENTS)[number] | null>(null);
  const chooseDocument = () => fileRef.current?.click();
  return (
    <>
      <PageHeader
        title="Requirements"
        subtitle="Upload and track the documents required before and during deployment."
        action={<Button onClick={chooseDocument}>Upload document</Button>}
      />
      <input
        ref={fileRef}
        type="file"
        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
        className="hidden"
        onChange={(event) => {
          const file = event.target.files?.[0];
          if (file) toast.success(`${file.name} selected for upload.`);
          event.target.value = "";
        }}
      />
      <Card>
        <Table head={["Document", "Status", "Date", "Action"]}>
          {REQUIREMENTS.map((r) => (
            <Row key={r.name}>
              <td className="font-medium">{r.name}</td>
              <td>
                <Pill tone={statusTone(r.status)}>{r.status}</Pill>
              </td>
              <td className="text-muted-foreground">{r.date}</td>
              <td>
                <button type="button" onClick={r.status === "Missing" ? chooseDocument : () => setViewing(r)} className="text-sm font-semibold text-brand hover:underline">
                  {r.status === "Missing" ? "Upload" : "View"}
                </button>
              </td>
            </Row>
          ))}
        </Table>
      </Card>
      {viewing && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-foreground/30 px-4" role="presentation" onClick={() => setViewing(null)}>
          <div role="dialog" aria-modal="true" className="w-full max-w-2xl rounded-lg border border-border bg-card p-5 shadow-xl" onClick={(event) => event.stopPropagation()}>
            <div className="flex items-center justify-between gap-3 border-b border-border pb-3">
              <div><h2 className="text-lg font-semibold">{viewing.name}</h2><p className="text-sm text-muted-foreground">Uploaded document preview</p></div>
              <Button variant="ghost" onClick={() => setViewing(null)}>Close</Button>
            </div>
            <div className="mt-5 flex min-h-80 items-center justify-center rounded-md border border-border bg-muted p-8 text-center">
              <div><p className="font-semibold">{viewing.name}</p><p className="mt-2 text-sm text-muted-foreground">This uploaded document is available for viewing.</p><p className="mt-1 text-xs text-muted-foreground">Status: {viewing.status} · Submitted: {viewing.date}</p></div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}

function Notifications() {
  const items = [
    { title: "Placement approved", body: "Your placement at DataCore Solutions Inc. was approved by the coordinator.", time: "2 hours ago", tone: "success" as const },
    { title: "Week 6 journal due", body: "Submit your weekly journal before Aug 30, 2026.", time: "Yesterday", tone: "warn" as const },
    { title: "New recommendation", body: "Davao Region IT Hub now matches your profile at 90%.", time: "3 days ago", tone: "info" as const },
    { title: "Insurance certificate received", body: "Your document is under review by the practicum office.", time: "Aug 9, 2026", tone: "muted" as const },
  ];
  return (
    <>
      <PageHeader title="Notifications" subtitle="Updates on placements, requirements, and evaluations." />
      <div className="space-y-3">
        {items.map((n) => (
          <Card key={n.title} className="flex items-start justify-between gap-4">
            <div>
              <p className="font-semibold">{n.title}</p>
              <p className="mt-1 text-sm text-muted-foreground">{n.body}</p>
            </div>
            <div className="shrink-0 text-right">
              <Pill tone={n.tone}>New</Pill>
              <p className="mt-2 text-xs text-muted-foreground">{n.time}</p>
            </div>
          </Card>
        ))}
      </div>
    </>
  );
}
