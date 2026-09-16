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
import { EVALUATIONS, HOSTS, PENDING_RECOMMENDATIONS, PROGRAMS, REQUIREMENTS, STUDENTS } from "@/lib/internmatch";
import { ProfileEditor } from "@/components/im/ProfileEditor";

export function CoordinatorSection({ section }: { section: string }) {
  switch (section) {
    case "profile":
      return <ProfileEditor role="coordinator" />;
    case "":
      return <Dashboard />;
    case "recommendations":
      return <Recommendations />;
    case "approvals":
      return <Approvals />;
    case "students":
      return <Students />;
    case "hosts":
      return <Hosts />;
    case "progress":
      return <Progress />;
    case "requirements":
      return <Requirements />;
    case "evaluations":
      return <Evaluations />;
    case "analytics":
      return <Analytics />;
    case "map":
      return (
        <>
          <PageHeader
            title="Accessibility map"
            subtitle="Placement distribution across host establishments in the Davao Region."
          />
          <AccessibilityMap note="Use the map to balance placements against student travel burden." />
        </>
      );
    default:
      return <PageHeader title="Not found" subtitle="This coordinator page does not exist." />;
  }
}

function Dashboard() {
  return (
    <>
      <PageHeader title="Practicum dashboard" subtitle="AY 2026–2027 · 160 enrolled practicum students" />
      <StatGrid>
        <StatCard label="Students deployed" value="148" tone="success" />
        <StatCard label="Pending approvals" value="9" tone="warn" />
        <StatCard label="Unplaced students" value="3" tone="brand" />
        <StatCard label="Active host partners" value="50" />
      </StatGrid>
      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardTitle right={<Button variant="ghost">View all</Button>}>Recommendations awaiting review</CardTitle>
          <Table head={["Student", "Program", "Recommended host", "Match", "Distance"]}>
            {PENDING_RECOMMENDATIONS.slice(0, 5).map((r) => (
              <Row key={r.student}>
                <td className="font-medium">{r.student}</td>
                <td className="text-muted-foreground">{r.program}</td>
                <td>{r.host}</td>
                <td>
                  <Pill tone={matchTone(r.match)}>{r.match}%</Pill>
                </td>
                <td className="text-muted-foreground">{r.km} km</td>
              </Row>
            ))}
          </Table>
        </Card>
        <Card>
          <CardTitle>Deployment by program</CardTitle>
          <Bars data={PROGRAMS.map((p) => ({ label: p.code, value: p.rate }))} />
          <div className="mt-5 space-y-2 text-sm">
            {PROGRAMS.map((p) => (
              <div key={p.code} className="flex justify-between text-muted-foreground">
                <span>{p.name}</span>
                <span className="font-medium text-foreground">
                  {p.deployed}/{p.students}
                </span>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </>
  );
}

function Recommendations() {
  const [filter, setFilter] = useState("All programs");
  const rows = PENDING_RECOMMENDATIONS.filter((r) => filter === "All programs" || r.program === filter);
  return (
    <>
      <PageHeader
        title="Recommendation review"
        subtitle="Machine-generated matches ranked by competency similarity and accessibility."
        action={<Button>Regenerate batch</Button>}
      />
      <FilterChips options={["All programs", "BSIT", "BSCS", "BSIS"]} value={filter} onChange={setFilter} />
      <Card>
        <Table head={["Student", "Program", "Recommended host", "Match", "Distance", "Action"]}>
          {rows.map((r) => (
            <Row key={r.student}>
              <td className="font-medium">{r.student}</td>
              <td className="text-muted-foreground">{r.program}</td>
              <td>{r.host}</td>
              <td>
                <Pill tone={matchTone(r.match)}>{r.match}%</Pill>
              </td>
              <td className="text-muted-foreground">{r.km} km</td>
              <td className="space-x-3">
                <button type="button" className="text-sm font-semibold text-brand hover:underline">
                  Approve
                </button>
                <button type="button" className="text-sm font-semibold text-muted-foreground hover:underline">
                  Override
                </button>
              </td>
            </Row>
          ))}
        </Table>
      </Card>
    </>
  );
}

function Approvals() {
  return (
    <>
      <PageHeader title="Pending approvals" subtitle="9 placements and 12 documents require your action." />
      <div className="grid gap-5 lg:grid-cols-2">
        <Card>
          <CardTitle>Placement approvals</CardTitle>
          <div className="space-y-3">
            {PENDING_RECOMMENDATIONS.slice(0, 4).map((r) => (
              <div key={r.student} className="rounded-md border border-border p-4">
                <div className="flex items-center justify-between gap-3">
                  <p className="font-semibold">{r.student}</p>
                  <Pill tone={matchTone(r.match)}>{r.match}% match</Pill>
                </div>
                <p className="mt-1 text-sm text-muted-foreground">
                  {r.host} · {r.km} km
                </p>
                <div className="mt-3 flex gap-2">
                  <Button>Approve</Button>
                  <Button variant="outline">Reassign</Button>
                </div>
              </div>
            ))}
          </div>
        </Card>
        <Card>
          <CardTitle>Document approvals</CardTitle>
          <Table head={["Document", "Student", "Status"]}>
            {REQUIREMENTS.map((r) => (
              <Row key={r.name}>
                <td className="font-medium">{r.name}</td>
                <td className="text-muted-foreground">Trisha Talamillo</td>
                <td>
                  <Pill tone={statusTone(r.status)}>{r.status}</Pill>
                </td>
              </Row>
            ))}
          </Table>
        </Card>
      </div>
    </>
  );
}

function Students() {
  const [filter, setFilter] = useState("All");
  const rows = STUDENTS.filter((s) => filter === "All" || s.status === filter);
  return (
    <>
      <PageHeader title="Student profiles" subtitle="160 practicum students across three programs." />
      <FilterChips options={["All", "Deployed", "Pending approval", "Unplaced"]} value={filter} onChange={setFilter} />
      <Card>
        <Table head={["Student", "Program", "Profile", "Status", "Host establishment"]}>
          {rows.map((s) => (
            <Row key={s.name}>
              <td className="font-medium">{s.name}</td>
              <td className="text-muted-foreground">{s.program}</td>
              <td className="w-40">
                <Meter value={s.completeness} tone={s.completeness > 80 ? "success" : "warn"} />
                <span className="text-xs text-muted-foreground">{s.completeness}%</span>
              </td>
              <td>
                <Pill tone={statusTone(s.status)}>{s.status}</Pill>
              </td>
              <td className="text-muted-foreground">{s.host}</td>
            </Row>
          ))}
        </Table>
      </Card>
    </>
  );
}

function Hosts() {
  return (
    <>
      <PageHeader
        title="Host establishments"
        subtitle="Partner organisations, slot capacity, and agreement status."
        action={<Button>Add establishment</Button>}
      />
      <div className="grid gap-4 xl:grid-cols-2">
        {HOSTS.map((h) => (
          <Card key={h.name}>
            <div className="flex items-start justify-between gap-3">
              <div>
                <h3 className="text-lg font-semibold">{h.name}</h3>
                <p className="text-sm text-muted-foreground">
                  {h.field} · {h.city}
                </p>
              </div>
              <Pill tone={statusTone(h.moa)}>{h.moa}</Pill>
            </div>
            <div className="mt-4 grid grid-cols-3 gap-3">
              <Field label="Slots" value={`${h.slotsOpen}/${h.slotsTotal}`} />
              <Field label="Distance" value={`${h.km} km`} />
              <Field label="Rating" value={`${h.rating} / 5.0`} />
            </div>
          </Card>
        ))}
      </div>
    </>
  );
}

function Progress() {
  return (
    <>
      <PageHeader title="Internship progress" subtitle="Hours logged against the 486-hour practicum requirement." />
      <StatGrid>
        <StatCard label="Average completion" value="52%" />
        <StatCard label="On track" value="131" tone="success" />
        <StatCard label="At risk" value="14" tone="warn" />
        <StatCard label="Behind schedule" value="3" tone="brand" />
      </StatGrid>
      <Card>
        <CardTitle>Progress by student</CardTitle>
        <Table head={["Student", "Program", "Hours", "Completion", "Status"]}>
          {STUDENTS.map((s, i) => {
            const hours = 120 + i * 62;
            const pct = Math.round((hours / 486) * 100);
            return (
              <Row key={s.name}>
                <td className="font-medium">{s.name}</td>
                <td className="text-muted-foreground">{s.program}</td>
                <td>{hours} / 486</td>
                <td className="w-40">
                  <Meter value={pct} tone={pct > 50 ? "success" : "warn"} />
                </td>
                <td>
                  <Pill tone={pct > 50 ? "success" : "warn"}>{pct > 50 ? "On track" : "At risk"}</Pill>
                </td>
              </Row>
            );
          })}
        </Table>
      </Card>
    </>
  );
}

function Requirements() {
  return (
    <>
      <PageHeader title="Requirements tracking" subtitle="Document compliance across all deployed students." />
      <StatGrid>
        <StatCard label="Complete" value="118" tone="success" />
        <StatCard label="Under review" value="26" tone="warn" />
        <StatCard label="Missing" value="16" tone="brand" />
        <StatCard label="Compliance rate" value="74%" />
      </StatGrid>
      <Card>
        <CardTitle>Compliance by document type</CardTitle>
        <Bars
          data={[
            { label: "Medical Clearance", value: 96 },
            { label: "Parental Consent", value: 93 },
            { label: "Endorsement Letter", value: 88 },
            { label: "Memorandum of Agreement", value: 71 },
            { label: "Insurance Certificate", value: 64 },
            { label: "Weekly Journals", value: 58 },
          ]}
        />
      </Card>
    </>
  );
}

function Evaluations() {
  return (
    <>
      <PageHeader title="Evaluations" subtitle="Supervisor assessments submitted per evaluation period." />
      <Card>
        <Table head={["Intern", "Period", "Technical", "Work ethic", "Communication", "Overall", "Status"]}>
          {EVALUATIONS.map((e) => (
            <Row key={e.intern + e.period}>
              <td className="font-medium">{e.intern}</td>
              <td className="text-muted-foreground">{e.period}</td>
              <td>{e.technical}</td>
              <td>{e.work}</td>
              <td>{e.communication}</td>
              <td className="font-semibold">{e.overall}</td>
              <td>
                <Pill tone={statusTone(e.status)}>{e.status}</Pill>
              </td>
            </Row>
          ))}
        </Table>
      </Card>
    </>
  );
}

function Analytics() {
  return (
    <>
      <PageHeader title="Analytics" subtitle="Placement outcomes, match quality, and travel burden." />
      <StatGrid>
        <StatCard label="Average match score" value="84%" tone="success" />
        <StatCard label="Median travel distance" value="5.6 km" />
        <StatCard label="Override rate" value="11%" tone="warn" />
        <StatCard label="Completion rate" value="93%" tone="success" />
      </StatGrid>
      <div className="grid gap-5 lg:grid-cols-2">
        <Card>
          <CardTitle>Placement rate by program</CardTitle>
          <Bars data={PROGRAMS.map((p) => ({ label: p.name, value: p.rate }))} />
        </Card>
        <Card>
          <CardTitle>Placements by distance band</CardTitle>
          <Bars
            data={[
              { label: "Under 5 km", value: 46 },
              { label: "5 – 10 km", value: 31 },
              { label: "10 – 20 km", value: 17 },
              { label: "Over 20 km", value: 6 },
            ]}
          />
        </Card>
      </div>
    </>
  );
}
