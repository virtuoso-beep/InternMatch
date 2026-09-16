import { useState } from "react";
import { AccessibilityMap } from "@/components/im/AccessibilityMap";
import {
  Bars,
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
import { COMPETENCIES, HOSTS, REQUIREMENTS } from "@/lib/internmatch";

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
            subtitle="Compare host establishments by distance, travel time, and available slots."
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
  return (
    <>
      <PageHeader
        title="Competencies"
        subtitle="Your skill vector feeds the recommendation engine's similarity search."
        action={<Button variant="outline">Add competency</Button>}
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
    </>
  );
}

function Recommendations() {
  const [filter, setFilter] = useState("Best match");
  const sorted = [...HOSTS].sort((a, b) =>
    filter === "Nearest" ? a.km - b.km : filter === "Most slots" ? b.slotsOpen - a.slotsOpen : b.match - a.match,
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
            <div className="flex items-start justify-between gap-4">
              <div>
                <h3 className="text-lg font-semibold">{h.name}</h3>
                <p className="text-sm text-muted-foreground">
                  {h.field} · {h.city}
                </p>
              </div>
              <Pill tone={matchTone(h.match)}>{h.match}% match</Pill>
            </div>
            <div className="mt-4 flex flex-wrap gap-2">
              {h.tags.map((t) => (
                <Pill key={t} tone="muted">
                  {t}
                </Pill>
              ))}
            </div>
            <div className="mt-4 grid grid-cols-3 gap-3 text-sm">
              <Field label="Distance" value={`${h.km} km`} />
              <Field label="Travel" value={h.travel} />
              <Field label="Slots" value={`${h.slotsOpen}/${h.slotsTotal}`} />
            </div>
            <div className="mt-5 flex gap-2">
              <Button>Express interest</Button>
              <Button variant="outline">View details</Button>
            </div>
          </Card>
        ))}
      </div>
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
  return (
    <>
      <PageHeader
        title="Requirements"
        subtitle="Upload and track the documents required before and during deployment."
        action={<Button>Upload document</Button>}
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
                <button type="button" className="text-sm font-semibold text-brand hover:underline">
                  {r.status === "Missing" ? "Upload" : "View"}
                </button>
              </td>
            </Row>
          ))}
        </Table>
      </Card>
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
