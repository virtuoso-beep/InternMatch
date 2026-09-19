import { NotificationList } from "@/components/im/NotificationList";
import { AccessibilityMap } from "@/components/im/AccessibilityMap";
import { toast } from "sonner";
import {
  Bars,
  Button,
  downloadText,
  Card,
  CardTitle,
  Field,
  PageHeader,
  Pill,
  Row,
  StatCard,
  StatGrid,
  Table,
  statusTone,
} from "@/components/im/ui";
import { AUDIT, HOSTS, PROGRAMS } from "@/lib/internmatch";
import { ProfileEditor } from "@/components/im/ProfileEditor";

export function DeanSection({ section }: { section: string }) {
  switch (section) {
    case "notifications": return <NotificationList />;
    case "profile":
      return <ProfileEditor role="dean" />;
    case "":
      return <Dashboard />;
    case "performance":
      return <Performance />;
    case "hosts":
      return <Hosts />;
    case "equity":
      return <Equity />;
    case "analytics":
      return <Analytics />;
    case "accreditation":
      return <Accreditation />;
    case "export":
      return <Export />;
    case "audit":
      return <Audit />;
    default:
      return <PageHeader title="Not found" subtitle="This dean page does not exist." />;
  }
}

function Dashboard() {
  return (
    <>
      <PageHeader title="Executive dashboard" subtitle="College of Computing Education · AY 2026–2027" />
      <StatGrid>
        <StatCard label="Practicum students" value="160" />
        <StatCard label="Placement rate" value="93%" tone="success" />
        <StatCard label="Active partners" value="50" />
        <StatCard label="Avg. supervisor rating" value="4.4" tone="success" />
      </StatGrid>
      <div className="grid gap-5 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardTitle>Program performance</CardTitle>
          <Table head={["Program", "Students", "Deployed", "Partners", "Placement rate"]}>
            {PROGRAMS.map((p) => (
              <Row key={p.code}>
                <td className="font-medium">{p.name}</td>
                <td>{p.students}</td>
                <td>{p.deployed}</td>
                <td>{p.partners}</td>
                <td>
                  <Pill tone={p.rate >= 92 ? "success" : "warn"}>{p.rate}%</Pill>
                </td>
              </Row>
            ))}
          </Table>
        </Card>
        <Card>
          <CardTitle>Placement equity</CardTitle>
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

function Performance() {
  return (
    <>
      <PageHeader title="Program performance" subtitle="Outcomes per program across the practicum cycle." />
      <div className="grid gap-5 lg:grid-cols-2">
        <Card>
          <CardTitle>Placement rate</CardTitle>
          <Bars data={PROGRAMS.map((p) => ({ label: p.code, value: p.rate }))} />
        </Card>
        <Card>
          <CardTitle>Completion rate</CardTitle>
          <Bars
            data={[
              { label: "BSIT", value: 91 },
              { label: "BSCS", value: 87 },
              { label: "BSIS", value: 94 },
            ]}
          />
        </Card>
        <Card className="lg:col-span-2">
          <CardTitle>Detailed breakdown</CardTitle>
          <Table head={["Program", "Students", "Deployed", "Completed", "Avg. rating", "Partners"]}>
            {PROGRAMS.map((p, i) => (
              <Row key={p.code}>
                <td className="font-medium">{p.name}</td>
                <td>{p.students}</td>
                <td>{p.deployed}</td>
                <td>{p.deployed - i - 2}</td>
                <td>{(4.6 - i * 0.15).toFixed(1)}</td>
                <td>{p.partners}</td>
              </Row>
            ))}
          </Table>
        </Card>
      </div>
    </>
  );
}

function Hosts() {
  return (
    <>
      <PageHeader title="Host establishments" subtitle="Partner network health and agreement coverage." />
      <StatGrid>
        <StatCard label="Active partners" value="50" tone="success" />
        <StatCard label="MOAs expiring in 90 days" value="6" tone="warn" />
        <StatCard label="New partners this year" value="8" />
        <StatCard label="Avg. slots per partner" value="3.4" />
      </StatGrid>
      <Card>
        <Table head={["Establishment", "Industry", "City", "Slots", "Rating", "MOA"]}>
          {HOSTS.map((h) => (
            <Row key={h.name}>
              <td className="font-medium">{h.name}</td>
              <td className="text-muted-foreground">{h.field}</td>
              <td className="text-muted-foreground">{h.city}</td>
              <td>
                {h.slotsOpen}/{h.slotsTotal}
              </td>
              <td>{h.rating}</td>
              <td>
                <Pill tone={statusTone(h.moa)}>{h.moa}</Pill>
              </td>
            </Row>
          ))}
        </Table>
      </Card>
    </>
  );
}

function Equity() {
  return (
    <>
      <PageHeader
        title="Placement equity"
        subtitle="Ensuring geographic accessibility is fairly distributed across students."
      />
      <StatGrid>
        <StatCard label="Median travel distance" value="5.6 km" />
        <StatCard label="Students over 20 km" value="9" tone="warn" />
        <StatCard label="High-accessibility placements" value="68%" tone="success" />
        <StatCard label="Equity index" value="0.82" tone="success" />
      </StatGrid>
      <AccessibilityMap note="Low-accessibility markers highlight where travel burden is concentrated." />
    </>
  );
}

function Analytics() {
  return (
    <>
      <PageHeader title="Analytics" subtitle="Trends across recommendation quality and placement outcomes." />
      <div className="grid gap-5 lg:grid-cols-2">
        <Card>
          <CardTitle>Match score distribution</CardTitle>
          <Bars
            data={[
              { label: "90–100%", value: 28 },
              { label: "80–89%", value: 41 },
              { label: "70–79%", value: 22 },
              { label: "Below 70%", value: 9 },
            ]}
          />
        </Card>
        <Card>
          <CardTitle>Placement rate trend</CardTitle>
          <Bars
            data={[
              { label: "AY 2023–2024", value: 84 },
              { label: "AY 2024–2025", value: 88 },
              { label: "AY 2025–2026", value: 91 },
              { label: "AY 2026–2027", value: 93 },
            ]}
          />
        </Card>
        <Card className="lg:col-span-2">
          <CardTitle>Top competencies demanded by partners</CardTitle>
          <Bars
            data={[
              { label: "Web Development", value: 78 },
              { label: "Database Design", value: 66 },
              { label: "Networking", value: 51 },
              { label: "Data Analysis", value: 47 },
              { label: "Technical Support", value: 39 },
            ]}
          />
        </Card>
      </div>
    </>
  );
}

function Accreditation() {
  const reports = [
    { name: "Practicum Placement Summary", period: "AY 2026–2027", status: "Ready" },
    { name: "Industry Partner Inventory", period: "AY 2026–2027", status: "Ready" },
    { name: "Student Outcomes Report", period: "AY 2025–2026", status: "Ready" },
    { name: "Equity & Accessibility Report", period: "AY 2026–2027", status: "Pending" },
  ];
  return (
    <>
      <PageHeader title="Accreditation reports" subtitle="Pre-formatted reports for accreditation submissions." />
      <Card>
        <Table head={["Report", "Coverage", "Status", "Action"]}>
          {reports.map((r) => (
            <Row key={r.name}>
              <td className="font-medium">{r.name}</td>
              <td className="text-muted-foreground">{r.period}</td>
              <td>
                <Pill tone={statusTone(r.status)}>{r.status}</Pill>
              </td>
              <td>
                <button type="button" onClick={() => downloadText(`${r.name.toLowerCase().replaceAll(" ", "-")}.txt`, `${r.name}\nCoverage: ${r.period}\nStatus: Generated\n`)} className="text-sm font-semibold text-brand hover:underline">
                  Generate
                </button>
              </td>
            </Row>
          ))}
        </Table>
      </Card>
    </>
  );
}

function Export() {
  const exportDataset = (title: string) => downloadText(`${title.toLowerCase().replaceAll(" ", "-")}.csv`, `Dataset,Records\n${title},Available in InternMatch\n`, "text/csv");
  return (
    <>
      <PageHeader title="Export data" subtitle="Download practicum datasets for institutional reporting." />
      <div className="grid gap-4 md:grid-cols-2">
        {[
          ["Student placements", "160 records · CSV, XLSX"],
          ["Host establishments", "50 records · CSV, XLSX"],
          ["Evaluations", "312 records · CSV"],
          ["Attendance logs", "8,412 records · CSV"],
        ].map(([title, meta]) => (
          <Card key={title}>
            <div className="flex items-center justify-between gap-3">
              <div>
                <p className="font-semibold">{title}</p>
                <p className="mt-1 text-sm text-muted-foreground">{meta}</p>
              </div>
              <Button variant="outline" onClick={() => exportDataset(title ?? "Report")}>Export</Button>
            </div>
          </Card>
        ))}
      </div>
    </>
  );
}

function Audit() {
  return (
    <>
      <PageHeader title="Audit trail" subtitle="Read-only record of placement and reporting activity." />
      <Card>
        <Table head={["Timestamp", "Actor", "Action", "Target"]}>
          {AUDIT.map((a) => (
            <Row key={a.time}>
              <td className="text-muted-foreground">{a.time}</td>
              <td className="font-medium">{a.actor}</td>
              <td>{a.action}</td>
              <td className="text-muted-foreground">{a.target}</td>
            </Row>
          ))}
        </Table>
        <div className="mt-4">
          <Field label="Retention policy" value="Audit records retained for 5 academic years" />
        </div>
      </Card>
    </>
  );
}
